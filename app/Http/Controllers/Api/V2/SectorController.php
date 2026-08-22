<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\CommitmentResource;
use App\Http\Resources\V2\SectorResource;
use App\Services\V2\HierarchyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Sectors read hierarchy (API_REFERENCE.md §11.3.1–§11.3.3).
 */
class SectorController extends BaseController
{
    public function __construct(private readonly HierarchyService $hierarchy)
    {
    }

    /**
     * GET /sectors[?frameworkId=N]
     *
     * Defaults to the currently Active framework — sector composition differs
     * between frameworks, so the list MUST be framework-scoped. Pass an explicit
     * frameworkId to view a different cycle (e.g. for historical comparison).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'frameworkId' => ['nullable', 'integer'],
        ]);

        return SectorResource::collection(
            $this->hierarchy->listSectors($request->user(), $validated['frameworkId'] ?? null),
        );
    }

    /** GET /sectors/{id} */
    public function show(Request $request, string $id): SectorResource
    {
        return new SectorResource($this->hierarchy->getSector($request->user(), $id));
    }

    /** GET /sectors/{id}/commitments */
    public function commitments(Request $request, string $id): AnonymousResourceCollection
    {
        return CommitmentResource::collection($this->hierarchy->listCommitments($request->user(), $id));
    }
}
