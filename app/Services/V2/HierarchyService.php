<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Framework;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Read-only sector → commitment → deliverable hierarchy (API_REFERENCE.md §11.3).
 *
 * Enforces the same role-based access as the web app (reusing the User helpers)
 * and scopes lists to the active framework. Attaches derived metrics (computed by
 * HierarchyMetrics) onto the models as transient attributes for the Resources to
 * read. Pure reads — no model is mutated/persisted.
 */
class HierarchyService
{
    public function __construct(
        private readonly HierarchyMetrics $metrics,
        private readonly SectorAccessService $access,
    ) {
    }

    public function activeFrameworkId(): ?int
    {
        return optional(Framework::where('status', 'Active')->first())->id;
    }

    /**
     * Sectors the user may see, optionally constrained to a specific framework.
     * Pass $frameworkId to scope the list; null means "every framework".
     */
    private function accessibleSectorQuery(User $user, ?int $frameworkId = null)
    {
        return $this->access->accessibleSectorQuery($user, $frameworkId);
    }

    /**
     * @param  int|null  $frameworkId  caller-specified framework; falls back to
     *                                 the currently Active framework when null.
     *                                 Frameworks differ in their sector composition,
     *                                 so the list MUST be framework-scoped.
     * @return Collection<int,Sector>
     */
    public function listSectors(User $user, ?int $frameworkId = null): Collection
    {
        $frameworkId ??= $this->activeFrameworkId();

        $sectors = $this->accessibleSectorQuery($user, $frameworkId)
            ->orderBy('sector_name')->get();

        $metrics = $this->metrics->forSectors($sectors->pluck('id')->all());
        $sectors->each(fn (Sector $s) => $this->attachSectorMetrics($s, $metrics[$s->id] ?? null, false));

        return $sectors;
    }

    public function getSector(User $user, string $id): Sector
    {
        // Detail/drilldown deliberately don't constrain by framework — a caller
        // that already knows the sector id should be able to read it regardless
        // of which framework is currently Active.
        $sector = $this->accessibleSectorQuery($user)->find($id);

        if (! $sector) {
            throw ApiException::notFound('Sector not found.');
        }

        $metrics = $this->metrics->forSectors([$sector->id])[$sector->id] ?? null;
        $this->attachSectorMetrics($sector, $metrics, true);
        $sector->setAttribute('pending_approvals', $this->metrics->sectorPendingApprovals($sector->id));

        return $sector;
    }

    /** @return Collection<int,Commitment> */
    public function listCommitments(User $user, string $sectorId): Collection
    {
        $this->getSector($user, $sectorId); // authorize sector access (throws 404 otherwise)

        $commitments = Commitment::where('sector_id', $sectorId)->orderBy('name')->get();
        $this->attachCommitmentMetrics($commitments);

        return $commitments;
    }

    public function getCommitment(User $user, string $id): Commitment
    {
        $commitment = Commitment::find($id);
        if (! $commitment) {
            throw ApiException::notFound('Commitment not found.');
        }
        $this->authorizeSector($user, $commitment->sector_id);

        $this->attachCommitmentMetrics(collect([$commitment]));

        return $commitment;
    }

    /** @return Collection<int,Deliverable> */
    public function listDeliverables(User $user, string $commitmentId): Collection
    {
        $this->getCommitment($user, $commitmentId); // authorize

        $deliverables = Deliverable::where('commitment_id', $commitmentId)->orderBy('deliverable')->get();
        $this->attachDeliverableMetrics($deliverables, $commitmentId);

        return $deliverables;
    }

    public function getDeliverable(User $user, string $id): Deliverable
    {
        $deliverable = Deliverable::with('commitment')->find($id);
        if (! $deliverable || ! $deliverable->commitment) {
            throw ApiException::notFound('Deliverable not found.');
        }
        $this->authorizeSector($user, $deliverable->commitment->sector_id);

        $this->attachDeliverableMetrics(collect([$deliverable]), $deliverable->commitment_id);

        return $deliverable;
    }

    // --- helpers -------------------------------------------------------------

    private function authorizeSector(User $user, int|string|null $sectorId): void
    {
        $allowed = $this->accessibleSectorQuery($user)
            ->where('id', $sectorId)
            ->exists();

        if (! $allowed) {
            throw ApiException::notFound('Resource not found.');
        }
    }

    private function attachSectorMetrics(Sector $sector, ?array $m, bool $detail): void
    {
        $m ??= ['completed' => 0, 'in_progress' => 0, 'at_risk' => 0, 'not_started' => 0, 'total' => 0, 'progress' => 0.0];
        $sector->setAttribute('progress_fraction', $m['progress']);
        $sector->setAttribute('completed_commitments', $m['completed']);
        $sector->setAttribute('at_risk_commitments', $m['at_risk']);

        if ($detail) {
            $sector->setAttribute('total_commitments', $m['total']);
            $sector->setAttribute('in_progress_commitments', $m['in_progress']);
        }
    }

    private function attachCommitmentMetrics(Collection $commitments): void
    {
        $m = $this->metrics->forCommitments($commitments->pluck('id')->all());
        $commitments->each(function (Commitment $c) use ($m) {
            $row = $m[$c->id] ?? null;
            $c->setAttribute('kpi_count', $row['kpi_count'] ?? 0);
            $c->setAttribute('progress_fraction', $row['progress'] ?? 0.0);
            $c->setAttribute('deliverable_count', $row['deliverable_count'] ?? 0);
            $c->setAttribute('at_risk_count', $row['at_risk_count'] ?? 0);
            $c->setAttribute('completed_deliverables', $row['completed_deliverables'] ?? 0);
            $c->setAttribute('next_milestone', $row['next_milestone'] ?? null);
        });
    }

    private function attachDeliverableMetrics(Collection $deliverables, int|string $commitmentId): void
    {
        $m = $this->metrics->forDeliverables($deliverables->pluck('id')->all());
        $parentTitle = optional(Commitment::find($commitmentId))->name;

        $deliverables->each(function (Deliverable $d) use ($m, $parentTitle) {
            $row = $m[$d->id] ?? null;
            $d->setAttribute('kpi_count', $row['kpi_count'] ?? 0);
            $d->setAttribute('progress_fraction', $row['progress'] ?? 0.0);
            $d->setAttribute('last_updated_at', $row['last_updated'] ?? null);
            $d->setAttribute('parent_commitment_title', $parentTitle);
        });
    }
}
