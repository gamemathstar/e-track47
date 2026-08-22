<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Http\Controllers\ReportController;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Framework;
use App\Models\Kpi;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\User;
use App\Support\V2\Presenters\SectorPresenter;
use App\Support\V2\WireEnums;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

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
    public function __construct(
        private readonly HierarchyMetrics $metrics,
        private readonly SectorAccessService $access,
    ) {
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
                'short' => (string) ($s->description ?? ''),
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

    // --- 11.8.7 comprehensive report (real Excel / PDF) ----------------------

    /**
     * Mobile equivalent of the web's "Download Excel" / "Print → Save as PDF"
     * comprehensive report. Delegates spreadsheet + print-data construction to
     * ReportController so the artifact is byte-identical to the web output;
     * here we just decide the format, write the file under the public disk,
     * and return a downloadUrl.
     *
     * Sector scoping mirrors the web flow: Sector Head / Data Admin are pinned
     * to their own sector regardless of the `sectors` param; all-access roles
     * (Governor / Coordinator / Deputy Coordinator / System Admin) honour the
     * provided ids, or default to every sector in the framework when omitted.
     */
    public function generateComprehensiveReport(User $user, array $body): array
    {
        $year = (int) $body['year'];
        $startQuarter = (int) $body['start_quarter'];
        $endQuarter = (int) $body['end_quarter'];
        $type = $body['type'];
        // The wire contract carries sector ids as strings (matching every other
        // sector-scoped endpoint — see §11.3, §11.11.1, §11.6.7 …). Cast to int
        // for the DB query; the validator's `exists:sectors,id` already filtered
        // non-numeric/unknown values out as 422.
        $requestedIds = array_values(array_map('intval', $body['sectors'] ?? []));

        $framework = Framework::where('year', $year)->first();
        if (! $framework) {
            throw ApiException::unprocessable("No framework found for year {$year}.", ['year' => ["No framework found for year {$year}."]]);
        }

        $userSector = $user->isSectorHead() ?: $user->isDataAdmin();

        if ($userSector) {
            $owned = DB::table('sectors')->where('id', $userSector->id)->where('framework_id', $framework->id)->first();
            $sectorIds = $owned ? [(int) $userSector->id] : [];
        } else {
            if (! $this->access->accessibleSectorQuery($user)->where('framework_id', $framework->id)->exists()) {
                throw ApiException::forbidden();
            }

            if (! empty($requestedIds)) {
                $sectorIds = DB::table('sectors')
                    ->whereIn('id', $requestedIds)
                    ->where('framework_id', $framework->id)
                    ->pluck('id')->map(fn ($id) => (int) $id)->all();
            } else {
                $sectorIds = DB::table('sectors')
                    ->where('framework_id', $framework->id)
                    ->pluck('id')->map(fn ($id) => (int) $id)->all();
            }
        }

        $controller = app(ReportController::class);

        if ($type === 'excel') {
            $spreadsheet = $controller->buildComprehensiveSpreadsheet(
                $year, $startQuarter, $endQuarter, $sectorIds, $framework, $userSector,
            );
            return $this->writeSpreadsheetArtifact($spreadsheet, $year);
        }

        $data = $controller->buildComprehensivePrintData(
            $year, $startQuarter, $endQuarter, $sectorIds, $framework, $userSector,
        );
        $html = view('pages.reports.comprehensive-print', $data)->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape')->output();

        return $this->writePdfArtifact($pdf, $year);
    }

    // --- 11.8.8 word document (single-sector PDCU template) -----------------

    /**
     * Mobile equivalent of the web's "Generate Word Document" form on
     * `/reports/word`. Delegates the PhpWord build to ReportController so the
     * .docx is byte-identical to the web stream-download; here we write the
     * file to the public disk and return a `downloadUrl`.
     *
     * Sector Head / Data Admin are pinned to their own sector regardless of
     * the requested `sector_id`; all-access roles (Governor / Coordinator /
     * Deputy Coordinator / System Admin) honour the supplied id.
     */
    public function generateWordDocument(User $user, array $body): array
    {
        $year = (int) $body['year'];
        $requestedSectorId = (int) $body['sector_id'];

        $framework = Framework::where('year', $year)->first();
        if (! $framework) {
            throw ApiException::unprocessable("No framework found for year {$year}.", ['year' => ["No framework found for year {$year}."]]);
        }

        $userSector = $user->isSectorHead() ?: $user->isDataAdmin();
        $sectorId = $userSector ? (int) $userSector->id : $requestedSectorId;

        if (! $this->access->canAccess($user, $sectorId)) {
            throw ApiException::forbidden();
        }

        $sector = Sector::find($sectorId);
        if (! $sector) {
            throw ApiException::notFound('Sector not found.');
        }

        $controller = app(ReportController::class);
        $phpWord = $controller->buildWordReport(
            $sector,
            $year,
            $body['observations'] ?? null,
            $body['recommendations'] ?? null,
            $body['pdcu_coordinator_signature'] ?? null,
            $body['pdcu_coordinator_date'] ?? null,
            $body['sector_facilitator_signature'] ?? null,
            $body['sector_facilitator_date'] ?? null,
        );

        return $this->writeWordArtifact($phpWord, $sector->sector_name, $year);
    }

    private function writeWordArtifact(\PhpOffice\PhpWord\PhpWord $phpWord, string $sectorName, int $year): array
    {
        $id = 'word-'.Str::lower(Str::random(8));
        $stem = ReportController::wordReportFilenameStem($sectorName, $year);
        $filename = "{$stem}.docx";
        $path = 'uploads/reports/'.$id.'.docx';

        $absolute = Storage::disk('public')->path($path);
        @mkdir(dirname($absolute), 0775, true);
        WordIOFactory::createWriter($phpWord, 'Word2007')->save($absolute);

        return [
            'id' => $id,
            'format' => 'word',
            'filename' => $filename,
            'fileSizeLabel' => $this->humanSize((int) (Storage::disk('public')->size($path) ?: 0)),
            'downloadUrl' => Storage::disk('public')->url($path),
        ];
    }

    private function writeSpreadsheetArtifact(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, int $year): array
    {
        $id = 'comp-'.Str::lower(Str::random(8));
        $filename = "All_Sectors_MDAs_Full_Year_Assessment_Reporting_{$year}.xlsx";
        $path = 'uploads/reports/'.$id.'.xlsx';

        $writer = new Xlsx($spreadsheet);
        $absolute = Storage::disk('public')->path($path);
        @mkdir(dirname($absolute), 0775, true);
        $writer->save($absolute);

        return [
            'id' => $id,
            'format' => 'excel',
            'filename' => $filename,
            'fileSizeLabel' => $this->humanSize((int) (Storage::disk('public')->size($path) ?: 0)),
            'downloadUrl' => Storage::disk('public')->url($path),
        ];
    }

    private function writePdfArtifact(string $pdfBytes, int $year): array
    {
        $id = 'comp-'.Str::lower(Str::random(8));
        $filename = "All_Sectors_MDAs_Full_Year_Assessment_Reporting_{$year}.pdf";
        $path = 'uploads/reports/'.$id.'.pdf';
        Storage::disk('public')->put($path, $pdfBytes);

        return [
            'id' => $id,
            'format' => 'pdf',
            'filename' => $filename,
            'fileSizeLabel' => $this->humanSize(strlen($pdfBytes)),
            'downloadUrl' => Storage::disk('public')->url($path),
        ];
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
