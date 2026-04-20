@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto ml-3">Final coordinator review</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            <div class="box p-5 rounded-md">
                <p class="text-slate-600 dark:text-slate-300">
                    Submissions listed here were accepted by a Facilitator (after Sector Head approval) and are awaiting your final approval or rejection.
                </p>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            <div class="rounded-md">
                @if($performanceTrackings->count())
                    <table class="table table-report mt-2">
                        <thead>
                        <tr>
                            <th>MDA/Sector</th>
                            <th>Submissions</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($performanceTrackings as $tracking)
                            <tr>
                                <td>{{ $tracking->sector_name }}</td>
                                <td>{{ $tracking->count }} awaiting final review</td>
                                <td>
                                    <a href="{{ route('delivery.coordinator.final-review.sector', $tracking->id) }}"
                                       class="btn btn-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="box p-8 text-center text-slate-500">
                        <p>There are no facilitator-approved submissions awaiting final coordinator review.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
