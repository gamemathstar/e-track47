<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\DiscussionComment;
use App\Models\DiscussionCommentLike;
use App\Models\DiscussionThread;
use App\Models\Sector;
use App\Models\User;
use App\Support\V2\Presenters\Presenter;
use App\Support\V2\Presenters\SectorPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Discussions hub + sector thread feeds + thread detail + post + toggle-like
 * (API_REFERENCE.md §11.15). Net-new domain over three tables (threads,
 * comments, comment_likes). Hub trending is a deterministic config-style payload
 * for now — easy to swap for real "trending" analytics later.
 */
class DiscussionsService
{
    /** Wire enum values for thread status; default in_progress. */
    private const KNOWN_STATUSES = ['in_progress', 'resolved', 'blocked'];

    // --- 11.15.1 hub --------------------------------------------------------

    public function hub(string $filter): array
    {
        $sectors = Sector::orderBy('sector_name')->get();

        $sectorRows = $sectors->map(function (Sector $s) {
            $count = DiscussionThread::where('sector_id', $s->id)->count();

            return [
                'id' => 'sector-'.$s->id,
                'name' => $s->sector_name,
                'tagline' => $s->description ?: 'Discussions and announcements.',
                'accent' => SectorPresenter::accent($s->id),
                'iconKey' => SectorPresenter::icon($s),
                'countLabel' => $this->shortCount($count),
            ];
        });

        if ($filter === 'priority') {
            $sectorRows = $sectorRows->sortByDesc('countLabel')->values();
        } elseif ($filter === 'recent') {
            $sectorRows = $sectorRows->values();
        }

        return [
            'sectors' => $sectorRows->values()->all(),
            'trending' => [
                'hotTopicTag' => 'HOT TOPIC',
                'hotTopicTitle' => 'Quarterly Performance Review',
                'hotTopicBody' => 'Active debate on Q-end submissions.',
                'healthBody' => 'Coverage discussion gaining traction.',
                'educationBody' => 'Teacher training rollout feedback.',
            ],
        ];
    }

    // --- 11.15.2 sector thread feed -----------------------------------------

    public function sectorThreads(string $sectorId, string $tab): array
    {
        if (! Sector::whereKey($sectorId)->exists()) {
            throw ApiException::notFound('Sector not found.');
        }

        $threads = DiscussionThread::where('sector_id', $sectorId)
            ->orderByDesc('updated_at')->get();

        return $threads->map(function (DiscussionThread $t) {
            $count = $t->comments()->count();

            return [
                'threadId' => (string) $t->id,
                'title' => $t->title,
                'commentCountLabel' => (string) $count,
                'authorName' => $t->author_name ?? '—',
                'timeLabel' => $t->updated_at ? Carbon::parse($t->updated_at)->diffForHumans(['short' => true]) : '—',
                'previewBody' => $t->preview_body ?? '',
            ];
        })->values()->all();
    }

    // --- 11.15.3 thread detail ----------------------------------------------

    public function threadDetail(User $user, string $threadId): array
    {
        $thread = DiscussionThread::find($threadId);
        if (! $thread) {
            throw ApiException::notFound('Thread not found.');
        }

        $likedIds = DiscussionCommentLike::where('user_id', $user->id)
            ->whereIn('comment_id', $thread->comments()->pluck('id'))
            ->pluck('comment_id')->all();
        $likedSet = array_flip($likedIds);

        $status = in_array($thread->status, self::KNOWN_STATUSES, true) ? $thread->status : 'in_progress';

        return [
            'id' => (string) $thread->id,
            'title' => $thread->title,
            'status' => $status,
            'statusLabel' => $thread->status_label ?? $this->defaultStatusLabel($status),
            'leadName' => $thread->lead_name ?? '—',
            'leadLabel' => $thread->lead_label ?? 'LEAD OFFICER',
            'leadInitials' => $thread->lead_initials ?: Presenter::initials($thread->lead_name),
            'comments' => $thread->comments()->orderBy('created_at')->get()->map(function (DiscussionComment $c) use ($likedSet) {
                $out = [
                    'id' => (string) $c->id,
                    'authorName' => $c->author_name,
                    'authorRole' => $c->author_role ?? 'Member',
                    'authorInitials' => $c->author_initials ?: Presenter::initials($c->author_name),
                    'timeLabel' => $c->created_at ? Carbon::parse($c->created_at)->diffForHumans(['short' => true]) : '—',
                    'body' => $c->body,
                    'likeCount' => (int) $c->like_count,
                    'isLikedByCurrentUser' => isset($likedSet[$c->id]),
                ];
                if ($c->parent_id) {
                    $out['parentId'] = (string) $c->parent_id;
                }

                return $out;
            })->values()->all(),
        ];
    }

    // --- 11.15.4 post comment / reply ---------------------------------------

    public function postComment(User $user, string $threadId, string $body, ?string $parentId): void
    {
        $thread = DiscussionThread::find($threadId);
        if (! $thread) {
            throw ApiException::notFound('Thread not found.');
        }

        if ($parentId !== null) {
            $parent = DiscussionComment::find($parentId);
            if (! $parent || (int) $parent->thread_id !== (int) $thread->id) {
                throw ApiException::unprocessable('Parent comment does not belong to this thread.', ['parentId' => 'Invalid parent.']);
            }
        }

        DiscussionComment::create([
            'thread_id' => $thread->id,
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'author_name' => $user->full_name ?? 'User',
            'author_role' => optional($user->getCurrentRole())->role ?? 'Member',
            'author_initials' => Presenter::initials($user->full_name ?? 'U'),
            'body' => $body,
        ]);

        $thread->touch();
    }

    // --- 11.15.5 toggle like ------------------------------------------------

    public function toggleLike(User $user, string $commentId): void
    {
        $comment = DiscussionComment::find($commentId);
        if (! $comment) {
            throw ApiException::notFound('Comment not found.');
        }

        DB::transaction(function () use ($user, $comment) {
            $existing = DiscussionCommentLike::where('comment_id', $comment->id)
                ->where('user_id', $user->id)->first();

            if ($existing) {
                $existing->delete();
                DiscussionComment::where('id', $comment->id)->decrement('like_count');
            } else {
                DiscussionCommentLike::create(['comment_id' => $comment->id, 'user_id' => $user->id]);
                DiscussionComment::where('id', $comment->id)->increment('like_count');
            }
        });
    }

    // --- helpers ------------------------------------------------------------

    private function defaultStatusLabel(string $status): string
    {
        return match ($status) {
            'resolved' => 'Resolved',
            'blocked' => 'Blocked',
            default => 'In Progress',
        };
    }

    private function shortCount(int $n): string
    {
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.').'k';
        }

        return (string) $n;
    }
}
