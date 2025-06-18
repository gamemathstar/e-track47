<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class PerformanceTracking extends Model
{
    use HasFactory;

//    protected $table = 'performance_tracking';

    protected $fillable = [
        'kpi_id',
        'quarter',
        'milestone',
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

    public function status()
    {
        return $this->confirmation_status;
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function attachments($id)
    {
        $target = Auth::user()->role()->target_entity;
        $files = File::where(['fileable_id' => $id, 'attached_by' => $target])->get();

        return view('pages.sector.ajax.attachments', ['files' => $files]);
    }
}
