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
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('admin.gallery.index') }}"
                       class="text-primary hover:text-primary/80 transition-colors">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <h2 class="text-2xl font-bold text-slate-900">Gallery Item Details</h2>
                </div>
                <p class="text-slate-600 text-sm">{{ $gallery->title ?? 'Untitled Image' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.gallery.edit', $gallery->id) }}"
                   class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2 text-sm font-bold">
                    <span class="material-icons text-sm">edit</span>
                    Edit
                </a>
                <a href="{{ route('admin.gallery.index') }}"
                   class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-all flex items-center gap-2 text-sm font-bold">
                    <span class="material-icons text-sm">close</span>
                    Close
                </a>
            </div>
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

        <!-- Image and Details Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Image Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-primary/10 overflow-hidden shadow-sm">
                    <div class="aspect-video w-full bg-slate-100">
                        <img alt="{{ $gallery->title ?? 'Gallery Image' }}"
                             class="w-full h-full object-cover"
                             src="{{ asset($gallery->image_path) }}">
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-primary/10 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-icons text-primary">info</span>
                        Details
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Title</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $gallery->title ?? 'Untitled' }}</p>
                        </div>
                        @if($gallery->caption)
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Caption</p>
                                <p class="text-sm text-slate-700">{{ $gallery->caption }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Status</p>
                            <span class="px-2 py-1 {{ $gallery->status === 'active' ? 'bg-green-500' : 'bg-slate-400' }} text-white text-xs font-bold uppercase rounded">
                                {{ ucfirst($gallery->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Display Order</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $gallery->display_order }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Upload Date</p>
                            <p class="text-sm text-slate-700">{{ $gallery->created_at->format('M d, Y \a\t h:i A') }}</p>
                        </div>
                        @if($gallery->uploader)
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Uploaded By</p>
                                <p class="text-sm text-slate-700">{{ $gallery->uploader->full_name ?? $gallery->uploader->name ?? 'N/A' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="bg-white rounded-xl border border-primary/10 p-6 lg:p-8 shadow-sm">
            <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                <span class="material-icons text-primary">comment</span>
                Comments ({{ $comments->count() }})
            </h2>

            @if($comments->count() > 0)
                <div class="space-y-4">
                    @foreach($comments as $comment)
                        <div class="p-5 bg-background-light rounded-lg border border-primary/10 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                                        <span class="material-icons text-primary text-lg">person</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $comment->commenter_name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $comment->created_at->format('M d, Y \a\t h:i A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-slate-700 leading-relaxed ml-13 mb-3">{{ $comment->comment }}</p>
                            <div class="ml-13 space-y-2">
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <span class="material-icons text-sm text-primary">phone</span>
                                    <span class="font-medium">Phone:</span>
                                    <span>{{ $comment->phone_number }}</span>
                                </div>
                                @if($comment->email)
                                    <div class="flex items-center gap-2 text-sm text-slate-600">
                                        <span class="material-icons text-sm text-primary">email</span>
                                        <span class="font-medium">Email:</span>
                                        <span>{{ $comment->email }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-slate-500">
                    <span class="material-icons text-4xl mb-3 text-slate-300">comment</span>
                    <p class="font-medium">No comments yet for this gallery item.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
