<table class="table">
    @foreach($files as $file)
        <tr>
            <td style="width: 40%">
                @if(in_array($file->type, ['jpg', 'jpeg', 'png','JPG','JPEG','PNG']))
                    <img src="{{ Storage::url($file->path) }}" class="mt-2 max-w-full h-32 object-contain">
                @elseif(in_array($file->type,['pdf','PDF']))
                    <iframe src="{{ Storage::url($file->path) }}" class="mt-2 w-full h-32"></iframe>
                @else
                    <div class="mt-2 h-32 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500">No preview available</span>
                    </div>
                @endif
            </td>
            <td>
                <p class="font-semibold">{{ $file->name }}</p>
                <p class="text-sm text-gray-600">Type: {{ $file->type }}</p>
                <p class="text-sm text-gray-600">Size: {{ round($file->size/1024, 2) }} KB</p>
                <a href="" class="btn btn-primary mt-2">
                    Download
                </a>
            </td>
        </tr>
    @endforeach
</table>
