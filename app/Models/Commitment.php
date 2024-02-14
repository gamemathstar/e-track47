<?php

// app/Models/Commitment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'start_date',
        'description',
        'end_date',
        'status',
        'budget',
        'duration_in_days',
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

    public function title($characterCount = null)
    {
        if (!$characterCount) return $this->name;
        return strlen($this->name) > $characterCount ? substr($this->name, 0, $characterCount) . " ..." : $this->name;
    }

    // Add other relationships or methods as needed
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    private function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function commentsCount()
    {
        return count($this->comments()->get());
    }

    public function recentComments()
    {
        return $this->comments()->orderBy('id', 'desc')->limit(2)->get();
    }

    public function allComments()
    {
        return $this->comments()->orderBy('id', 'desc')->get();
    }


    public function countKPI()
    {
//        $records = Sector::with(["commitments.deliverables.kpis"])->where('id', $this->id)->get();
        $total = 0;
        foreach ($this->deliverables as $deliverable) {
            $total += $deliverable->kpis->count();
        }

        return $total;
    }

}
