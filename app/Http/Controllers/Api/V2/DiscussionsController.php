<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Discussions\PostCommentRequest;
use App\Services\V2\DiscussionsService;
use Illuminate\Http\Request;

/**
 * Discussions (API_REFERENCE.md §11.15): hub + sector feeds + thread detail +
 * post comment/reply + toggle-like.
 */
class DiscussionsController extends BaseController
{
    public function __construct(private readonly DiscussionsService $discussions)
    {
    }

    public function hub(Request $request): array
    {
        $validated = $request->validate(['filter' => ['required', 'in:all,priority,recent']]);

        return $this->discussions->hub($validated['filter']);
    }

    public function sectorThreads(Request $request, string $sectorId): array
    {
        $validated = $request->validate(['tab' => ['required', 'in:commitments,stakeholders']]);

        return $this->discussions->sectorThreads($sectorId, $validated['tab']);
    }

    public function threadDetail(Request $request, string $threadId): array
    {
        return $this->discussions->threadDetail($request->user(), $threadId);
    }

    public function postComment(PostCommentRequest $request, string $threadId)
    {
        $data = $request->validated();
        $this->discussions->postComment($request->user(), $threadId, $data['body'], $data['parentId'] ?? null);

        return $this->accepted();
    }

    public function toggleLike(Request $request, string $commentId)
    {
        $this->discussions->toggleLike($request->user(), $commentId);

        return $this->accepted();
    }
}
