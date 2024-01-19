<?php

// app/Models/Sector.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function commitments()
    {
        return $this->hasMany(Commitment::class);
    }

    public function head()
    {
        return SectorHead::join('users','sector_heads.user_id','=','users.id')
            ->orderBy('date_to','DESC')
            ->whereDate('date_from','<=',today())
            ->whereDate('date_to','>=',today())
            ->where('sector_id',$this->id)
            ->first();
    }

    public function files()
    {
        return SectorFile::where('sector_id',$this->id)->get();
    }

    public function budgets()
    {
        return SectorBudget::leftJoin('commitments','commitments.sector_id','=','sector_budgets.sector_id')
            ->leftJoin('commitment_budgets',function ($join){
                $join->on('commitment_budgets.commitment_id','=','commitments.id')
                ->on('commitment_budgets.year','=','sector_budgets.year');
            })
            ->where('commitments.sector_id',$this->id)
            ->where('sector_budgets.sector_id',$this->id)
            ->select(['commitments.sector_id','sector_budgets.amount as sector_amount',DB::raw('SUM(commitment_budgets.amount) as allocation'),'sector_budgets.year'])
            ->groupBy('sector_budgets.year')
            ->get();
    }


    public static function currentYear()
    {
        return date('Y');
    }

    public function distribution($year=0)
    {
        $yearX = $year?:StateBudget::currentYear();
        $budget = StateBudget::activeBudget();
        $myBudget = SectorBudget::where('sector_id',$this->id)->sum('amount');
        return $myBudget && $budget? intval(($myBudget/$budget->amount)*100) :0;
    }
}
