<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
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

        return view('pages.public.gallery-show', compact('gallery', 'previous', 'next'));
    }
}
