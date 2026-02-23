@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Generate Word Document Report
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="arrow-left"></i>
                Back to Reports
            </a>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">Performance Assessment Report (Word Document)</div>
                </div>
                <form action="{{ route('reports.word.generate') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-12 gap-4 gap-y-3 mt-3">
                        <div class="col-span-12 sm:col-span-6">
                            <label for="sector_id" class="form-label">Select Sector / MDA <span class="text-danger">*</span></label>
                            <select name="sector_id" id="sector_id" class="form-control" required>
                                <option value="">-- Select Sector --</option>
                                @foreach($sectors as $s)
                                    <option value="{{ $s->id }}" {{ ($selectedSector && $selectedSector->id == $s->id) ? 'selected' : '' }}>
                                        {{ $s->sector_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sector_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" id="year" value="{{ $year }}" class="form-control"
                                   min="2020" max="2030" required>
                            @error('year')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <div class="border-t border-slate-200/60 dark:border-darkmode-400 mt-5 pt-5">
                                <h3 class="text-lg font-medium mb-4">Additional Information</h3>
                                <p class="text-slate-500 mb-4">Fill in the fields below. Data from the system will be automatically included in the report.</p>
                            </div>
                        </div>

                        <div class="col-span-12">
                            <label for="observations" class="form-label">Observations</label>
                            <textarea name="observations" id="observations" class="form-control" rows="5" 
                                      placeholder="Enter your observations about the sector's performance..."></textarea>
                            <small class="text-slate-500">This will be included in Section C of the report.</small>
                            @error('observations')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <label for="recommendations" class="form-label">Recommendations on specific outputs / results that needs focused on</label>
                            <textarea name="recommendations" id="recommendations" class="form-control" rows="5" 
                                      placeholder="Enter your recommendations..."></textarea>
                            <small class="text-slate-500">This will be included in Section C of the report.</small>
                            @error('recommendations')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <div class="border-t border-slate-200/60 dark:border-darkmode-400 mt-5 pt-5">
                                <h3 class="text-lg font-medium mb-4">Signatures</h3>
                            </div>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="pdcu_coordinator_signature" class="form-label">PDCU Coordinator Signature</label>
                            <input type="text" name="pdcu_coordinator_signature" id="pdcu_coordinator_signature" 
                                   class="form-control" placeholder="Enter name or leave blank">
                            @error('pdcu_coordinator_signature')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="pdcu_coordinator_date" class="form-label">PDCU Coordinator Date</label>
                            <input type="date" name="pdcu_coordinator_date" id="pdcu_coordinator_date" 
                                   class="form-control">
                            @error('pdcu_coordinator_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="sector_facilitator_signature" class="form-label">Sector / MDA Facilitator Signature</label>
                            <input type="text" name="sector_facilitator_signature" id="sector_facilitator_signature" 
                                   class="form-control" placeholder="Enter name or leave blank">
                            @error('sector_facilitator_signature')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="sector_facilitator_date" class="form-label">Sector / MDA Facilitator Date</label>
                            <input type="date" name="sector_facilitator_date" id="sector_facilitator_date" 
                                   class="form-control">
                            @error('sector_facilitator_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-span-12 mt-5">
                            <div class="flex justify-end">
                                <button type="submit" class="btn btn-primary w-52">
                                    <i class="w-4 h-4 mr-2" data-lucide="file-text"></i>
                                    Generate Word Document
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-3 alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
