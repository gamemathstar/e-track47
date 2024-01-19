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
        return DB::table('delivery_kpis')->join('kpis','kpis.id','=','delivery_kpis.kpi_id')->where('delivery_kpis.deliverable_id',$this->id)->get();
    }


    public function title($characterCount=null)
    {
        if(!$characterCount) return $this->deliverable_title;
        return strlen($this->deliverable_title)>$characterCount?substr($this->deliverable_title,0,$characterCount)." ...":$this->deliverable_title;
    }

    public function commitment()
    {
        return $this->belongsTo(Commitment::class);
    }


}
