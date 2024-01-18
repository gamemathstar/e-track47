<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    use HasFactory;
    protected $fillable = [
        'commitment_id',
        'kpi_name',
        'measurement_unit',
        'target',
        'actual_value',
        'status',
    ];
}
