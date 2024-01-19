<?php

// app/Models/Commitment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'commitment_title',
        'description',
        'deadline',
        'status',
        'date_created',
        'year',
        'sector_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_commitments')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class);
    }


    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function title($characterCount=null)
    {
        if(!$characterCount) return $this->commitment_title;
        return strlen($this->commitment_title)>$characterCount?substr($this->commitment_title,0,$characterCount)." ...":$this->commitment_title;
    }

    // Add other relationships or methods as needed
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

}
