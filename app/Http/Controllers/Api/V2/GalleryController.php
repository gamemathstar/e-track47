<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Gallery\SubmitGalleryCommentRequest;
use App\Http\Requests\V2\Gallery\UploadGalleryRequest;
use App\Services\V2\GalleryService;
use Illuminate\Http\Request;

/**
 * Gallery (API_REFERENCE.md §11.13). Admin management list + public list +
 * detail + multipart upload.
 */
class GalleryController extends BaseController
{
    public function __construct(private readonly GalleryService $gallery)
    {
    }

    public function management(Request $request): array
    {
        $validated = $request->validate(['tab' => ['required', 'in:all,recent,archived']]);

        return $this->gallery->managementList($request->user(), $validated['tab']);
    }

    public function publicList(Request $request): array
    {
        $validated = $request->validate(['filter' => ['required', 'in:all,roads,healthcare,education']]);

        return $this->gallery->publicList($validated['filter']);
    }

    public function show(Request $request, string $id): array
    {
        // $request->user() is null for anonymous callers (auth.optional
        // middleware on this route — see routes/api_v2.php §11.13). The
        // service uses that to gate non-public items to System Admin only.
        return $this->gallery->detail($id, $request->user());
    }

    public function upload(UploadGalleryRequest $request)
    {
        $this->gallery->upload($request->user(), $request->validated(), $request->file('asset'));

        return $this->accepted();
    }

    /**
     * Unauthenticated public comment submission. Body: { authorName, body }.
     * Stored as `status = pending` (moderation queue). Response is `204` —
     * we never echo the created row.
     */
    public function submitComment(SubmitGalleryCommentRequest $request, string $id)
    {
        $this->gallery->submitComment($id, $request->validated());

        return $this->noContent();
    }
}
