<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        $roles = ['0'=>'System Admin','1'=>'Governor','2'=>'Sector Head','3'=>'Sector Admin'];
        return $roles[$this->role];
    }

    public function sectorHead()
    {
        return  SectorHead::where(['user_id'=>$this->id])->orderBy('date_to','DESC')->first();

    }

    public function sector()
    {
        $sectorHead = $this->sectorHead();
        return $sectorHead?Sector::find($sectorHead->sector_id):null;
    }


}
