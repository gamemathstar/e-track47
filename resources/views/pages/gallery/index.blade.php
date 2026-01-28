@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Gallery Management
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('admin.gallery.create') }}" class="btn btn-primary shadow-md mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="plus"></i>
                Upload New Image
            </a>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible show flex items-center mb-2" role="alert">
                    <i data-lucide="check-circle" class="w-6 h-6 mr-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible show flex items-center mb-2" role="alert">
                    <i data-lucide="alert-circle" class="w-6 h-6 mr-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            <div class="box p-5">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="whitespace-nowrap">Image</th>
                                <th class="whitespace-nowrap">Title</th>
                                <th class="whitespace-nowrap">Caption</th>
                                <th class="whitespace-nowrap">Status</th>
                                <th class="whitespace-nowrap">Display Order</th>
                                <th class="whitespace-nowrap">Uploaded By</th>
                                <th class="whitespace-nowrap">Date</th>
                                <th class="whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($galleries as $gallery)
                                <tr>
                                    <td>
                                        <img src="{{ asset($gallery->image_path) }}" 
                                             alt="{{ $gallery->title ?? 'Gallery Image' }}" 
                                             class="w-20 h-20 object-cover rounded">
                                    </td>
                                    <td>{{ $gallery->title ?? '-' }}</td>
                                    <td>
                                        <div class="max-w-xs truncate">
                                            {{ Str::limit($gallery->caption, 50) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $gallery->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ ucfirst($gallery->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $gallery->display_order }}</td>
                                    <td>{{ $gallery->uploader->name ?? 'N/A' }}</td>
                                    <td>{{ $gallery->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.gallery.edit', $gallery->id) }}" 
                                               class="btn btn-sm btn-secondary">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            <form action="{{ route('admin.gallery.destroy', $gallery->id) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this image?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i data-lucide="trash" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-10">
                                        <div class="text-gray-500">
                                            <i data-lucide="image" class="w-16 h-16 mx-auto mb-4"></i>
                                            <p>No gallery items found. Upload your first image!</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($galleries->hasPages())
                    <div class="mt-5">
                        {{ $galleries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
