@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#008751",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Public Sans"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-icons {
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }

        .content {
            font-family: 'Public Sans', sans-serif;
        }
    </style>
@endsection

@section('content')
    <div class="p-6 space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Gallery Management</h2>
                <p class="text-slate-600 text-sm mt-1">Showing {{ $galleries->total() }} gallery items</p>
            </div>
            <a href="{{ route('admin.gallery.create') }}"
               class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2 text-sm font-bold">
                <span class="material-icons text-sm">add_photo_alternate</span>
                Upload New Image
            </a>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                <span class="material-icons text-green-600">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
                <span class="material-icons text-red-600">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Gallery Grid -->
        @if($galleries->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach($galleries as $gallery)
                    <div class="group bg-white rounded-xl border border-primary/10 overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="relative aspect-video overflow-hidden bg-slate-100">
                            <img alt="{{ $gallery->title ?? 'Gallery Image' }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 src="{{ asset($gallery->image_path) }}">
                            <div class="absolute top-2 left-2 right-2 flex justify-between items-start opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="bg-black/50 text-white p-1.5 rounded backdrop-blur-sm shadow-sm">
                                    <span class="material-icons text-sm">drag_indicator</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('admin.gallery.edit', $gallery->id) }}"
                                       class="bg-black/50 text-white p-1.5 rounded backdrop-blur-sm shadow-sm hover:bg-primary transition-colors">
                                        <span class="material-icons text-sm">edit</span>
                                    </a>
                                    <form action="{{ route('admin.gallery.destroy', $gallery->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this image?');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-black/50 text-white p-1.5 rounded backdrop-blur-sm shadow-sm hover:bg-red-600 transition-colors">
                                            <span class="material-icons text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="absolute bottom-2 left-2">
                                <span class="px-2 py-1 {{ $gallery->status === 'active' ? 'bg-green-500' : 'bg-slate-400' }} text-white text-[10px] font-bold uppercase rounded tracking-wider shadow-lg">
                                    {{ ucfirst($gallery->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-slate-900 truncate">{{ $gallery->title ?? 'Untitled' }}</h3>
                            @if($gallery->caption)
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ Str::limit($gallery->caption, 60) }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-primary/10">
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-500 font-medium">{{ $gallery->created_at->format('M d, Y') }}</span>
                                    @if($gallery->uploader)
                                        <span class="text-[10px] text-slate-500 mt-0.5">By {{ $gallery->uploader->full_name ?? $gallery->uploader->name ?? 'N/A' }}</span>
                                    @elseif($gallery->uploaded_by)
                                        <span class="text-[10px] text-slate-500 mt-0.5">By User #{{ $gallery->uploaded_by }}</span>
                                    @else
                                        <span class="text-[10px] text-slate-500 mt-0.5">By Unknown</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.gallery.show', $gallery->id) }}"
                                       class="text-[10px] px-2 py-0.5 bg-primary/10 text-primary font-semibold rounded hover:bg-primary hover:text-white transition-colors flex items-center gap-1">
                                        <span class="material-icons text-xs">comment</span>
                                        {{ $gallery->comments_count ?? 0 }} comments
                                    </a>
                                    <span class="text-[10px] px-2 py-0.5 bg-slate-100 text-slate-600 font-semibold rounded">
                                        Order: {{ $gallery->display_order }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($galleries->hasPages())
                <div class="mt-6 flex items-center justify-center">
                    {{ $galleries->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-xl border border-primary/10 p-12 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-3xl text-primary">collections</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">No Gallery Items Found</h3>
                <p class="text-sm text-slate-600 mb-6">Get started by uploading your first gallery image.</p>
                <a href="{{ route('admin.gallery.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all text-sm font-bold">
                    <span class="material-icons text-sm">add_photo_alternate</span>
                    Upload New Image
                </a>
            </div>
        @endif
    </div>
@endsection
