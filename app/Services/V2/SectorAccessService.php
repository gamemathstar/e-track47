<?php

namespace App\Services\V2;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for v2 role-based sector access, reusing the web app's
 * existing User role helpers. Shared by the hierarchy reads (§11.3) and the KPI
 * tracking mutations (§11.4) so authorization stays consistent.
 *
 *  - all-access roles (coordinator / deputy / governor / system admin) → every sector
 *  - facilitator → assigned sectors
 *  - sector head / data admin → own sector
 *  - otherwise → none
 */
class SectorAccessService
{
    public function accessibleSectorQuery(User $user, ?int $frameworkId = null): Builder
    {
        $query = Sector::query();

        if ($frameworkId) {
            $query->where('framework_id', $frameworkId);
        }

        if ($user->canAccessAllSectors()) {
            return $query;
        }

        if ($user->isFacilitator()) {
            return $query->whereIn('id', $user->getAssignedSectorIds() ?: [-1]);
        }

        if ($own = ($user->isSectorHead() ?: $user->isDataAdmin())) {
            return $query->where('id', $own->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canAccess(User $user, int|string|null $sectorId): bool
    {
        if ($sectorId === null) {
            return false;
        }

        return $this->accessibleSectorQuery($user)->whereKey($sectorId)->exists();
    }
}
