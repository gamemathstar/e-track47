<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\GalleryComment;
use Illuminate\Http\Request;

class PublicGalleryController extends Controller
{
    /**
     * Display the public gallery
     */
    public function index()
    {
        $galleries = Gallery::active()
            ->ordered()
            ->paginate(12);

        return view('pages.public.gallery', compact('galleries'));
    }

    /**
     * Display a single gallery item in detail view
     */
    public function show(Gallery $gallery)
    {
        // Only show active gallery items
        if ($gallery->status !== 'active') {
            abort(404);
        }

        // Get related galleries (previous and next)
        $previous = Gallery::active()
            ->where('id', '<', $gallery->id)
            ->orderBy('id', 'desc')
            ->first();

        $next = Gallery::active()
            ->where('id', '>', $gallery->id)
            ->orderBy('id', 'asc')
            ->first();

        // Load comments for this gallery item
        $comments = $gallery->comments;

        return view('pages.public.gallery-show', compact('gallery', 'previous', 'next', 'comments'));
    }

    /**
     * Store a new comment for a gallery item
     */
    public function storeComment(Request $request, Gallery $gallery)
    {
        // Validate the comment data
        $validated = $request->validate([
            'commenter_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'comment' => 'required|string|max:2000',
        ]);

        // Only allow comments on active gallery items
        if ($gallery->status !== 'active') {
            return back()->with('error', 'Comments are not allowed on this gallery item.');
        }

        // Create the comment
        $comment = new GalleryComment($validated);
        $comment->gallery_id = $gallery->id;
        $comment->save();

        return back()->with('success', 'Your comment has been submitted successfully!');
    }
}
