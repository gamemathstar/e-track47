<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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

    public function role()
    {
        return UserRole::where(['user_id' => $this->id])->orderBy('id', 'DESC')->first();
    }

    public function sector()
    {
        $role = $this->role();
        return $role ? Sector::find($role->entity_id) : null;
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
            ->select('sectors.sector_name', DB::raw('COUNT(DISTINCT performance_trackings.id) as confirmed_kpi_count'))
            ->groupBy('sectors.id')
            ->get();

    }

    public function kpiPerformanceRatio()
    {
        $sectors = Sector::select('sectors.sector_name')
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
        return $sectorsWithBudget = Sector::select('sector_name', DB::raw('SUM(commitments.budget) as total_budget'))
            ->leftJoin('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->groupBy('sectors.id')
            ->get();
    }

    public function pendingCompleted()
    {
        $sectorsWithCommitmentStatus = Sector::leftJoin('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->select('sectors.id', 'sectors.sector_name')
            ->selectRaw('COUNT(DISTINCT CASE WHEN commitments.status = "Completed" THEN commitments.id END) as completed_commitments_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN commitments.status != "Completed" THEN commitments.id END) as pending_commitments_count')
            ->groupBy('sectors.id', 'sectors.sector_name')
            ->get();

        return $sectorsWithCommitmentStatus;
    }
}
