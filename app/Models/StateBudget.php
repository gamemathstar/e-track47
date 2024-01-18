<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StateBudget extends Model
{
    use HasFactory;

    public static function currentYear()
    {
        return date('Y');
    }

    public static function activeBudget($year=0)
    {
        $yearX = $year?:self::currentYear();
        return StateBudget::where('year',$yearX)->first();
    }

    public static function releases($year=0)
    {
        $yearX = $year?:self::currentYear();
        return SectorRelease::where('year',$yearX)->sum('amount');
    }

    public static function releaseCount($year=0)
    {
        $yearX = $year?:self::currentYear();

        return $records = DB::table('sectors')
            ->leftJoin('sector_budgets',function ($join)use($yearX){
                $join->on('sector_budgets.sector_id','=','sectors.id')
                    ->on('sector_budgets.year','=',DB::raw($yearX));
            })
            ->leftJoin('sector_releases',function ($join)use($yearX){
                $join->on('sector_releases.sector_id','=','sectors.id')
                    ->on('sector_releases.year','=',DB::raw($yearX));

            })->select([DB::raw("SUM(sector_releases.amount) AS total_released"),"sector_budgets.amount AS budget_amount"])
            ->groupBy('sectors.id')
            ->havingRaw("total_released < budget_amount OR total_released IS NULL OR budget_amount IS NULL")->count();
    }

    public static function deliveredIn($year=0)
    {
        $yearX = $year?:self::currentYear();

        return DeliveryKpi::where('year','<=',$yearX)->where('target',DB::raw('actual_value'))->count();
    }
}
