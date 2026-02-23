<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of gallery items
     */
    public function index()
    {
        $galleries = Gallery::withCount('comments')
            ->with('uploader')
            ->orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.gallery.index', compact('galleries'));
    }

    /**
     * Show the form for creating a new gallery item
     */
    public function create()
    {
        return view('pages.gallery.create');
    }

    /**
     * Store a newly created gallery item
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->move(public_path('uploads/gallery'), $imageName);
            
            // Store relative path
            $relativePath = 'uploads/gallery/' . $imageName;

            // Create gallery item
            $gallery = Gallery::create([
                'image_path' => $relativePath,
                'title' => $request->title,
                'caption' => $request->caption,
                'status' => $request->status,
                'display_order' => $request->display_order ?? 0,
                'uploaded_by' => Auth::id(),
            ]);

            return redirect()->route('admin.gallery.index')->with('success', 'Gallery item uploaded successfully!');
        }

        return redirect()->back()->with('error', 'Image upload failed.');
    }

    /**
     * Display the specified gallery item
     */
    public function show(Gallery $gallery)
    {
        // Load comments with all details for admin view
        $comments = $gallery->comments()->orderBy('created_at', 'desc')->get();
        
        return view('pages.gallery.show', compact('gallery', 'comments'));
    }

    /**
     * Show the form for editing the specified gallery item
     */
    public function edit(Gallery $gallery)
    {
        return view('pages.gallery.edit', compact('gallery'));
    }

    /**
     * Update the specified gallery item
     */
    public function update(Request $request, Gallery $gallery)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // Handle image update if new image is uploaded
        if ($request->hasFile('image')) {
            // Delete old image
            if ($gallery->image_path && file_exists(public_path($gallery->image_path))) {
                unlink(public_path($gallery->image_path));
            }

            // Upload new image
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/gallery'), $imageName);
            $gallery->image_path = 'uploads/gallery/' . $imageName;
        }

        // Update gallery item
        $gallery->update([
            'title' => $request->title,
            'caption' => $request->caption,
            'status' => $request->status,
            'display_order' => $request->display_order ?? $gallery->display_order,
        ]);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item updated successfully!');
    }

    /**
     * Remove the specified gallery item
     */
    public function destroy(Gallery $gallery)
    {
        // Delete image file
        if ($gallery->image_path && file_exists(public_path($gallery->image_path))) {
            unlink(public_path($gallery->image_path));
        }

        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item deleted successfully!');
    }
}
