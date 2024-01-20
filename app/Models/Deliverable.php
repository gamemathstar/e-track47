<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'commitment_id',
        'deliverable_title',
        'description',
        'deadline',
        'status',
    ];

    // Define relationships or additional methods as needed



    public function kpis()
    {
        return $this->hasMany(Kpi::class);
    }

    public function __kpis()
    {
        return DB::table('deliverables')
            ->join('kpis','deliverables.id','=','kpis.deliverable_id')
            ->join('performance_trackings','kpis.id','=','performance_trackings.kpi_id')
            ->where('deliverables.id',$this->id)->get();
    }


    public function title($characterCount=null)
    {
        if(!$characterCount) return $this->deliverable;
        return strlen($this->deliverable)>$characterCount?substr($this->deliverable,0,$characterCount)." ...":$this->deliverable;
    }

    public function commitment()
    {
        return $this->belongsTo(Commitment::class);
    }


}
