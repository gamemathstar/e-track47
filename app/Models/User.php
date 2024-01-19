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
        $roles = ['0'=>'System Admin','1'=>'Governor','2'=>'Sector Head','3'=>'Sector Admin'];
        return $roles[$this->role];
    }

    public function sectorHead()
    {
        return  SectorHead::where(['user_id'=>$this->id])->orderBy('date_to','DESC')->first();

    }

    public function sector()
    {
        $sectorHead = $this->sectorHead();
        return $sectorHead?Sector::find($sectorHead->sector_id):null;
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
        return  DB::table('commitments')
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

    public function sectorPerformanceKpi()
    {
        $year = 2024;
        return $sectors = Sector::with(['commitments.deliverables.kpis.performanceTracking' => function ($query) use ($year) {
            $query->whereYear('tracking_date', '=', $year)
//                ->where('actual_value', '=', DB::raw('target_value'))
                ->where('delivery_department_value', '=', DB::raw('target_value'))
                ->where('confirmation_status', '=', 'Confirmed');
        }])->get();
    }
}
