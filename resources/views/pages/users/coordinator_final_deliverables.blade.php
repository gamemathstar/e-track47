@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <div class="mr-auto ml-3">
            <a href="{{ route('delivery.coordinator.final-review.sector', $commitment->sector_id) }}"
               class="text-primary text-sm hover:underline">← Back to commitments</a>
            <h2 class="text-lg font-medium mt-2">Final coordinator review</h2>
        </div>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            <div class="box p-5 rounded-md">
                <div class="text-primary text-2xl">{{ $commitment->name }}</div>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            @if($performanceTrackings->count())
                <table class="table table-report mt-2">
                    <thead>
                    <tr>
                        <th>Deliverable</th>
                        <th>Submissions</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($performanceTrackings as $row)
                        <tr>
                            <td>{{ $row->deliverable }}</td>
                            <td>{{ $row->count }} awaiting final review</td>
                            <td>
                                <a href="{{ route('delivery.coordinator.final-review.deliverable', $row->id) }}"
                                   class="btn btn-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="box p-8 text-center text-slate-500">
                    <p>No deliverables in this commitment have submissions awaiting your final review.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
