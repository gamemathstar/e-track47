<?php

namespace App\Services\V2;

use App\Models\Sector;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for v2 role-based sector access. Shared by the
 * hierarchy reads (§11.3) and the KPI tracking mutations (§11.4) so
 * authorization stays consistent.
 *
 *  - all-access roles → every sector
 *      - Coordinator / Deputy Coordinator (via User::canAccessAllSectors)
 *      - System Admin (v2-only escalation — the web's helper excludes them,
 *        but the mobile admin directory needs read access everywhere)
 *  - facilitator → assigned sectors
 *  - sector head / data admin → own sector
 *  - otherwise → none
 *
 * The System Admin escalation lives here (not on the shared User model) so the
 * web app's role-based access stays exactly as it was (GR1).
 */
class SectorAccessService
{
    public function accessibleSectorQuery(User $user, ?int $frameworkId = null): Builder
    {
        $query = Sector::query();

        if ($frameworkId) {
            $query->where('framework_id', $frameworkId);
        }

        if ($this->hasAllSectorAccess($user)) {
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

    /**
     * Sector ids the user may see, for filtering rows joined to sectors.
     * `null` = all sectors (no constraint); `[]` = none.
     *
     * @return int[]|null
     */
    public function accessibleSectorIds(User $user): ?array
    {
        if ($this->hasAllSectorAccess($user)) {
            return null;
        }

        if ($user->isFacilitator()) {
            return array_map('intval', $user->getAssignedSectorIds() ?: []);
        }

        if ($own = ($user->isSectorHead() ?: $user->isDataAdmin())) {
            return [(int) $own->id];
        }

        return [];
    }

    /**
     * v2-scoped "all sectors visible" check. Delegates to the web app's
     * User::canAccessAllSectors() (Coordinator / Deputy Coordinator) and
     * additionally treats System Admin as all-access — needed for the mobile
     * admin directory, gallery management, and other read-everywhere flows
     * that the web app handles via system-level routes instead of sector
     * scoping.
     *
     * NB: we don't use `$user->isSystemAdmin()` here. That helper only checks
     * `target_entity === 'System'` without verifying the role name, so any
     * user whose current role row happens to have target_entity='System'
     * (data error, legacy fixture, etc.) would be granted cross-sector
     * access. We check the role name explicitly instead.
     */
    private function hasAllSectorAccess(User $user): bool
    {
        if ($user->canAccessAllSectors()) {
            return true;
        }

        $role = $user->getCurrentRole();

        return $role && $role->isActive() && $role->role === UserRole::ROLE_SYSTEM_ADMIN;
    }
}

