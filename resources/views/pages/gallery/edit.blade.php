@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Edit Gallery Image
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-secondary shadow-md mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="arrow-left"></i>
                Back to Gallery
            </a>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-8">
            <div class="box p-5">
                <form action="{{ route('admin.gallery.update', $gallery->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label for="image" class="form-label">Image</label>
                            <input type="file" 
                                   id="image" 
                                   name="image" 
                                   class="form-control" 
                                   accept="image/*">
                            <div class="text-xs text-gray-500 mt-1">
                                Leave empty to keep current image. Supported formats: JPEG, PNG, JPG, GIF, WEBP (Max: 5MB)
                            </div>
                            @error('image')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   class="form-control" 
                                   placeholder="Enter image title (optional)"
                                   value="{{ old('title', $gallery->title) }}">
                            @error('title')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <label for="caption" class="form-label">Caption/Description</label>
                            <textarea id="caption" 
                                      name="caption" 
                                      class="form-control" 
                                      rows="4"
                                      placeholder="Enter image description or caption (optional)">{{ old('caption', $gallery->caption) }}</textarea>
                            <div class="text-xs text-gray-500 mt-1">
                                Maximum 1000 characters
                            </div>
                            @error('caption')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="active" {{ old('status', $gallery->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $gallery->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" 
                                   id="display_order" 
                                   name="display_order" 
                                   class="form-control" 
                                   placeholder="0"
                                   value="{{ old('display_order', $gallery->display_order) }}"
                                   min="0">
                            @error('display_order')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.gallery.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="w-4 h-4 mr-2" data-lucide="save"></i>
                                    Update Image
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="box p-5">
                <h3 class="text-lg font-medium mb-4">Current Image</h3>
                <img src="{{ asset($gallery->image_path) }}" 
                     alt="{{ $gallery->title ?? 'Gallery Image' }}" 
                     class="w-full rounded-lg mb-4">
                <div class="text-sm text-gray-600">
                    <p><strong>Uploaded:</strong> {{ $gallery->created_at->format('M d, Y') }}</p>
                    @if($gallery->uploader)
                        <p><strong>By:</strong> {{ $gallery->uploader->name }}</p>
                    @endif
                </div>
            </div>

            <div class="box p-5 mt-5">
                <h3 class="text-lg font-medium mb-4">New Image Preview</h3>
                <div id="imagePreview" class="hidden">
                    <img id="previewImg" src="" alt="Preview" class="w-full rounded-lg mb-4">
                </div>
                <div id="noPreview" class="text-center text-gray-500 py-10">
                    <i data-lucide="image" class="w-16 h-16 mx-auto mb-2"></i>
                    <p>Select a new image to preview</p>
                </div>
            </div>
        </div>
    </div>

    @section('js')
    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                    document.getElementById('noPreview').classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imagePreview').classList.add('hidden');
                document.getElementById('noPreview').classList.remove('hidden');
            }
        });
    </script>
    @endsection
@endsection
