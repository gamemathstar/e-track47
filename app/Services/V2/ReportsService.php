<?php

namespace App\Services\V2;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Framework;
use App\Models\Kpi;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\User;
use App\Support\V2\Presenters\SectorPresenter;
use App\Support\V2\WireEnums;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reports hub + setup preview + viewer content + comprehensive/Word generation +
 * print preview (API_REFERENCE.md §11.8).
 *
 * Generation writes a simple, format-appropriate artifact into the public disk
 * under `uploads/reports/` and returns a downloadUrl (per the contract: the
 * client fetches the file separately, not bytes inline). Reuses HierarchyMetrics
 * for sector progress to stay consistent with §11.3 / §11.11.
 */
class ReportsService
{
    public function __construct(private readonly HierarchyMetrics $metrics)
    {
    }

    // --- 11.8.1 hub ----------------------------------------------------------

    public function hub(?string $sectorId, ?string $quarterWire, ?int $year): array
    {
        $fw = $this->activeFramework();
        $sectors = $this->resolveSectors($fw, $sectorId);

        $sectorMetrics = $this->metrics->forSectors($sectors->pluck('id')->all());
        $rows = $sectors->map(function (Sector $s) use ($sectorMetrics) {
            $fraction = (float) ($sectorMetrics[$s->id]['progress'] ?? 0.0);

            return [
                'label' => $s->sector_name,
                'fraction' => round($fraction, 4),
                'valueLabel' => (string) round($fraction * 100),
                'accent' => SectorPresenter::accent($s->id),
            ];
        });

        $avg = $rows->avg('fraction') ?? 0.0;
        $top = $rows->sortByDesc('fraction')->first();

        $kpiIds = $this->frameworkKpiIds($fw);
        $statusMix = $this->statusMix($kpiIds);
        $pending = PerformanceTracking::whereIn('kpi_id', $kpiIds)
            ->whereIn('confirmation_status', ['Pending Sector Head Approval', 'Pending Facilitator', 'Pending Coordinator'])
            ->count();

        return [
            'avgPerformanceFraction' => round((float) $avg, 4),
            'avgPerformanceLabel' => round($avg * 100).'%',
            'topSectorLabel' => $top['label'] ?? '—',
            'pendingCount' => (int) $pending,
            'pendingCaption' => 'Pending review',
            'sectorBars' => $rows->values()->all(),
            'statusMix' => $statusMix,
        ];
    }

    // --- 11.8.2 setup preview ------------------------------------------------

    public function setupPreview(array $body): array
    {
        $sectorIds = $body['sectorIds'] ?: $this->activeFrameworkSectorIds();
        $commitments = Commitment::whereIn('sector_id', $sectorIds)->count();
        $deliverables = Deliverable::whereIn('commitment_id', Commitment::whereIn('sector_id', $sectorIds)->pluck('id'))->count();
        $kpis = Kpi::whereIn('deliverable_id', Deliverable::whereIn('commitment_id', Commitment::whereIn('sector_id', $sectorIds)->pluck('id'))->pluck('id'))->count();

        // Crude estimate: ~30 KB per KPI section, +60 KB per evidence pack.
        $bytes = max(150_000, 30_000 * $kpis + ($body['includeEvidence'] ? 60_000 * $kpis : 0));

        return [
            'commitmentsCount' => (int) $commitments,
            'deliverablesCount' => (int) $deliverables,
            'kpisCount' => (int) $kpis,
            'fileSizeLabel' => $this->humanSize($bytes),
        ];
    }

    // --- 11.8.3 viewer content ----------------------------------------------

    public function viewerContent(array $body): array
    {
        $fw = $this->activeFramework();
        $sectorIds = $body['sectorIds'] ?: $this->activeFrameworkSectorIds();
        $sectors = Sector::whereIn('id', $sectorIds)->orderBy('sector_name')->get();
        $year = $body['year'] ?? optional($fw)->year ?? (int) date('Y');
        $quarter = WireEnums::wireToQuarter($body['quarter'] ?? null);

        $groups = $sectors->map(function (Sector $s) use ($year, $quarter) {
            return [
                'id' => 'sector-'.$s->id,
                'label' => $s->sector_name,
                'accent' => SectorPresenter::accent($s->id),
                'kpiRows' => $this->kpiRowsForSector($s->id, $year, $quarter),
            ];
        });

        return [
            'title' => $this->reportTitle($year, $quarter),
            'subtitle' => $sectors->pluck('sector_name')->implode(' & '),
            'groups' => $groups->values()->all(),
        ];
    }

    // --- 11.8.4 / 11.8.5 generation -----------------------------------------

    public function generateComprehensive(User $user, array $body): array
    {
        $format = $body['format'];
        $content = $this->buildSummary($body);

        return $this->writeArtifact('comp', $format, $content);
    }

    public function generateWord(User $user, array $body): array
    {
        $content = "PDCU Report\n=================\n".
            "Title: ".($body['title'] ?: '—')."\n".
            "Author: ".($body['author'] ?: '—')."\n".
            "Date: ".($body['dateLabel'] ?: '—')."\n";
        if (! empty($body['sectorId'])) {
            $content .= "Sector: ".$body['sectorId']."\n";
        }
        if (! empty($body['year'])) {
            $content .= "Year: ".$body['year']."\n";
        }
        if (! empty($body['quarter'])) {
            $content .= "Quarter: ".$body['quarter']."\n";
        }

        return $this->writeArtifact('word', 'word', $content);
    }

    // --- 11.8.6 print preview ------------------------------------------------

    public function printPreview(): array
    {
        return [
            'pageCount' => 12,
            'docNoLabel' => 'Document No.',
            'docNoValue' => 'PDCU/RPT/'.date('Y').'/'.str_pad((string) (int) (microtime(true) % 10000), 4, '0', STR_PAD_LEFT),
        ];
    }

    // --- helpers -------------------------------------------------------------

    private function activeFramework(): ?Framework
    {
        return Framework::where('status', 'Active')->first();
    }

    private function activeFrameworkSectorIds(): array
    {
        $fw = $this->activeFramework();

        return $fw ? Sector::where('framework_id', $fw->id)->pluck('id')->all() : [];
    }

    /** @return \Illuminate\Support\Collection<int,Sector> */
    private function resolveSectors(?Framework $fw, ?string $sectorId)
    {
        $query = Sector::query()->orderBy('sector_name');
        if ($fw) {
            $query->where('framework_id', $fw->id);
        }
        if ($sectorId) {
            $query->where('id', $sectorId);
        }

        return $query->get();
    }

    private function frameworkKpiIds(?Framework $fw): array
    {
        return $fw ? Kpi::where('framework_id', $fw->id)->pluck('id')->all() : [];
    }

    private function statusMix(array $kpiIds): array
    {
        $total = count($kpiIds);
        if ($total === 0) {
            return [
                'achievedFraction' => 0.0, 'onTrackFraction' => 0.0, 'criticalFraction' => 0.0,
                'totalKpiCount' => 0,
                'achievedPctLabel' => '0%', 'onTrackPctLabel' => '0%', 'criticalPctLabel' => '0%',
            ];
        }

        $fractionExpr = "NULLIF(COALESCE(NULLIF(delivery_department_value, ''), actual_value), '')";
        $milestoneExpr = "NULLIF(milestone, '')";
        $perKpi = DB::table('performance_trackings')
            ->whereIn('kpi_id', $kpiIds)
            ->selectRaw("kpi_id, AVG(LEAST(CAST({$fractionExpr} AS DECIMAL(20,4)) / CAST({$milestoneExpr} AS DECIMAL(20,4)), 1.0)) AS frac")
            ->groupBy('kpi_id')->pluck('frac', 'kpi_id');

        $achieved = $onTrack = $critical = 0;
        foreach ($kpiIds as $id) {
            $f = $perKpi[$id] ?? null;
            if ($f === null) {
                $critical++;
                continue;
            }
            if ((float) $f >= 0.85) {
                $achieved++;
            } elseif ((float) $f >= 0.6) {
                $onTrack++;
            } else {
                $critical++;
            }
        }

        return [
            'achievedFraction' => round($achieved / $total, 4),
            'onTrackFraction' => round($onTrack / $total, 4),
            'criticalFraction' => round($critical / $total, 4),
            'totalKpiCount' => (int) $total,
            'achievedPctLabel' => round($achieved / $total * 100).'%',
            'onTrackPctLabel' => round($onTrack / $total * 100).'%',
            'criticalPctLabel' => round($critical / $total * 100).'%',
        ];
    }

    private function kpiRowsForSector(int|string $sectorId, int $year, ?int $quarter): array
    {
        $kpis = Kpi::whereHas('deliverable.commitment', fn ($q) => $q->where('sector_id', $sectorId))
            ->with('performanceTracking')->orderBy('kpi')->get();

        return $kpis->values()->map(function (Kpi $kpi, int $i) use ($year, $quarter) {
            $tracks = $kpi->performanceTracking->filter(fn ($t) => (int) $t->year === $year && ($quarter === null || (int) $t->quarter === $quarter));
            $latest = $tracks->sortByDesc('quarter')->first();
            $fraction = $this->averageFraction($tracks);

            return [
                'index' => '1.'.($i + 1),
                'title' => $kpi->kpi,
                'body' => $kpi->description ?: '—',
                'targetLabel' => 'Target: '.(string) ($latest->milestone ?? '—'),
                'currentLabel' => (string) ($latest->actual_value ?? '—'),
                'currentAccent' => 'secondary',
                'percentFraction' => round($fraction, 4),
                'percentLabel' => round($fraction * 100).'%',
                'percentAccent' => $fraction >= 0.85 ? 'primary' : ($fraction >= 0.6 ? 'tertiary' : 'error'),
                'trendPoints' => $tracks->sortBy('quarter')->values()->map(fn ($t) => round((float) ($t->actual_value ?: 0) / 100, 2))->all() ?: [],
                'perfLabel' => 'Adjusted performance',
                'perfIconKey' => 'analytics',
                'perfAccent' => $fraction >= 0.85 ? 'primary' : 'tertiary',
                'evidenceLabel' => $tracks->sum(fn ($t) => $t->files?->count() ?? 0).' attachments',
                'notes' => $latest?->remarks ?: null,
            ];
        })->all();
    }

    private function averageFraction(\Illuminate\Support\Collection $tracks): float
    {
        $fractions = $tracks->map(function ($t) {
            $val = $t->delivery_department_value !== null && $t->delivery_department_value !== '' ? $t->delivery_department_value : $t->actual_value;
            if (! is_numeric($val) || ! is_numeric($t->milestone) || (float) $t->milestone == 0.0) {
                return null;
            }

            return min((float) $val / (float) $t->milestone, 1.0);
        })->filter(fn ($v) => $v !== null);

        return $fractions->isEmpty() ? 0.0 : (float) $fractions->avg();
    }

    private function buildSummary(array $body): string
    {
        $fw = $this->activeFramework();
        $year = $body['year'] ?? optional($fw)->year ?? (int) date('Y');
        $quarter = $body['quarter'] ?? null;
        $sectorIds = $body['sectorIds'] ?: $this->activeFrameworkSectorIds();
        $sectors = Sector::whereIn('id', $sectorIds)->orderBy('sector_name')->get();

        $lines = [
            'PDCU Performance Report',
            str_repeat('=', 40),
            'Year: '.$year.($quarter ? ' '.strtoupper($quarter) : ''),
            'Sectors: '.($sectors->pluck('sector_name')->implode(', ') ?: '—'),
            'Include Evidence: '.($body['includeEvidence'] ? 'Yes' : 'No'),
            '',
        ];

        foreach ($sectors as $s) {
            $lines[] = 'Sector: '.$s->sector_name;
            foreach ($this->kpiRowsForSector($s->id, (int) $year, WireEnums::wireToQuarter($quarter)) as $row) {
                $lines[] = '  '.$row['index'].' '.$row['title'].' — '.$row['percentLabel'];
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function writeArtifact(string $prefix, string $format, string $content): array
    {
        $ext = match ($format) {
            'excel' => 'xlsx',
            'word' => 'docx',
            'pdf' => 'pdf',
            'print' => 'pdf',
            default => 'txt',
        };
        $id = $prefix.'-'.Str::lower(Str::random(8));
        $path = 'uploads/reports/'.$id.'.'.$ext;
        Storage::disk('public')->put($path, $content);
        $size = strlen($content);

        return [
            'id' => $id,
            'format' => $format,
            'fileSizeLabel' => $this->humanSize($size),
            'downloadUrl' => Storage::disk('public')->url($path),
        ];
    }

    private function reportTitle(int $year, ?int $quarter): string
    {
        return ($quarter ? 'Q'.$quarter.' ' : '').$year.' Performance Report';
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }
}
