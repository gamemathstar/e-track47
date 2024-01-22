<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceTracking extends Model
{
    use HasFactory;

//    protected $table = 'performance_tracking';

    public function kpi()
    {
        return $this->belongsTo(Kpi::class);
    }
}
