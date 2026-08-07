<?php

namespace App\Services;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Kpi;
use Illuminate\Support\Collection;

class BulkUploadKpiResolver
{
    /** @var Collection<int, Commitment>|null */
    private ?Collection $commitments = null;

    /** @var array<int, Collection<int, Deliverable>> */
    private array $deliverablesByCommitment = [];

    /** @var array<int, Collection<int, Kpi>> */
    private array $kpisByDeliverable = [];

    public function __construct(
        private readonly int $sectorId,
        private readonly int $frameworkId,
        private readonly int $year,
    ) {
    }

    public function resolve(string $commitmentName, string $deliverableName, string $kpiName): ?Kpi
    {
        $commitment = $this->commitments()
            ->first(fn (Commitment $item) => BulkUploadLabelMatcher::labelsAreEquivalent($item->name, $commitmentName));

        if (!$commitment || trim($deliverableName) === '' || trim($kpiName) === '') {
            return null;
        }

        $deliverable = $this->deliverablesFor($commitment->id)
            ->first(fn (Deliverable $item) => BulkUploadLabelMatcher::labelsAreEquivalent($item->deliverable, $deliverableName));

        if (!$deliverable) {
            return null;
        }

        return $this->kpisFor($deliverable->id)
            ->first(fn (Kpi $item) => BulkUploadLabelMatcher::labelsAreEquivalent($item->kpi, $kpiName));
    }

    private function commitments(): Collection
    {
        if ($this->commitments === null) {
            $this->commitments = Commitment::query()
                ->where('sector_id', $this->sectorId)
                ->where('framework_id', $this->frameworkId)
                ->get();
        }

        return $this->commitments;
    }

    private function deliverablesFor(int $commitmentId): Collection
    {
        if (!isset($this->deliverablesByCommitment[$commitmentId])) {
            $this->deliverablesByCommitment[$commitmentId] = Deliverable::query()
                ->where('commitment_id', $commitmentId)
                ->where('framework_id', $this->frameworkId)
                ->get();
        }

        return $this->deliverablesByCommitment[$commitmentId];
    }

    private function kpisFor(int $deliverableId): Collection
    {
        if (!isset($this->kpisByDeliverable[$deliverableId])) {
            $this->kpisByDeliverable[$deliverableId] = Kpi::query()
                ->where('deliverable_id', $deliverableId)
                ->where('framework_id', $this->frameworkId)
                ->where('year', $this->year)
                ->get();
        }

        return $this->kpisByDeliverable[$deliverableId];
    }
}
