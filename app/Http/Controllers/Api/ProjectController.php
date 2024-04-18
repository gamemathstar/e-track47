<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    //

    public function index(Request $request)
    {
        $commts = Commitment::leftJoin('comments', 'commitments.id', '=', 'comments.commitment_id')
            ->select([
                'commitments.id', 'commitments.name', 'comments.commitment_id',
                'description', DB::raw("COUNT(comments.id) AS comments_count")
            ])
            ->groupBy('commitments.id')
            ->get();
        $projects = [];
        foreach ($commts as $commt) {
            $projects[] = [
                'id' => $commt->id, 'name' => $commt->name,
                'description' => $commt->description, 'comments_count' => $commt->comments_count,
                'comments' => [Comment::comment($commt->commitment_id)], 'deliverables' => []
            ];
        }

        return $projects;
    }

    public function project(Request $request, $id)
    {
        $commt = Commitment::leftJoin('comments', 'commitments.id', '=', 'comments.commitment_id')
            ->where('commitments.id', $id)
            ->select([
                'commitments.id', 'commitments.name', 'comments.commitment_id',
                'description', DB::raw("COUNT(comments.id) AS comments_count")
            ])
            ->first();

        $deliverables = Deliverable::where('commitment_id', $commt->id)
            ->select([
                'deliverables.id', 'deliverables.commitment_id', 'deliverables.budget',
                'deliverables.start_date', 'deliverables.end_date', 'deliverables.deliverable'
            ])->get();
        $comments = Comment::where('commitment_id', $commt->id)
            ->select([
                'id', 'comment', 'commenter_name AS name',
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
            ->orderBy('comments.id', 'DESC')->get();


        $project = [
            'id' => $commt->id, 'name' => $commt->name, 'commitment_id' => $commt->commitment_id,
            'description' => $commt->description, 'comments_count' => $commt->comments_count,
            'comments' => $comments, 'deliverables' => $deliverables
        ];
        return response(['success' => true, 'message' => "", 'data' => $project]);;
    }

    public function addComment(Request $request)
    {
        $this->validate($request,
            [
                'project_id' => "required",
                'name' => "required",
                'comment' => "required",
            ]);

        $project = Commitment::find($request->project_id);
        if ($project) {
            $comment = new Comment();
            $comment->commitment_id = $request->project_id;
            $comment->comment = $request->comment;
            $comment->commenter_name = $request->name;
            $comment->save();
            if ($comment) {
                return response(['success' => true, 'message' => 'Comment added']);
            } else {
                return response(['success' => false, 'message' => 'Failed to create comment']);
            }

        }
        return response(['message' => "Invalid Project", 'errors' => []]);

    }

    public function sectors(Request $request)
    {
        $sectors = Sector::select(['id', 'sector_name', 'description'])->get();
        return response(['success' => true, 'message' => "", 'data' => $sectors]);
    }
}
