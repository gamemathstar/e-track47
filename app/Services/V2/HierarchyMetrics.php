<?php

namespace App\Services\V2;

use Illuminate\Support\Facades\DB;

/**
 * Derived performance/count metrics for the sector → commitment → deliverable
 * hierarchy (API_REFERENCE.md §11.3). All methods use **grouped** aggregate
 * queries keyed by the relevant id, so a list endpoint computes every row's
 * metrics in a constant number of queries (no N+1).
 *
 * Progress is a 0–1 fraction: the mean of COALESCE(delivery_department_value,
 * actual_value) / milestone across the subtree's performance trackings, each
 * achievement capped at 1.0. The performance columns are numeric-valued strings,
 * cast to DECIMAL; a zero/empty milestone yields NULL (ignored by AVG). MySQL.
 */
class HierarchyMetrics
{
    /** Pending lifecycle states (DB confirmation_status values). */
    private const PENDING_STATES = [
        'Pending Sector Head Approval',
        'Pending Facilitator',
        'Pending Coordinator',
    ];

    private function fractionExpr(string $ptAlias = 'pt'): string
    {
        $value = "NULLIF(COALESCE(NULLIF({$ptAlias}.delivery_department_value, ''), {$ptAlias}.actual_value), '')";
        $milestone = "NULLIF({$ptAlias}.milestone, '')";

        return "AVG(LEAST(CAST({$value} AS DECIMAL(20,4)) / CAST({$milestone} AS DECIMAL(20,4)), 1.0))";
    }

    /**
     * Per-sector commitment counts + progress fraction.
     *
     * @return array<int|string, array{completed:int,in_progress:int,at_risk:int,not_started:int,total:int,progress:float}>
     */
    public function forSectors(array $sectorIds): array
    {
        if (empty($sectorIds)) {
            return [];
        }

        $out = [];
        foreach ($sectorIds as $id) {
            $out[$id] = ['completed' => 0, 'in_progress' => 0, 'at_risk' => 0, 'not_started' => 0, 'total' => 0, 'progress' => 0.0];
        }

        $counts = DB::table('commitments')
            ->whereIn('sector_id', $sectorIds)
            ->selectRaw('sector_id, status, COUNT(*) AS c')
            ->groupBy('sector_id', 'status')
            ->get();

        foreach ($counts as $row) {
            $bucket = match (strtolower(trim((string) $row->status))) {
                'completed' => 'completed',
                'at risk', 'at_risk' => 'at_risk',
                'not started', 'not_started' => 'not_started',
                default => 'in_progress', // in progress, active, …
            };
            $out[$row->sector_id][$bucket] += (int) $row->c;
            $out[$row->sector_id]['total'] += (int) $row->c;
        }

        $progress = DB::table('performance_trackings as pt')
            ->join('kpis as k', 'k.id', '=', 'pt.kpi_id')
            ->join('deliverables as d', 'd.id', '=', 'k.deliverable_id')
            ->join('commitments as c', 'c.id', '=', 'd.commitment_id')
            ->whereIn('c.sector_id', $sectorIds)
            ->selectRaw('c.sector_id AS sid, '.$this->fractionExpr().' AS frac')
            ->groupBy('c.sector_id')
            ->get();

        foreach ($progress as $row) {
            if ($row->frac !== null) {
                $out[$row->sid]['progress'] = round((float) $row->frac, 4);
            }
        }

        return $out;
    }

    public function sectorPendingApprovals(int|string $sectorId): int
    {
        return DB::table('performance_trackings as pt')
            ->join('kpis as k', 'k.id', '=', 'pt.kpi_id')
            ->join('deliverables as d', 'd.id', '=', 'k.deliverable_id')
            ->join('commitments as c', 'c.id', '=', 'd.commitment_id')
            ->where('c.sector_id', $sectorId)
            ->whereIn('pt.confirmation_status', self::PENDING_STATES)
            ->count();
    }

    /**
     * Per-commitment metrics.
     *
     * @return array<int|string, array{kpi_count:int,deliverable_count:int,at_risk_count:int,completed_deliverables:int,progress:float,next_milestone:?string}>
     */
    public function forCommitments(array $commitmentIds): array
    {
        if (empty($commitmentIds)) {
            return [];
        }

        $out = [];
        foreach ($commitmentIds as $id) {
            $out[$id] = ['kpi_count' => 0, 'deliverable_count' => 0, 'at_risk_count' => 0, 'completed_deliverables' => 0, 'progress' => 0.0, 'next_milestone' => null];
        }

        $deliverables = DB::table('deliverables')
            ->whereIn('commitment_id', $commitmentIds)
            ->selectRaw('commitment_id, status, COUNT(*) AS c, MAX(due_date) AS next_due')
            ->groupBy('commitment_id', 'status')
            ->get();

        foreach ($deliverables as $row) {
            $out[$row->commitment_id]['deliverable_count'] += (int) $row->c;
            $wire = strtolower(trim((string) $row->status));
            if (in_array($wire, ['at risk', 'at_risk', 'delayed', 'critical'], true)) {
                $out[$row->commitment_id]['at_risk_count'] += (int) $row->c;
            }
            if ($wire === 'completed') {
                $out[$row->commitment_id]['completed_deliverables'] += (int) $row->c;
            }
            if ($row->next_due !== null && ($out[$row->commitment_id]['next_milestone'] === null || $row->next_due > $out[$row->commitment_id]['next_milestone'])) {
                $out[$row->commitment_id]['next_milestone'] = $row->next_due;
            }
        }

        $kpis = DB::table('kpis as k')
            ->join('deliverables as d', 'd.id', '=', 'k.deliverable_id')
            ->whereIn('d.commitment_id', $commitmentIds)
            ->selectRaw('d.commitment_id AS cid, COUNT(*) AS c')
            ->groupBy('d.commitment_id')
            ->get();

        foreach ($kpis as $row) {
            $out[$row->cid]['kpi_count'] = (int) $row->c;
        }

        $progress = DB::table('performance_trackings as pt')
            ->join('kpis as k', 'k.id', '=', 'pt.kpi_id')
            ->join('deliverables as d', 'd.id', '=', 'k.deliverable_id')
            ->whereIn('d.commitment_id', $commitmentIds)
            ->selectRaw('d.commitment_id AS cid, '.$this->fractionExpr().' AS frac')
            ->groupBy('d.commitment_id')
            ->get();

        foreach ($progress as $row) {
            if ($row->frac !== null) {
                $out[$row->cid]['progress'] = round((float) $row->frac, 4);
            }
        }

        return $out;
    }

    /**
     * Per-deliverable metrics.
     *
     * @return array<int|string, array{kpi_count:int,progress:float,last_updated:?string}>
     */
    public function forDeliverables(array $deliverableIds): array
    {
        if (empty($deliverableIds)) {
            return [];
        }

        $out = [];
        foreach ($deliverableIds as $id) {
            $out[$id] = ['kpi_count' => 0, 'progress' => 0.0, 'last_updated' => null];
        }

        $kpis = DB::table('kpis')
            ->whereIn('deliverable_id', $deliverableIds)
            ->selectRaw('deliverable_id, COUNT(*) AS c')
            ->groupBy('deliverable_id')
            ->get();

        foreach ($kpis as $row) {
            $out[$row->deliverable_id]['kpi_count'] = (int) $row->c;
        }

        $progress = DB::table('performance_trackings as pt')
            ->join('kpis as k', 'k.id', '=', 'pt.kpi_id')
            ->whereIn('k.deliverable_id', $deliverableIds)
            ->selectRaw('k.deliverable_id AS did, '.$this->fractionExpr().' AS frac, MAX(pt.updated_at) AS last_updated')
            ->groupBy('k.deliverable_id')
            ->get();

        foreach ($progress as $row) {
            if ($row->frac !== null) {
                $out[$row->did]['progress'] = round((float) $row->frac, 4);
            }
            $out[$row->did]['last_updated'] = $row->last_updated;
        }

        return $out;
    }
}
