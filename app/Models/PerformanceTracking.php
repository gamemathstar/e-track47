<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceTracking extends Model
{
    use HasFactory;

    protected $table = 'performance_tracking';

    protected $fillable = [
        'kpi_id',
        'tracking_date',
        'actual_value',
        'remarks',
        'delivery_department_value',
        'delivery_department_remark',
        'confirmation_status'
    ];

    public function kpi()
    {
        return $this->belongsTo(Kpi::class);
    }
}
