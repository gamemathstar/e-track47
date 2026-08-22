<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Framework;
use App\Models\Kpi;
use App\Models\Sector;
use App\Models\User;
use App\Support\V2\Presenters\Presenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Framework management (API_REFERENCE.md §11.5). Coordinator-only.
 *
 * `set-default` reuses the web app's "activate" semantics: sets `status='Active'`
 * and archives every other framework; `is_default` mirrors `status='Active'` so
 * the web's source of truth never diverges (GR2). Inheritance copies the sector
 * hierarchy from a source framework into the new one (sectors → commitments →
 * deliverables → KPIs; performance data is intentionally not copied).
 */
class FrameworkService
{
    /** @return array<int,array> */
    public function list(): array
    {
        return Framework::orderByDesc('year')->get()->map(fn (Framework $f) => $this->summary($f))->all();
    }

    public function stats(): array
    {
        $latest = Framework::orderByDesc('updated_at')->first();

        return [
            'activeCount' => (int) Framework::where('status', 'Active')->count(),
            'archivedCount' => (int) Framework::where('status', 'Archived')->count(),
            'latestUpdateLabel' => 'Latest Update',
            'latestUpdateValue' => $latest?->title ?? '—',
        ];
    }

    public function get(string $id): array
    {
        $f = $this->findOrFail($id);
        $f->loadCount(['sectors', 'commitments', 'deliverables', 'kpis']);

        return array_merge($this->summary($f), [
            'description' => $f->description ?: null,
            'reportingYear' => (int) $f->year,
            'sectorCount' => (int) $f->sectors_count,
            'kpiCount' => (int) $f->kpis_count,
            'commitmentsCount' => (int) $f->commitments_count,
            'deliverablesCount' => (int) $f->deliverables_count,
            'createdAt' => optional($f->created_at)->toIso8601String(),
            'createdBy' => optional($f->creator)->full_name,
            'creatorInitials' => Presenter::initials(optional($f->creator)->full_name),
            'isDefault' => (bool) ($f->is_default ?? false) || $f->status === 'Active',
            'inheritedFromFrameworkId' => $f->inherited_from_framework_id ? (string) $f->inherited_from_framework_id : null,
            'inheritedFromTitle' => $f->inherited_from_framework_id
                ? optional(Framework::find($f->inherited_from_framework_id))->title
                : null,
        ]);
    }

    /** @return array<int,array> */
    public function sectorsFor(string $id): array
    {
        $f = $this->findOrFail($id);

        return Sector::where('framework_id', $f->id)->orderBy('sector_name')->get()->map(function (Sector $s) use ($f) {
            $commitments = Commitment::where('sector_id', $s->id)->count();
            $deliverables = Deliverable::whereIn('commitment_id', Commitment::where('sector_id', $s->id)->pluck('id'))->count();
            $accents = ['error', 'secondary', 'tertiary', 'performance_fair'];

            return [
                'id' => (string) $s->id,
                'frameworkId' => (string) $f->id,
                'name' => $s->sector_name,
                'meta' => "{$commitments} Commitments • {$deliverables} Deliverables",
                'accent' => $accents[abs(crc32((string) $s->id)) % count($accents)],
            ];
        })->values()->all();
    }

    public function create(User $user, array $params): array
    {
        $this->assertCoordinator($user);

        $reportingYear = isset($params['reportingYear']) ? (int) $params['reportingYear'] : (int) date('Y');
        if (Framework::where('year', $reportingYear)->exists()) {
            throw ApiException::conflict("A framework for {$reportingYear} already exists.");
        }

        return DB::transaction(function () use ($user, $params, $reportingYear) {
            $framework = new Framework();
            $framework->year = $reportingYear;
            $framework->title = $params['name'];
            $framework->subtitle = $params['subtitle'] ?? null;
            $framework->status = 'Active'; // newly created is the active framework
            $framework->description = $params['description'] ?? null;
            $framework->created_by = $user->id;
            $framework->is_default = true;
            if (($params['sectorMethod'] ?? 'blank') === 'inherit' && ! empty($params['inheritedFromFrameworkId'])) {
                $framework->inherited_from_framework_id = (int) $params['inheritedFromFrameworkId'];
            }
            $framework->save();

            // Archive every other framework so there is one active = isDefault (GR2).
            Framework::where('id', '!=', $framework->id)
                ->update(['status' => 'Archived', 'is_default' => false]);

            if (($params['sectorMethod'] ?? 'blank') === 'inherit' && ! empty($params['inheritedFromFrameworkId'])) {
                $this->copyHierarchy((int) $params['inheritedFromFrameworkId'], $framework->id);
            }

            return $this->get((string) $framework->id);
        });
    }

    public function archive(User $user, string $id): void
    {
        $this->assertCoordinator($user);

        $f = $this->findOrFail($id);
        if ($f->status === 'Archived') {
            throw ApiException::conflict('Framework is already archived.');
        }

        $f->status = 'Archived';
        $f->archived_by = $user->id;
        $f->archived_at = now();
        $f->is_default = false;
        $f->save();
    }

    public function setDefault(User $user, string $id): void
    {
        $this->assertCoordinator($user);

        $f = $this->findOrFail($id);

        DB::transaction(function () use ($f) {
            // Reuse the web's "activate" semantics (GR2).
            Framework::where('id', '!=', $f->id)
                ->update(['status' => 'Archived', 'is_default' => false]);
            $f->status = 'Active';
            $f->is_default = true;
            $f->archived_at = null;
            $f->archived_by = null;
            $f->save();
        });
    }

    // --- helpers -------------------------------------------------------------

    private function findOrFail(string $id): Framework
    {
        $f = Framework::find($id);
        if (! $f) {
            throw ApiException::notFound('Framework not found.');
        }

        return $f;
    }

    private function assertCoordinator(User $user): void
    {
        if (! $user->isCoordinator()) {
            throw ApiException::forbidden('Only Coordinators may manage frameworks.');
        }
    }

    private function summary(Framework $f): array
    {
        $sectorCount = Sector::where('framework_id', $f->id)->count();

        return [
            'id' => (string) $f->id,
            'title' => $f->title,
            'subtitle' => $f->subtitle ?? '',
            'status' => $f->status === 'Active' ? 'active' : 'archived',
            'statusLabel' => $f->status === 'Active' ? 'Active' : 'Archived',
            'sectorCountLabel' => $sectorCount.' Sector'.($sectorCount === 1 ? '' : 's'),
            'dateLabel' => optional($f->created_at)->format('M j, Y') ?? '—',
            'reportingYear' => (int) $f->year,
            'sectorCount' => (int) $sectorCount,
        ];
    }

    private function copyHierarchy(int $sourceId, int $targetId): void
    {
        $source = Framework::find($sourceId);
        if (! $source) {
            return;
        }

        foreach (Sector::where('framework_id', $sourceId)->get() as $s) {
            $newSector = $s->replicate(['framework_id']);
            $newSector->framework_id = $targetId;
            $newSector->save();

            foreach (Commitment::where('sector_id', $s->id)->get() as $c) {
                $newCommitment = $c->replicate(['sector_id', 'framework_id']);
                $newCommitment->sector_id = $newSector->id;
                $newCommitment->framework_id = $targetId;
                $newCommitment->save();

                foreach (Deliverable::where('commitment_id', $c->id)->get() as $d) {
                    $newDeliverable = $d->replicate(['commitment_id', 'framework_id']);
                    $newDeliverable->commitment_id = $newCommitment->id;
                    $newDeliverable->framework_id = $targetId;
                    $newDeliverable->save();

                    foreach (Kpi::where('deliverable_id', $d->id)->get() as $k) {
                        $newKpi = $k->replicate(['deliverable_id', 'framework_id']);
                        $newKpi->deliverable_id = $newDeliverable->id;
                        $newKpi->framework_id = $targetId;
                        $newKpi->save();
                    }
                }
            }
        }
    }
}
