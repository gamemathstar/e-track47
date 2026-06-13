<?php

namespace App\View\Composers;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Hydrates the topbar notification bell on every authenticated page render.
 * Bound to the topbar partial so individual controllers don't need to pass
 * the unread count + recent items in their `view()` calls.
 *
 * Cheap: one COUNT and one limited SELECT per page render, both keyed on
 * (user_id, status). For typical admin volumes (≤ a few thousand rows per
 * user) this is well under 1ms — no need for a cache layer yet.
 */
class TopbarNotificationsComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        if (! $user) {
            $view->with('topbarNotifications', $this->emptyState());
            return;
        }

        $unreadCount = (int) Notification::where('user_id', $user->id)
            ->where('status', 'Not Read')
            ->count();

        $recent = Notification::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'title', 'body', 'type', 'kind', 'status', 'created_at']);

        $view->with('topbarNotifications', [
            'unreadCount' => $unreadCount,
            'unreadLabel' => $unreadCount > 99 ? '99+' : (string) $unreadCount,
            'recent' => $recent->map(fn (Notification $n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->title ?? ''),
                'body' => (string) ($n->body ?? ''),
                'kind' => $this->normalizeKind($n),
                'iconKey' => $this->iconForKind($this->normalizeKind($n)),
                'timeAgoLabel' => $n->created_at
                    ? Carbon::parse($n->created_at)->diffForHumans(['short' => true])
                    : '—',
                'isUnread' => $n->status === 'Not Read',
                'followUrl' => route('notifications.follow', ['id' => $n->id]),
            ])->all(),
            'indexUrl' => route('notifications.index'),
            'markAllReadUrl' => route('notifications.mark-all-read'),
        ]);
    }

    private function emptyState(): array
    {
        return [
            'unreadCount' => 0,
            'unreadLabel' => '0',
            'recent' => [],
            'indexUrl' => '',
            'markAllReadUrl' => '',
        ];
    }

    /**
     * Reuses the same kind-mapping logic as NotificationsService::kindOf,
     * lightly inlined. Reads from the v2 `kind` column when present, falls
     * back to keyword-scanning the legacy `type` column when not.
     */
    private function normalizeKind(Notification $n): string
    {
        $raw = strtolower(trim((string) ($n->kind ?? $n->type ?? '')));
        $known = ['submission', 'approval', 'rejection', 'discussion', 'deadline', 'mention', 'system'];
        if (in_array($raw, $known, true)) {
            return $raw;
        }
        return match (true) {
            str_contains($raw, 'approv') => 'approval',
            str_contains($raw, 'reject') => 'rejection',
            str_contains($raw, 'submit') => 'submission',
            str_contains($raw, 'comment'), str_contains($raw, 'discuss') => 'discussion',
            str_contains($raw, 'deadline'), str_contains($raw, 'due') => 'deadline',
            str_contains($raw, 'mention') => 'mention',
            default => 'system',
        };
    }

    /**
     * Lucide icon name per kind. Matches the icon names already used in
     * topbar.blade.php (lucide). Falls back to `bell` for unknown kinds.
     */
    private function iconForKind(string $kind): string
    {
        return match ($kind) {
            'approval' => 'check-circle',
            'rejection' => 'x-circle',
            'submission' => 'upload-cloud',
            'discussion' => 'message-square',
            'deadline' => 'clock',
            'mention' => 'at-sign',
            default => 'bell',
        };
    }
}
