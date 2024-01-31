<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Commitment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index()
    {
        $commitments = Commitment::all();
        return view('pages.comments.projects', compact('commitments'));
    }

    public function details(Commitment $commitment)
    {
        return view('pages.comments.project-details', compact('commitment'));
    }

    public function postComment(Request $request)
    {
        $comment = new Comment();
        $comment->commitment_id = $request->commitment_id;
        $comment->commenter_name = $request->commenter_name;
        $comment->comment = $request->comment;
        $comment->save();

        return back();
    }
}
