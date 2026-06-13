<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Gallery;
use App\Models\GalleryComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Gallery (API_REFERENCE.md §11.13). Admin management list + public list +
 * detail + multipart upload.
 */
class GalleryService
{
    private const CATEGORY_LABELS = [
        'infrastructure' => 'Infrastructure',
        'education' => 'Education',
        'health' => 'Health',
        'agriculture' => 'Agriculture',
    ];

    private const PUBLIC_FILTER_TO_CATEGORY = [
        'roads' => 'infrastructure',
        'healthcare' => 'health',
        'education' => 'education',
    ];

    public function managementList(User $user, string $tab): array
    {
        $this->assertAdmin($user);

        $query = Gallery::query();
        match ($tab) {
            'recent' => $query->orderByDesc('created_at'),
            'archived' => $query->where('status', 'inactive')->orderByDesc('updated_at'),
            default => $query->orderBy('display_order')->orderByDesc('created_at'),
        };

        return $query->get()->map(fn (Gallery $g) => $this->item($g))->all();
    }

    public function publicList(string $filter): array
    {
        $query = Gallery::where('status', 'active')
            ->when($this->hasIsPublicColumn(), fn ($q) => $q->where('is_public', true))
            ->orderBy('display_order')->orderByDesc('created_at');

        if ($filter !== 'all' && isset(self::PUBLIC_FILTER_TO_CATEGORY[$filter]) && $this->hasCategoryColumn()) {
            $query->where('category', self::PUBLIC_FILTER_TO_CATEGORY[$filter]);
        }

        return $query->get()->map(fn (Gallery $g) => $this->item($g))->all();
    }

    /**
     * Item detail. Anonymous (or non-admin) callers may only fetch items that
     * are both published (`is_public = true`) and active. Non-matching ids
     * return 404 rather than 403 so the existence of private items isn't
     * leaked. System Admins always see any item (so the management list's
     * detail-link works for archived / unpublished entries too).
     */
    public function detail(string $id, ?User $user = null): array
    {
        $g = Gallery::find($id);
        if (! $g) {
            throw ApiException::notFound('Gallery item not found.');
        }

        $isAdmin = $user && $user->isSystemAdmin();
        if (! $isAdmin) {
            $isActive = (string) ($g->status ?? '') === 'active';
            $isPublic = ! $this->hasIsPublicColumn() || (bool) ($g->is_public ?? false);
            if (! $isActive || ! $isPublic) {
                throw ApiException::notFound('Gallery item not found.');
            }
        }

        $comments = GalleryComment::where('gallery_id', $g->id)->orderByDesc('created_at')->limit(10)->get();

        return [
            'id' => (string) $g->id,
            'title' => $g->title ?: 'Untitled',
            'dateLabel' => optional($g->created_at)->format('M j, Y') ?? '—',
            'descriptionBlocks' => array_values(array_filter(explode("\n", (string) $g->caption))) ?: ['—'],
            'isVerified' => true,
            'verifiedPillLabel' => 'Verified Project',
            'heroImageUrl' => $this->imageUrlFor($g),
            'heroIconKey' => $this->iconForCategory($g->category ?? 'infrastructure'),
            'heroGradientKeys' => $this->gradientFor($g),
            'stats' => $this->statsFor($g),
            'comments' => $comments->map(fn (GalleryComment $c) => [
                'id' => (string) $c->id,
                'authorName' => $c->commenter_name,
                'authorInitials' => $this->initials($c->commenter_name),
                'timeLabel' => $c->created_at ? Carbon::parse($c->created_at)->diffForHumans(['short' => true]) : '—',
                'body' => $c->comment ?: '',
            ])->values()->all(),
        ];
    }

    public function upload(User $user, array $form, ?UploadedFile $asset): void
    {
        $this->assertAdmin($user);

        $g = new Gallery();
        $g->title = $form['title'];
        $g->caption = $form['description'];
        $g->display_order = (int) ($form['displayOrder'] ?? 0);
        $g->status = 'active';
        if ($this->hasCategoryColumn()) {
            $g->category = $form['category'];
        }
        if ($this->hasIsPublicColumn()) {
            $g->is_public = filter_var($form['isPublic'] ?? true, FILTER_VALIDATE_BOOLEAN);
        }

        $path = '';
        if ($asset) {
            $ext = $asset->getClientOriginalExtension() ?: 'jpg';
            $filename = uniqid('g_').'.'.$ext;
            Storage::disk('public')->putFileAs('uploads/galleries', $asset, $filename);
            $path = 'uploads/galleries/'.$filename;
        }
        $g->image_path = $path;
        $g->save();
    }

    // --- helpers -------------------------------------------------------------

    private function assertAdmin(User $user): void
    {
        if (! $user->isSystemAdmin()) {
            throw ApiException::forbidden('Only the System Admin may manage the gallery.');
        }
    }

    private function hasCategoryColumn(): bool
    {
        return Schema::hasColumn('galleries', 'category');
    }

    private function hasIsPublicColumn(): bool
    {
        return Schema::hasColumn('galleries', 'is_public');
    }

    private function item(Gallery $g): array
    {
        $cat = $g->category ?? 'infrastructure';
        $gradient = is_array($g->gradient_keys ?? null) ? $g->gradient_keys : (is_string($g->gradient_keys ?? null) ? (json_decode($g->gradient_keys, true) ?: []) : []);
        if (empty($gradient)) {
            $gradient = ['primary', 'tertiary'];
        }

        return [
            'id' => (string) $g->id,
            'title' => $g->title ?: 'Untitled',
            'category' => $cat,
            'categoryLabel' => self::CATEGORY_LABELS[$cat] ?? ucfirst($cat),
            'imageUrl' => $this->imageUrlFor($g),
            'iconKey' => $g->icon_key ?? $this->iconForCategory($cat),
            'gradientKeys' => $gradient,
            'isActive' => $g->status === 'active',
            'isPublic' => Schema::hasColumn('galleries', 'is_public') ? (bool) ($g->is_public ?? true) : true,
            'displayOrder' => (int) $g->display_order,
        ];
    }

    /**
     * Absolute URL for the uploaded gallery asset, or null when no file was
     * attached. Two upload paths feed this column:
     *
     *  - Web admin (app/Http/Controllers/GalleryController.php) writes files
     *    DIRECTLY into public/uploads/gallery/ via `$image->move(public_path(...))`
     *    and stores image_path = "uploads/gallery/<file>". The right URL is
     *    asset($path) → {APP_URL}/uploads/gallery/<file>.
     *
     *  - v2 admin (this service's upload()) writes via Storage::disk('public'),
     *    landing in storage/app/public/uploads/galleries/ and storing
     *    image_path = "uploads/galleries/<file>". The right URL is
     *    Storage::disk('public')->url($path) → {APP_URL}/storage/uploads/galleries/<file>
     *    (requires `php artisan storage:link` on the server).
     *
     * We distinguish by the path prefix the upload code wrote.
     */
    private function imageUrlFor(Gallery $g): ?string
    {
        $path = $g->image_path ?? null;
        if (! $path) {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'uploads/galleries/')) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }

    private function iconForCategory(string $category): string
    {
        return match ($category) {
            'health' => 'local_hospital',
            'education' => 'school',
            'agriculture' => 'agriculture',
            'infrastructure' => 'construction',
            default => 'image',
        };
    }

    private function gradientFor(Gallery $g): array
    {
        $g_keys = $g->gradient_keys ?? null;
        if (is_array($g_keys) && $g_keys) {
            return $g_keys;
        }
        if (is_string($g_keys) && $g_keys) {
            $decoded = json_decode($g_keys, true);
            if (is_array($decoded) && $decoded) {
                return $decoded;
            }
        }

        return ['primary', 'tertiary'];
    }

    private function statsFor(Gallery $g): array
    {
        return [
            ['iconKey' => 'speed', 'accent' => 'primary', 'label' => 'COMPLETION', 'value' => '100%'],
            ['iconKey' => 'verified', 'accent' => 'tertiary', 'label' => 'STATUS', 'value' => $g->status === 'active' ? 'Live' : 'Archived'],
        ];
    }

    private function initials(?string $name): string
    {
        if (! $name) {
            return '';
        }
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return implode('', array_map(fn ($p) => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2)));
    }
}
