<?php
// app/Models/UsersCommitment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersCommitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commitment_id',
        'role',
    ];



    // Define relationships or additional methods as needed
}
