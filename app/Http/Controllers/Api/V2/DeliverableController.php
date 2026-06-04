<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\AnnualTargets\SaveAnnualTargetsRequest;
use App\Http\Resources\V2\DeliverableResource;
use App\Services\V2\AnnualTargetsService;
use App\Services\V2\HierarchyService;
use Illuminate\Http\Request;

/**
 * Deliverable read endpoints (API_REFERENCE.md §11.3.6) + annual targets
 * (§11.4.6).
 */
class DeliverableController extends BaseController
{
    public function __construct(
        private readonly HierarchyService $hierarchy,
        private readonly AnnualTargetsService $annualTargets,
    ) {
    }

    /** GET /deliverables/{id} */
    public function show(Request $request, string $id): DeliverableResource
    {
        return new DeliverableResource($this->hierarchy->getDeliverable($request->user(), $id));
    }

    /** GET /deliverables/{id}/annual-targets?year={year} */
    public function annualTargets(Request $request, string $id): array
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        return $this->annualTargets->index($request->user(), $id, (int) $validated['year']);
    }

    /** POST /deliverables/{id}/annual-targets */
    public function saveAnnualTargets(SaveAnnualTargetsRequest $request, string $id)
    {
        $data = $request->validated();
        $this->annualTargets->save($request->user(), $id, (int) $data['year'], $data['targets']);

        return $this->accepted();
    }
}
