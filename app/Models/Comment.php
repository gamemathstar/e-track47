<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    public static function comment($commitment_id)
    {
        return Comment::where('commitment_id',$commitment_id)
            ->select(['id','comment','commenter_name AS name','commitment_id AS project_id','created_at'])
            ->orderBy('id','DESC')
            ->first();

    }
}
