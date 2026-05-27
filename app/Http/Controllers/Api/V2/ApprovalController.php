<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Approvals\BulkApproveRequest;
use App\Http\Requests\V2\Approvals\ReviewSubmissionRequest;
use App\Services\V2\ApprovalService;
use Illuminate\Http\Request;

/**
 * Approvals workflow (API_REFERENCE.md §11.6). Queue/detail reads return raw
 * computed arrays; the two mutations advance the §4 lifecycle and return 2xx.
 */
class ApprovalController extends BaseController
{
    public function __construct(private readonly ApprovalService $approvals)
    {
    }

    /** GET /approvals/coordinator/queue */
    public function coordinatorQueue(Request $request): array
    {
        return $this->approvals->coordinatorQueue($request->user());
    }

    /** GET /approvals/sector-head/queue */
    public function sectorHeadQueue(Request $request): array
    {
        return $this->approvals->sectorHeadQueue($request->user(), $request->query('quarter'));
    }

    /** GET /approvals/sector-head/bulk */
    public function sectorHeadBulk(Request $request): array
    {
        $validated = $request->validate(['grouping' => ['required', 'in:by_commitment,by_deliverable']]);

        return $this->approvals->sectorHeadBulk($request->user(), $validated['grouping']);
    }

    /** GET /approvals/facilitator/queue */
    public function facilitatorQueue(Request $request): array
    {
        $validated = $request->validate(['grouping' => ['required', 'in:by_sector,by_kpi']]);

        return $this->approvals->facilitatorQueue($request->user(), $validated['grouping']);
    }

    /** GET /approvals/data-admin/my-kpis */
    public function myKpis(Request $request): array
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'in:all,pending_entry,pending_sh,confirmed'],
            'quarter' => ['nullable', 'in:q1,q2,q3,q4'],
            'year' => ['nullable', 'integer'],
        ]);

        return $this->approvals->dataAdminMyKpis(
            $request->user(),
            $validated['filter'] ?? 'all',
            $validated['quarter'] ?? null,
            isset($validated['year']) ? (int) $validated['year'] : null,
        );
    }

    /** GET /approvals/submissions/{kpiId} */
    public function submissionDetail(Request $request, string $kpiId): array
    {
        return $this->approvals->submissionDetail($request->user(), $kpiId);
    }

    /** POST /approvals/submissions/{submissionId}/review */
    public function review(ReviewSubmissionRequest $request, string $submissionId)
    {
        $this->approvals->review($request->user(), $submissionId, $request->validated());

        return $this->accepted();
    }

    /** POST /approvals/submissions/bulk-approve */
    public function bulkApprove(BulkApproveRequest $request)
    {
        $data = $request->validated();
        $this->approvals->bulkApprove($request->user(), $data['submissionIds'], $data['role']);

        return $this->accepted();
    }
}
