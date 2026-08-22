<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Sector;
use App\Models\User;
use App\Models\UserRole;
use App\Support\V2\Presenters\Presenter;
use App\Support\V2\Presenters\SectorPresenter;
use App\Support\V2\WireEnums;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Users & security (API_REFERENCE.md §11.9). System Admin only for the
 * directory + create flows; `me` flows (change password, update photo) are for
 * the authenticated user.
 */
class UsersService
{
    /** Wire roles we accept on create; mapped to DB enum values. */
    private const WIRE_TO_DB_ROLE = [
        'governor' => 'Governor',
        'coordinator' => 'Coordinator',
        'sector_head' => 'Sector Head',
        'data_admin' => 'Data Admin',
        'facilitator' => 'Facilitator',
        'system_admin' => 'System Admin',
    ];

    private const DB_ROLE_TO_TARGET = [
        'Governor' => 'State',
        'System Admin' => 'System',
        'Coordinator' => 'Deliverable',
        'Deputy Coordinator' => 'Deliverable',
        'Sector Head' => 'Sector',
        'Data Admin' => 'Sector',
        'Facilitator' => 'Sector',
    ];

    /**
     * Display rank for GET /users. Lower number = higher rank = appears earlier
     * in the list. Anything not listed (legacy/unknown roles, deprecated rows,
     * or users whose active role got revoked between query and sort) falls to
     * rank 99 and sorts last.
     */
    private const ROLE_RANK = [
        'Governor' => 1,
        'Coordinator' => 2,
        'Deputy Coordinator' => 3,
        'Sector Head' => 4,
        'Facilitator' => 5,
        'Data Admin' => 6,
        'System Admin' => 7,
        // Deprecated DB roles — kept just above unknown so legacy rows still
        // sort sensibly if any remain in production.
        'Sector Admin' => 8,
        'Delivery Department' => 8,
    ];

    public function listUsers(User $user, ?string $search, string $roleFilter, string $sectorFilter): array
    {
        $this->assertAdmin($user);

        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('role_status', 'Active'))
            // Eager-load active roles (newest first) so the rank-sort below
            // doesn't fire one query per user. Matches User::getCurrentRole()'s
            // ordering so the rank we sort by lines up with the role listRow displays.
            ->with(['roles' => fn ($q) => $q->where('role_status', 'Active')->orderByDesc('id')]);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== 'all') {
            $dbRole = WireEnums::wireToRole($roleFilter) ?? self::WIRE_TO_DB_ROLE[$roleFilter] ?? null;
            if ($dbRole) {
                $query->whereHas('roles', fn ($q) => $q->where('role_status', 'Active')->where('role', $dbRole));
            }
        }

        if ($sectorFilter !== 'all') {
            $matchingSectorIds = $this->sectorIdsMatchingWire($sectorFilter);
            $query->whereHas('roles', fn ($q) => $q->where('role_status', 'Active')->whereIn('entity_id', $matchingSectorIds ?: [-1]));
        }

        // Sort by rank (Governor → ... → System Admin → deprecated → unknown),
        // then alphabetically by full name within each rank. The composite
        // key string sorts the zero-padded rank first, then the lowercased
        // name — single-pass equivalent of a multi-column ORDER BY.
        return $query->get()
            ->sortBy(fn (User $u) => sprintf(
                '%02d|%s',
                self::ROLE_RANK[optional($u->roles->first())->role ?? ''] ?? 99,
                strtolower((string) $u->full_name),
            ))
            ->values()
            ->map(fn (User $u) => $this->listRow($u))
            ->all();
    }

    public function getUserProfile(User $caller, string $id): array
    {
        $this->assertAdmin($caller);

        $user = User::find($id);
        if (! $user) {
            throw ApiException::notFound('User not found.');
        }

        $role = $user->getCurrentRole();
        $sector = $role && $role->target_entity === 'Sector' ? Sector::find($role->entity_id) : null;

        return [
            'id' => (string) $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'initials' => Presenter::initials($user->full_name),
            'accent' => SectorPresenter::accent($user->id),
            'role' => WireEnums::roleToWire(optional($role)->role),
            'roleLabel' => optional($role)->role ?? 'No Role',
            'sectorLabel' => $sector?->sector_name ?? 'Cross-Sector',
            'fullLegalName' => $user->full_name,
            'staffId' => 'PDCU-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'joinDate' => optional($user->created_at)->format('M j, Y') ?? '—',
            'bio' => '—',
            'twoFactorStatus' => 'Disabled',
            'isVerified' => true,
        ];
    }

    public function createUser(User $caller, array $form, ?UploadedFile $photo): void
    {
        $this->assertAdmin($caller);

        $dbRole = self::WIRE_TO_DB_ROLE[$form['role']] ?? null;
        if (! $dbRole) {
            throw ApiException::unprocessable('Unsupported role.', ['role' => 'Role is not supported.']);
        }

        if (User::where('email', $form['email'])->exists()) {
            throw ApiException::conflict('A user with that email already exists.');
        }

        DB::transaction(function () use ($form, $photo, $dbRole) {
            $user = new User();
            $user->full_name = $form['fullName'];
            $user->email = $form['email'];
            $user->phone_number = $form['phone'];
            $user->role = 0;
            $user->image_url = '';
            $user->password = Str::random(16); // hashed by cast; admin must reset
            $user->must_change_password = true;
            if (! empty($form['avatarKey'])) {
                $user->avatar_key = $form['avatarKey'];
            }
            $user->save();

            UserRole::create([
                'user_id' => $user->id,
                'role' => $dbRole,
                'target_entity' => self::DB_ROLE_TO_TARGET[$dbRole] ?? 'System',
                'entity_id' => 0,
                'role_status' => 'Active',
            ]);

            if ($photo) {
                $this->savePhoto($user, $photo);
            }
        });
    }

    public function changeMyPassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ApiException::unprocessable('Current password is incorrect.', ['currentPassword' => 'Current password is incorrect.']);
        }

        $user->password = $newPassword; // hashed by cast
        $user->must_change_password = false;
        $user->save();
    }

    public function updateMyPhoto(User $user, UploadedFile $photo): void
    {
        $this->savePhoto($user, $photo);
    }

    /** @return array<int,array> */
    public function securityLog(User $caller, string $filter, ?string $q): array
    {
        $this->assertAdmin($caller);

        // Real data: recent oauth token issuances as login events. Other filter
        // categories ("changes", "denied") would back onto a dedicated audit
        // table (planned `security_events`) — return [] until then.
        if ($filter === 'changes' || $filter === 'denied') {
            return [];
        }

        if (! Schema::hasTable('oauth_access_tokens')) {
            return [];
        }

        $rows = DB::table('oauth_access_tokens as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('u.full_name', 'like', "%{$q}%")->orWhere('u.email', 'like', "%{$q}%");
            }))
            ->orderByDesc('t.created_at')->limit(50)
            ->get(['t.id', 't.created_at', 'u.full_name', 'u.email']);

        return $rows->map(fn ($r) => [
            'id' => 'evt-'.$r->id,
            'kind' => 'success',
            'iconKey' => 'login',
            'title' => 'Admin Login Successful',
            'userLabel' => $r->full_name ?? $r->email ?? 'unknown',
            'timeLabel' => $r->created_at ? Carbon::parse($r->created_at)->diffForHumans(['short' => true]) : '—',
            'ipAddress' => '—',
            'deviceLabel' => 'API client',
        ])->values()->all();
    }

    // --- helpers -------------------------------------------------------------

    private function assertAdmin(User $user): void
    {
        if (! $user->isSystemAdmin()) {
            throw ApiException::forbidden('Only the System Admin may manage users.');
        }
    }

    private function listRow(User $u): array
    {
        $role = $u->getCurrentRole();
        $sector = $role && $role->target_entity === 'Sector' ? Sector::find($role->entity_id) : null;
        $sectorWire = $this->sectorNameToWire($sector?->sector_name);

        return [
            'id' => (string) $u->id,
            'name' => $u->full_name,
            'email' => $u->email,
            'role' => WireEnums::roleToWire(optional($role)->role) ?? 'governor',
            'sector' => $sectorWire,
            'roleLabel' => optional($role)->role ?? 'No Role',
            'sectorLabel' => $sector?->sector_name ?? 'Cross-Sector',
            'initials' => Presenter::initials($u->full_name),
            'accent' => SectorPresenter::accent($u->id),
        ];
    }

    private function sectorNameToWire(?string $name): string
    {
        if (! $name) {
            return 'any';
        }
        $n = strtolower($name);

        return match (true) {
            str_contains($n, 'health') => 'health',
            str_contains($n, 'educat') || str_contains($n, 'school') => 'education',
            str_contains($n, 'agric') => 'agriculture',
            str_contains($n, 'infra') || str_contains($n, 'road') || str_contains($n, 'works') => 'infrastructure',
            default => 'any',
        };
    }

    /** Sectors whose name maps to the wire token (e.g. 'health' → all health sectors). */
    private function sectorIdsMatchingWire(string $wire): array
    {
        if ($wire === 'any' || $wire === 'all') {
            return [];
        }
        $pattern = match ($wire) {
            'health' => '%health%',
            'education' => '%educat%',
            'agriculture' => '%agric%',
            'infrastructure' => '%infra%',
            default => '%%',
        };

        return Sector::where('sector_name', 'like', $pattern)->pluck('id')->all();
    }

    private function savePhoto(User $user, UploadedFile $photo): void
    {
        $ext = $photo->getClientOriginalExtension() ?: 'jpg';
        $filename = $user->id.'_'.time().'.'.$ext;
        Storage::disk('public')->putFileAs('uploads/users', $photo, $filename);
        $user->image_url = $filename;
        $user->save();
    }
}
