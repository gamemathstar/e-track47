<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Commitment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    //

    public function index(Request $request)
    {
        $commts = Commitment::join('comments','commitments.id','=','comments.commitment_id')
            ->select([
                'commitments.id','commitments.name','comments.commitment_id',
                'description',DB::raw("COUNT(comments.id) AS comments")
            ])
            ->get();
        $projects = [];
        foreach ($commts  as $commt){
            $projects[] = [
                'id'=>$commt->id,'name'=>$commt->name,
                'description'=>$commt->description,'comments'=>$commt->comments,
                'last_comment'=>Comment::comment($commt->commitment_id)
            ];
        }

        return $projects;
//        DB::raw("SELECT * FROM comments cmts WHERE cmts.commitment_id=commitments.id LIMIT 1")
    }
}
