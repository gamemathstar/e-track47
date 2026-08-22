<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\CommitmentResource;
use App\Http\Resources\V2\DeliverableResource;
use App\Services\V2\HierarchyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Commitment read endpoints (API_REFERENCE.md §11.3.4–§11.3.5).
 */
class CommitmentController extends BaseController
{
    public function __construct(private readonly HierarchyService $hierarchy)
    {
    }

    /** GET /commitments/{id} */
    public function show(Request $request, string $id): CommitmentResource
    {
        return new CommitmentResource($this->hierarchy->getCommitment($request->user(), $id));
    }

    /** GET /commitments/{id}/deliverables */
    public function deliverables(Request $request, string $id): AnonymousResourceCollection
    {
        return DeliverableResource::collection($this->hierarchy->listDeliverables($request->user(), $id));
    }
}
