<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Kpi\AddTrackingEntryRequest;
use App\Http\Requests\V2\Kpi\SetMilestoneRequest;
use App\Http\Requests\V2\Kpi\SubmitPerformanceRequest;
use App\Http\Resources\V2\KpiResource;
use App\Services\V2\KpiTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * KPI tracking (API_REFERENCE.md §11.4): two reads + three queued command
 * endpoints. Commands return 202 with no body (the client treats any 2xx as
 * success and ignores the body).
 */
class KpiController extends BaseController
{
    public function __construct(private readonly KpiTrackingService $kpis)
    {
    }

    /** GET /deliverables/{id}/kpis */
    public function index(Request $request, string $id): AnonymousResourceCollection
    {
        return KpiResource::collection($this->kpis->listForDeliverable($request->user(), $id));
    }

    /** GET /kpis/{id} */
    public function show(Request $request, string $id): KpiResource
    {
        return new KpiResource($this->kpis->getKpi($request->user(), $id));
    }

    /** POST /kpis/{id}/submissions */
    public function submit(SubmitPerformanceRequest $request, string $id)
    {
        $this->kpis->submitPerformance($request->user(), $id, $request->validated());

        return $this->accepted();
    }

    /** GET /kpis/{id}/tracking-context */
    public function trackingContext(Request $request, string $id): array
    {
        return $this->kpis->getTrackingContext($request->user(), $id);
    }

    /** GET /kpis/{id}/milestones?quarter=q1..q4&year={int} */
    public function getMilestone(Request $request, string $id): array
    {
        $validated = $request->validate([
            'quarter' => ['required', 'in:q1,q2,q3,q4'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        return $this->kpis->getMilestone(
            $request->user(),
            $id,
            $validated['quarter'],
            (int) $validated['year'],
        );
    }

    /** POST /kpis/{id}/milestones */
    public function setMilestone(SetMilestoneRequest $request, string $id)
    {
        $this->kpis->setMilestone($request->user(), $id, $request->validated());

        return $this->accepted();
    }

    /** POST /kpis/{id}/tracking-entries */
    public function addTracking(AddTrackingEntryRequest $request, string $id)
    {
        $this->kpis->addTrackingEntry($request->user(), $id, $request->validated());

        return $this->accepted();
    }
}
