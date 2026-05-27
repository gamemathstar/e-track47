<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\DeliverableResource;
use App\Services\V2\HierarchyService;
use Illuminate\Http\Request;

/**
 * Deliverable read endpoint (API_REFERENCE.md §11.3.6).
 */
class DeliverableController extends BaseController
{
    public function __construct(private readonly HierarchyService $hierarchy)
    {
    }

    /** GET /deliverables/{id} */
    public function show(Request $request, string $id): DeliverableResource
    {
        return new DeliverableResource($this->hierarchy->getDeliverable($request->user(), $id));
    }
}
