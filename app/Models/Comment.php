<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    use HasFactory;

    public static function comment($commitment_id)
    {
        return Comment::where('commitment_id',$commitment_id)
            ->select([
                'id','comment','commenter_name AS name',
                'commitment_id AS project_id',
                DB::raw(" CASE
                WHEN TIMESTAMPDIFF(SECOND, created_at, NOW()) < 60 THEN
                    CONCAT(TIMESTAMPDIFF(SECOND, created_at, NOW()), ' seconds ago')
                WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN
                    CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 60 THEN
                    CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                ELSE
                    CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' days ago')
                END AS date")
            ])
            ->orderBy('id','DESC')
            ->first();

    }
}
