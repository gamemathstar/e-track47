<?php

namespace App\Http\Controllers\Api\V2;

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
        return $this->gallery->detail($id);
    }

    public function upload(UploadGalleryRequest $request)
    {
        $this->gallery->upload($request->user(), $request->validated(), $request->file('asset'));

        return $this->accepted();
    }
}
