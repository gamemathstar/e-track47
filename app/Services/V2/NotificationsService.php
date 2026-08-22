<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications inbox + per-user preferences (API_REFERENCE.md §11.14).
 * Every endpoint is user-scoped — a user only sees/mutates their own rows.
 */
class NotificationsService
{
    /** Wire allowed values for category mapping. */
    private const KNOWN_KINDS = ['submission', 'approval', 'rejection', 'discussion', 'deadline', 'mention', 'system'];

    public function inbox(User $user, string $tab): array
    {
        $query = Notification::where('user_id', $user->id);

        if ($tab === 'unread') {
            $query->where('status', 'Not Read');
        } elseif ($tab === 'mentions') {
            if (Schema::hasColumn('notifications', 'kind')) {
                $query->where('kind', 'mention');
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $rows = $query->orderByDesc('created_at')->limit(200)->get();

        // Group by date-bucket (Today / Yesterday / Earlier).
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $sections = ['today' => [], 'yesterday' => [], 'earlier' => []];

        foreach ($rows as $n) {
            $created = $n->created_at ? Carbon::parse($n->created_at) : Carbon::now();
            $key = $created->isSameDay($today) ? 'today'
                : ($created->isSameDay($yesterday) ? 'yesterday' : 'earlier');
            $sections[$key][] = $this->item($n);
        }

        $out = [];
        foreach (['today' => 'Today', 'yesterday' => 'Yesterday', 'earlier' => 'Earlier'] as $id => $label) {
            $out[] = ['id' => $id, 'label' => $label, 'notifications' => $sections[$id]];
        }

        return ['sections' => $out];
    }

    public function getPreferences(User $user): array
    {
        return $this->serializePreferences($this->loadOrDefault($user));
    }

    public function updatePreferences(User $user, array $data): void
    {
        $prefs = $this->loadOrDefault($user);
        $prefs->fill([
            'submissions' => (bool) $data['submissions'],
            'approvals' => (bool) $data['approvals'],
            'rejections' => (bool) $data['rejections'],
            'mentions' => (bool) $data['mentions'],
            'deadlines' => (bool) $data['deadlines'],
            'push' => (bool) $data['push'],
            'email' => (bool) $data['email'],
            'sms' => (bool) $data['sms'],
            'quiet_hours_enabled' => (bool) $data['quietHoursEnabled'],
            'quiet_from_hour' => (int) $data['quietFrom']['hour'],
            'quiet_from_minute' => (int) $data['quietFrom']['minute'],
            'quiet_to_hour' => (int) $data['quietTo']['hour'],
            'quiet_to_minute' => (int) $data['quietTo']['minute'],
        ]);
        $prefs->save();
    }

    public function markAllRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('status', 'Not Read')
            ->update(['status' => 'Read']);
    }

    public function markRead(User $user, string $id): void
    {
        $this->markReadAndReturn($user, $id);
    }

    /**
     * Same as markRead() but returns the (now-Read) Notification model so
     * the caller can inspect attached metadata (e.g. `deep_link_route`,
     * `deep_link_params`) without a second DB roundtrip. Used by the web
     * tap-through handler.
     */
    public function markReadAndReturn(User $user, string $id): Notification
    {
        $n = Notification::find($id);
        if (! $n) {
            throw ApiException::notFound('Notification not found.');
        }
        if ((int) $n->user_id !== (int) $user->id) {
            throw ApiException::notFound('Notification not found.');
        }

        $n->status = 'Read';
        $n->save();

        return $n;
    }

    /**
     * Upsert a device token for the user. Multiple devices per user are
     * fine — uniqueness is on the token itself, so the same handset re-
     * registering simply touches `last_seen_at` (and ownership moves to the
     * latest user if the handset was re-paired).
     */
    public function registerDeviceToken(User $user, string $token, string $platform = 'android', ?string $appVersion = null): void
    {
        DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'app_version' => $appVersion,
                'last_seen_at' => Carbon::now(),
            ],
        );
    }

    /**
     * Remove a device token. Idempotent — silent no-op if the token isn't
     * registered (or belongs to a different user). Used on logout.
     */
    public function unregisterDeviceToken(User $user, string $token): void
    {
        DeviceToken::where('token', $token)
            ->where('user_id', $user->id)
            ->delete();
    }

    // --- helpers -------------------------------------------------------------

    private function loadOrDefault(User $user): NotificationPreference
    {
        // firstOrCreate doesn't pull DB defaults into the new instance; refresh
        // to materialize them so the serialized payload reflects real defaults.
        return NotificationPreference::firstOrCreate(['user_id' => $user->id])->refresh();
    }

    private function serializePreferences(NotificationPreference $p): array
    {
        return [
            'submissions' => (bool) $p->submissions,
            'approvals' => (bool) $p->approvals,
            'rejections' => (bool) $p->rejections,
            'mentions' => (bool) $p->mentions,
            'deadlines' => (bool) $p->deadlines,
            'push' => (bool) $p->push,
            'email' => (bool) $p->email,
            'sms' => (bool) $p->sms,
            'quietHoursEnabled' => (bool) $p->quiet_hours_enabled,
            'quietFrom' => ['hour' => (int) $p->quiet_from_hour, 'minute' => (int) $p->quiet_from_minute],
            'quietTo' => ['hour' => (int) $p->quiet_to_hour, 'minute' => (int) $p->quiet_to_minute],
        ];
    }

    private function item(Notification $n): array
    {
        $kind = $this->kindOf($n);
        $deepRoute = Schema::hasColumn('notifications', 'deep_link_route') ? $n->deep_link_route : null;
        $deepParams = Schema::hasColumn('notifications', 'deep_link_params')
            ? (is_array($n->deep_link_params ?? null) ? $n->deep_link_params : (is_string($n->deep_link_params ?? null) ? json_decode($n->deep_link_params, true) : null))
            : null;

        $out = [
            'id' => (string) $n->id,
            'kind' => $kind,
            'iconKey' => $this->iconForKind($kind),
            'accent' => $this->accentForKind($kind),
            'title' => $n->title ?? '',
            'timeAgoLabel' => $n->created_at ? Carbon::parse($n->created_at)->diffForHumans(['short' => true]) : '—',
            'contextLabel' => '',
            'body' => $n->body ?? '',
            'isUnread' => $n->status === 'Not Read',
        ];
        if ($deepRoute) {
            $out['deepLinkRoute'] = $deepRoute;
        }
        if (is_array($deepParams)) {
            $out['deepLinkParams'] = $deepParams;
        }

        return $out;
    }

    private function kindOf(Notification $n): string
    {
        $raw = strtolower(trim((string) ($n->kind ?? $n->type ?? '')));
        if (in_array($raw, self::KNOWN_KINDS, true)) {
            return $raw;
        }
        // The legacy `type` column carries free-form strings; map by keyword.
        return match (true) {
            str_contains($raw, 'approv') => 'approval',
            str_contains($raw, 'reject') => 'rejection',
            str_contains($raw, 'submit') => 'submission',
            str_contains($raw, 'comment') || str_contains($raw, 'discuss') => 'discussion',
            str_contains($raw, 'deadline') || str_contains($raw, 'due') => 'deadline',
            str_contains($raw, 'mention') => 'mention',
            default => 'system',
        };
    }

    private function iconForKind(string $kind): string
    {
        return match ($kind) {
            'approval' => 'check_circle',
            'rejection' => 'cancel',
            'submission' => 'upload',
            'discussion' => 'forum',
            'deadline' => 'schedule',
            'mention' => 'alternate_email',
            default => 'notifications',
        };
    }

    private function accentForKind(string $kind): string
    {
        return match ($kind) {
            'approval', 'submission' => 'primary',
            'rejection' => 'error',
            'discussion', 'mention' => 'secondary',
            'deadline' => 'tertiary',
            default => 'primary',
        };
    }
}
