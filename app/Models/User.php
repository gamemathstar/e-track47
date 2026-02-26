<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

//use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

//    use HasApiTokens, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get all roles for this user
     */
    public function roles()
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Get the active role for this user (relationship)
     */
    public function activeRole()
    {
        return $this->hasOne(UserRole::class)
            ->where('role_status', UserRole::STATUS_ACTIVE)
            ->orderBy('id', 'DESC');
    }

    /**
     * Get the current active role (for backward compatibility)
     */
    public function role()
    {
        return $this->getCurrentRole();
    }

    /**
     * Get the current active role (for backward compatibility)
     */
    public function getCurrentRole()
    {
        return $this->roles()->active()->orderBy('id', 'DESC')->first();
    }

    /**
     * Get all active roles
     */
    public function activeRoles()
    {
        return $this->roles()->active()->get();
    }

    /**
     * Get all revoked roles
     */
    public function revokedRoles()
    {
        return $this->roles()->revoked()->get();
    }

    public function sector()
    {
        $role = $this->getCurrentRole();
        if ($role && $role->target_entity === UserRole::ENTITY_SECTOR) {
            return Sector::find($role->entity_id);
        }
        return null;
    }


    public function budgetVsExpenditure()
    {
        return DB::table('deliverables')
            ->leftJoin('expenditures', 'deliverables.id', '=', 'expenditures.deliverable_id')
            ->select('deliverable', 'budget', DB::raw('SUM(amount) as expenditure'))
            ->groupBy('deliverable')
            ->get();
    }

    public function fundRelease()
    {
        return DB::table('fund_releases')
            ->select('release_date', DB::raw('SUM(released_amount) as total_released'))
            ->groupBy('release_date')
            ->get();
    }

    public function kpiPerformance($kpiId)
    {
        return DB::table('performance_tracking')
            ->where('kpi_id', $kpiId)
            ->select('tracking_date', 'actual_value')
            ->get();
    }

    public function commitmentStatus()
    {
        return DB::table('commitments')
            ->select('status', DB::raw('COUNT(*) as status_count'))
            ->groupBy('status')
            ->get();
    }

    public function expenditureBreakdown()
    {
        return DB::table('expenditures')
            ->leftJoin('deliverables', 'expenditures.deliverable_id', '=', 'deliverables.id')
            ->select('deliverable', DB::raw('SUM(amount) as total_expenditure'))
            ->groupBy('deliverable')
            ->get();;
    }

    //Sector HEad
    public function commitmentDuration()
    {
        return DB::table('commitments')
            ->select('name', 'duration_in_days')
            ->get();
    }

    public function commitmentType()
    {
        return DB::table('commitments')
            ->select('type', DB::raw('COUNT(*) as type_count'))
            ->groupBy('type')
            ->get();
    }

    public function sectorWiseExpenditure()
    {
        return DB::table('expenditures')
            ->leftJoin('deliverables', 'expenditures.deliverable_id', '=', 'deliverables.id')
            ->leftJoin('commitments', 'deliverables.commitment_id', '=', 'commitments.id')
            ->leftJoin('sectors', 'commitments.sector_id', '=', 'sectors.id')
            ->select('sector_name', 'deliverable', DB::raw('SUM(amount) as total_expenditure'))
            ->groupBy('sector_name', 'deliverable')
            ->get();
    }

    public function sectorPerformanceKpi($year)
    {
//        $year = 2024; // Replace this with the desired year

//        return $sectors = Sector::with(['commitments.deliverables.kpis.performanceTracking' => function ($query) use ($year) {
//            $query->whereHas('kpi', function ($kpiQuery) use ($year) {
//                $kpiQuery->whereYear('tracking_date', '=', $year)
//                    ->where('actual_value', '=', DB::raw('`target_value`'))
//                    ->where('delivery_department_value', '=', DB::raw('`target_value`'))
//                    ->where('confirmation_status', '=', 'Confirmed');
//            });
//        }])->get();


        return Sector::join('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->whereYear('performance_trackings.tracking_date', '=', $year)
//            ->where('performance_trackings.actual_value', '=', DB::raw('kpis.target_value'))
//            ->where('performance_trackings.delivery_department_value', '=', DB::raw('kpis.target_value'))
            ->where('performance_trackings.confirmation_status', '=', 'Confirmed')
            ->select('sectors.description as sector_name', DB::raw('COUNT(DISTINCT performance_trackings.id) as confirmed_kpi_count'))
            ->groupBy('sectors.id')
            ->get();

    }

    public function kpiPerformanceRatio()
    {
        $sectors = Sector::select('sectors.description as sector_name')
            ->addSelect(DB::raw('COUNT(DISTINCT kpis.id) as total_kpi_count'))
            ->addSelect(DB::raw('COUNT(DISTINCT CASE WHEN performance_trackings.confirmation_status = "Confirmed" THEN kpis.id END) as confirmed_kpi_count'))
            ->leftJoin('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->leftJoin('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->leftJoin('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->leftJoin('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->groupBy('sectors.id')
            ->get();

// Calculate the confirmed KPI ratio
        return $sectors->each(function ($sector) {
            $sector->confirmed_kpi_ratio = $sector->total_kpi_count > 0
                ? $sector->confirmed_kpi_count / $sector->total_kpi_count
                : 0;
        });
    }

    public function canEditUser()
    {

    }

    public function budgetDistribution()
    {
        return $sectorsWithBudget = Sector::select('description as sector_name', DB::raw('SUM(commitments.budget) as total_budget'))
            ->leftJoin('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->groupBy('sectors.id')
            ->get();
    }

    public function pendingCompleted($sector_id = 0)
    {
        $where = [];
        if ($sector_id) {
            $where[] = ['sectors.id', '=', $sector_id];
        }
        $sectorsWithCommitmentStatus = Sector::leftJoin('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->select('sectors.id', 'sectors.description as sector_name')
            ->selectRaw('COUNT(DISTINCT CASE WHEN commitments.status = "Completed" THEN commitments.id END) as completed_commitments_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN commitments.status != "Completed" THEN commitments.id END) as pending_commitments_count')
            ->groupBy('sectors.id', 'sectors.sector_name')
            ->where($where)
            ->get();

        return $sectorsWithCommitmentStatus;
    }

    public function isSystemAdmin()
    {
        $userRole = $this->getCurrentRole();
        if ($userRole && $userRole->isActive()) {
            return $userRole->target_entity === UserRole::ENTITY_SYSTEM;
        }
        return false;
    }

    public function isGovernor()
    {
        $userRole = $this->getCurrentRole();
        if ($userRole && $userRole->isActive()) {
            return $userRole->target_entity === UserRole::ENTITY_STATE;
        }
        return false;
    }

    public function isSectorHead()
    {
        $userRole = $this->getCurrentRole();
        if ($userRole && $userRole->isActive()) {
            if ($userRole->target_entity === UserRole::ENTITY_SECTOR && $userRole->role === UserRole::ROLE_SECTOR_HEAD) {
                return Sector::find($userRole->entity_id);
            }
        }
        return false;
    }

    public function isSectorAdmin()
    {
        // Deprecated - use isDataAdmin() instead
        return $this->isDataAdmin();
    }

    public function isDataAdmin()
    {
        $userRole = $this->getCurrentRole();
        if ($userRole && $userRole->isActive()) {
            if ($userRole->target_entity === UserRole::ENTITY_SECTOR && 
                ($userRole->role === UserRole::ROLE_DATA_ADMIN || $userRole->role === UserRole::ROLE_SECTOR_ADMIN)) {
                return Sector::find($userRole->entity_id);
            }
        }
        return false;
    }

    public function isDeliveryDepartment()
    {
        $userRole = $this->getCurrentRole();
        if ($userRole && $userRole->isActive()) {
            return $userRole->target_entity === UserRole::ENTITY_DELIVERABLE;
        }
        return false;
    }

    /**
     * Check if user is a Coordinator
     */
    public function isCoordinator()
    {
        return $this->hasAnyActiveRole([UserRole::ROLE_COORDINATOR]);
    }

    /**
     * Check if user is a Deputy Coordinator
     */
    public function isDeputyCoordinator()
    {
        return $this->hasAnyActiveRole([UserRole::ROLE_DEPUTY_COORDINATOR]);
    }

    /**
     * Check if user is a Facilitator
     */
    public function isFacilitator()
    {
        return $this->hasAnyActiveRole([UserRole::ROLE_FACILITATOR]);
    }

    /**
     * Check if user has any delivery unit role (Coordinator, Deputy Coordinator, or Facilitator)
     */
    public function isDeliveryUnit()
    {
        return $this->hasAnyActiveRole([
            UserRole::ROLE_COORDINATOR,
            UserRole::ROLE_DEPUTY_COORDINATOR,
            UserRole::ROLE_FACILITATOR,
            UserRole::ROLE_DELIVERY_DEPARTMENT, // For backward compatibility
        ]);
    }

    /**
     * Check if user can access all sectors (Coordinator or Deputy Coordinator)
     */
    public function canAccessAllSectors()
    {
        $activeRoles = $this->activeRoles();
        foreach ($activeRoles as $role) {
            if ($role->canAccessAllSectors()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get sectors assigned to user (for Facilitators)
     * Returns array of sector IDs, or empty array if user can access all sectors
     */
    public function getAssignedSectorIds()
    {
        if ($this->canAccessAllSectors()) {
            return []; // Empty array means all sectors
        }

        $sectorIds = [];
        $activeRoles = $this->activeRoles();
        foreach ($activeRoles as $role) {
            if ($role->isRestrictedToAssignedSectors() && $role->entity_id > 0) {
                $sectorIds[] = $role->entity_id;
            }
        }
        return array_unique($sectorIds);
    }

    /**
     * Check if user has any of the specified active roles
     */
    public function hasAnyActiveRole(array $roles)
    {
        $activeRoles = $this->activeRoles();
        foreach ($activeRoles as $role) {
            if (in_array($role->role, $roles)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        $userRole = $this->getCurrentRole();
        return $userRole && $userRole->isActive() && $userRole->role === $role;
    }
}
