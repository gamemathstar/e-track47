@extends("layouts.app")

@section('content')
    <div class="intro-y flex items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sector: {{ $sector->sector_name }}
        </h2>
    </div>
    @php

        @endphp
        <!-- BEGIN: Profile Info -->
    <div class="intro-y box px-5 pt-5 mt-5">
        <div class="flex flex-col lg:flex-row border-b border-slate-200/60 dark:border-darkmode-400 pb-5 -mx-5">
            <div class="flex flex-1 px-5 items-center justify-center lg:justify-start">
                <div class="w-20 h-20 sm:w-24 sm:h-24 flex-none lg:w-32 lg:h-32 image-fit relative">
                    <img alt="Midone - HTML Admin Template" class="rounded-full"
                         src="{{asset('dist/images/profile-12.jpg')}}">
                </div>
                <div class="ml-5">
                    <div
                        class="w-24 sm:w-40 truncate sm:whitespace-normal font-medium text-lg">{{$head?$head->full_name:"No head"}}</div>
                    <div class="text-slate-500">{{$head?$head->username:"-----"}}</div>
                </div>
            </div>
            <div
                class="mt-6 lg:mt-0 flex-1 px-5 border-l border-r border-slate-200/60 dark:border-darkmode-400 border-t lg:border-t-0 pt-5 lg:pt-0">
                <div class="font-medium text-center lg:text-left lg:mt-3">Contact Details</div>
                <div class="flex flex-col justify-center items-center lg:items-start mt-4">
                    <div class="truncate sm:whitespace-normal flex items-center"><i data-lucide="mail"
                                                                                    class="w-4 h-4 mr-2"></i> {{$head?$head->email:'----'}}
                    </div>
                    <div class="truncate sm:whitespace-normal flex items-center mt-3"><i data-lucide="phone"
                                                                                         class="w-4 h-4 mr-2"></i> {{$head?$head->phone_number:'----'}}
                    </div>
                    <div class="truncate sm:whitespace-normal flex items-center mt-3"><i data-lucide="home"
                                                                                         class="w-4 h-4 mr-2"></i> {{$head?\App\Models\User::find($head->user_id)->role():'----'}}
                    </div>
                </div>
            </div>
            <div
                class="mt-6 lg:mt-0 flex-1 flex items-center justify-center px-5 border-t lg:border-0 border-slate-200/60 dark:border-darkmode-400 pt-5 lg:pt-0">
                <div class="text-center rounded-md w-40 py-3">
                    <div class="font-medium text-primary text-xl">{{count($commitments)}}</div>
                    <div class="text-slate-500">Commitments</div>
                </div>
                <div class="text-center rounded-md w-40 py-3">
                    <div class="font-medium text-primary text-xl">1k</div>
                    <div class="text-slate-500">Deliverables</div>
                </div>
            </div>
        </div>
        {{--        <ul class="nav nav-link-tabs flex-col sm:flex-row justify-center lg:justify-start text-center" role="tablist" >--}}
        {{--            <li id="profile-tab" class="nav-item" role="presentation">--}}
        {{--                <a href="javascript:;" class="nav-link py-4 flex items-center active" data-tw-target="#profile" aria-controls="profile" aria-selected="true" role="tab" > <i class="w-4 h-4 mr-2" data-lucide="user"></i> Profile </a>--}}
        {{--            </li>--}}
        {{--            <li id="account-tab" class="nav-item" role="presentation">--}}
        {{--                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#account" aria-selected="false" role="tab" > <i class="w-4 h-4 mr-2" data-lucide="shield"></i> Account </a>--}}
        {{--            </li>--}}
        {{--            <li id="change-password-tab" class="nav-item" role="presentation">--}}
        {{--                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#change-password" aria-selected="false" role="tab" > <i class="w-4 h-4 mr-2" data-lucide="lock"></i> Change Password </a>--}}
        {{--            </li>--}}
        {{--            <li id="settings-tab" class="nav-item" role="presentation">--}}
        {{--                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#settings" aria-selected="false" role="tab" > <i class="w-4 h-4 mr-2" data-lucide="settings"></i> Settings </a>--}}
        {{--            </li>--}}
        {{--        </ul>--}}
    </div>
    <!-- END: Profile Info -->
    <div class="tab-content mt-5">
        <div id="profile" class="tab-pane active" role="tabpanel" aria-labelledby="profile-tab">
            <div class="grid grid-cols-12 gap-6">
                <!-- BEGIN: Latest Uploads -->
                <div class="intro-y box col-span-12 lg:col-span-6">
                    <div
                        class="flex items-center px-5 py-5 sm:py-3 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="font-medium text-base mr-auto">
                            Latest Uploads
                        </h2>
                        <div class="dropdown ml-auto sm:hidden">
                            <a class="dropdown-toggle w-5 h-5 block" href="javascript:;" aria-expanded="false"
                               data-tw-toggle="dropdown"> <i data-lucide="more-horizontal"
                                                             class="w-5 h-5 text-slate-500"></i> </a>
                            <div class="dropdown-menu w-40">
                                <ul class="dropdown-content">
                                    <li><a href="javascript:;" class="dropdown-item">All Files</a></li>
                                </ul>
                            </div>
                        </div>
                        <button class="btn btn-outline-secondary hidden sm:flex" id="uploadDocsBtn">Upload Files
                        </button>
                    </div>
                    <div class="p-5">
                        @foreach($sector->files() as $file)
                            <div class="flex items-center mt-5">
                                <div class="file"><a href="" class="w-12 file__icon file__icon--directory"></a></div>
                                <div class="ml-4">
                                    <a class="font-medium" href="{{asset('storage/' .$file->url)}}"
                                       target="_blank">{{$file->title}}</a>
                                    <div class="text-slate-500 text-xs mt-0.5">

                                    </div>
                                </div>
                                <div class="dropdown ml-auto">
                                    <a class="dropdown-toggle w-5 h-5 block" href="javascript:;" aria-expanded="false"
                                       data-tw-toggle="dropdown"> <i data-lucide="more-horizontal"
                                                                     class="w-5 h-5 text-slate-500"></i> </a>
                                    <div class="dropdown-menu w-40">
                                        <ul class="dropdown-content">
                                            <li>
                                                <a href="" class="dropdown-item"> <i data-lucide="users"
                                                                                     class="w-4 h-4 mr-2"></i> Share
                                                    File </a>
                                            </li>
                                            <li>
                                                <a href="" class="dropdown-item"> <i data-lucide="trash"
                                                                                     class="w-4 h-4 mr-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- END: Latest Uploads -->
                <!-- BEGIN: Work In Progress -->
                <div class="intro-y box col-span-12 lg:col-span-6">
                    <div
                        class="flex items-center px-5 py-5 sm:py-0 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="font-medium text-base mr-auto">
                            Budgets
                        </h2>

                        <ul class="nav nav-link-tabs w-auto ml-auto hidden sm:flex" role="tablist">
                            {{--                            <li id="work-in-progress-new-tab" class="nav-item" role="presentation"> <a href="javascript:;" class="nav-link py-5 active" data-tw-target="#work-in-progress-new" aria-controls="work-in-progress-new" aria-selected="true" role="tab" > New </a> </li>--}}
                            <li id="work-in-progress-last-week-tab" class="nav-item" role="presentation"><a
                                    href="javascript:;" class="nav-link py-5"
                                    data-tw-target="#work-in-progress-last-week" aria-selected="false" role="tab"> </a>
                            </li>
                        </ul>

                        <button class="btn btn-outline-secondary hidden sm:flex" id="addBudgetBtn">Add Budget
                        </button>
                    </div>
                    <div class="p-5">
                        <div class="tab-content">
                            <div id="work-in-progress-new" class="tab-pane active" role="tabpanel"
                                 aria-labelledby="work-in-progress-new-tab">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>#</th>
                                            <th>Year</th>
                                            <th>Budget(&#8358;)</th>
                                            <th>%</th>
                                            <th>--</th>
                                        </tr>
                                        @foreach($sector->budgets() as $budget)
                                            <tr>
                                                <td>{{$loop->iteration}}</td>
                                                <td>{{$budget->year}}</td>
                                                <td>{{number_format($budget->allocation,2,'.')}}
                                                    /<strong> {{number_format($budget->sector_amount,2,'.')}}</strong>
                                                </td>
                                                <td>
                                                    @if($budget->allocation==0 || $budget->sector_amount==0)
                                                        0%
                                                    @else
                                                        {{ number_format(($budget->allocation/$budget->sector_amount) * 100,1)}}
                                                        %
                                                    @endif
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary-soft btn-sm comBudget"
                                                            sector-id="{{$budget->sector_id}}" year="{{$budget->year}}">
                                                        Commitment Budget
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END: Work In Progress -->
                <!-- BEGIN: Daily Sales -->
                <div class="intro-y box col-span-12 lg:col-span-6">
                    <div
                        class="flex items-center px-5 py-5 sm:py-3 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="font-medium text-base mr-auto">
                            Commitments
                        </h2>
                        <div class="dropdown ml-auto sm:hidden">
                            <a class="dropdown-toggle w-5 h-5 block" href="javascript:;" aria-expanded="false"
                               data-tw-toggle="dropdown"> <i data-lucide="more-horizontal"
                                                             class="w-5 h-5 text-slate-500"></i> </a>
                            <div class="dropdown-menu w-40">
                                <ul class="dropdown-content">
                                    <li>
                                        <a href="javascript:;" class="dropdown-item"> <i data-lucide="file"
                                                                                         class="w-4 h-4 mr-2"></i>
                                            Download Excel </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>
                    <div class="p-5">
                        @if($commitments->count())
                            @foreach($commitments as $commitment)
                                <div class="flex items-center">

                                    {{$loop->iteration}}.
                                    <a href="javascript:;" class="underline ml-1 commitments"
                                       com-id="{{$commitment->id}}">
                                        {{$commitment->title(48)}}
                                    </a>
                                </div>
                                <hr>
                            @endforeach
                        @else
                            <center>
                                Click <em class="text-success">Add New</em> to add commitments.
                            </center>
                        @endif
                    </div>
                </div>
                <!-- END: Daily Sales -->
                <!-- BEGIN: Latest Tasks -->
                <!-- END: Latest Tasks -->
                <!-- BEGIN: New Products -->
                <div class="intro-y box col-span-6">
                    <div class="flex items-center px-5 py-3 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="font-medium text-base mr-auto">

                        </h2>
                    </div>
                    <div id="new-products" class="tiny-slider py-5">
                        <div class="px-5">
                            <div class="box p-5 rounded-md" id="loadArea">

                            </div>
                        </div>
                    </div>
                </div>
                <!-- END: New Products --> <!-- BEGIN: New Products -->
                <div class="intro-y box col-span-12">
                    <div class="flex items-center px-5 py-3 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="font-medium text-base mr-auto">
                            Report
                        </h2>
                        <button data-carousel="new-products" data-target="prev"
                                class="tiny-slider-navigator btn btn-outline-secondary px-2 mr-2"><i
                                data-lucide="chevron-left" class="w-4 h-4"></i></button>
                        <button data-carousel="new-products" data-target="next"
                                class="tiny-slider-navigator btn btn-outline-secondary px-2"><i
                                data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    </div>
                    <div id="new-products" class="tiny-slider py-5">
                        <div class="px-5">
                            <div class="box p-5 rounded-md">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>Commitment</th>
                                        <th>Deliverable</th>
                                        <th>KPI</th>
                                        <th>Target ({{$lyear}})</th>
                                        @foreach ($years as $year)
                                            <th>{{ $year }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($commitments as $commitment)
                                        @php
                                            $rowspanCommitment = $commitment->deliverables->sum(function($deliverable) {
                                                return $deliverable->kpis->count();
                                            });
                                        @endphp

                                        @foreach ($commitment->deliverables as $indexDeliverable => $deliverable)
                                            @php
                                                $rowspanDeliverable = $deliverable->kpis->count();
                                            @endphp

                                            @foreach ($deliverable->kpis as $indexKPI => $kpi)
                                                <tr>
                                                    @if ($indexDeliverable === 0 && $indexKPI === 0)
                                                        <td rowspan="{{ $rowspanCommitment }}">
                                                            {{ $commitment->commitment_title }}
                                                        </td>
                                                    @endif

                                                    @if ($indexKPI === 0)
                                                        <td rowspan="{{ $rowspanDeliverable }}">
                                                            {{ $deliverable->deliverable_title }}
                                                        </td>
                                                    @endif

                                                    <td>
                                                        {{ \App\Models\Kpi::find($kpi->kpi_id)->kpi }}
                                                    </td>
                                                    <td>{{ $kpi->target }}</td>
                                                    <td>{{ $kpi->actual_value }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- END: New Products -->
            </div>
        </div>
    </div>

    <div id="comBudgetModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="table-responsive" id="comBudgetLoadArea">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" data-tw-dismiss="modal" class="btn btn-secondary" id="">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="uploadDocsModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <form action="{{ route("sectors.document.save") }}" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="sector_id" value="{{$sector->id}}">
                        <div>
                            <label for="regular-form-1" class="form-label">Document Title</label>
                            <input name="title" type="text" class="form-control" placeholder="Document Title">
                        </div>
                        <div class="mt-3">
                            <label for="regular-form-3" class="form-label">File</label>
                            <input name="image" type="file" class="form-control" placeholder="File">
                            <div class="form-help text-primary">
                                .jpeg| .png| .jpg
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="">Upload</button>
                        <button type="button" data-tw-dismiss="modal" class="btn btn-secondary" id="">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="addBudgetModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                {{--                <h1><strong>Add Budget</strong></h1>--}}
                {{--                <hr>--}}
                {{--                <br>--}}
                {{--                <br>--}}
                <form action="{{ route("sectors.budget.save") }}" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="sector_id" value="{{$sector->id}}">
                        <div>
                            <label for="regular-form-1" class="form-label">Amount</label>
                            <input name="amount" type="text" class="form-control" placeholder="Amount">
                        </div>
                        <div class="mt-3">
                            <label for="regular-form-3" class="form-label">Year</label>
                            <select name="year" class="form-control">
                                <option value=""> Select Year</option>
                                @foreach(range(date('Y') ,2020) as $year)
                                    <option value="{{$year}}">{{$year}}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="">Add Budget</button>
                        <button type="button" data-tw-dismiss="modal" class="btn btn-secondary" id="">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="next-overlapping-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h2>Add KPI for <strong class="font-bold" id="del-title"></strong></h2>
                    <input type="hidden" name="deliverable_id" value="" id="deliverable_id">
                    <hr>
                    <div class="text-warning mt-2" id="addKpiMsg">

                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">KPI</label>
                        <select name="" class="form-control form-control-rounded" id="kpi_id">
                            <option value="">Select KPI</option>
                            @foreach(\App\Models\Kpi::get() as $kpi)
                                <option value="{{$kpi->id}}">{{$kpi->kpi}} - {{$kpi->unit_of_measurement}}</option>
                            @endforeach
                        </select>

                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">Year</label>
                        <input id="year" type="number" class="form-control form-control-rounded" placeholder="Year"
                               name="year">
                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">Target</label>
                        <input id="target" type="text" class="form-control form-control-rounded" placeholder="Target"
                               name="target">
                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">Actual Value</label>
                        <input id="actual_value" type="text" class="form-control form-control-rounded"
                               placeholder="Actual Value" name="actual_value">
                    </div>
                    <hr>
                    <div class="mt-3 text-center">
                        <button class="btn btn-primary btn-sm btn-rounded" id="addEditDeliverableBtn">Save KPI</button>
                        <button type="button" data-tw-dismiss="modal" class="btn btn-secondary  btn-sm btn-rounded"
                                id="">Close
                        </button>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <div id="commitmentBudgetModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <form action="{{ route('commitments.budget.save') }}" method="post">
                        @csrf
                        <h2>Add Budget to Commitment <strong class="font-bold" id="del-title"></strong></h2>
                        {{--                        <input type="hidden" name="commitment_id" value="" id="commitment_budget_id">--}}
                        <input type="hidden" name="year" value="" id="year_budget">
                        <hr>
                        <div class="mt-3">
                            <label for="regular-form-2" class="form-label">Commitment</label>
                            <select name="commitment_id" id="" class="form-control">
                                <option value="">Select Commitment</option>
                                @foreach($commitments as $commitment)
                                    <option value="{{$commitment->id}}">{{$commitment->title(40)}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-3">
                            <label for="regular-form-2" class="form-label">Amount</label>
                            <input id="target" type="text" class="form-control form-control-rounded"
                                   placeholder="Target"
                                   name="amount">
                        </div>
                        <div class="mt-3 text-center">
                            <button class="btn btn-primary btn-sm btn-rounded" id="addEditDeliverableBtn">Save Amount
                            </button>
                            <button type="button" data-tw-dismiss="modal" class="btn btn-secondary  btn-sm btn-rounded"
                                    id="">Close
                            </button>
                        </div>
                    </form>


                </div>
            </div>
        </div>
    </div>


    <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{route('commitments.save')}}" method="post">
                    @csrf
                    <input type="hidden" name="sector_id" value="{{$sector->id}}">
                    <!-- BEGIN: Modal Header -->
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Add Commitment
                            to {{$sector->name}}</h2>

                    </div> <!-- END: Modal Header -->
                    <!-- BEGIN: Modal Body -->
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-12 sm:col-span-12">
                            <label for="modal-form-1" class="form-label">Commitment</label>
                            <input id="modal-form-1" type="text" class="form-control"
                                   name="commitment_title" required>
                        </div>
                        <div class="col-span-12 sm:col-span-12">
                            <label for="modal-form-2" class="form-label">Description</label>
                            <textarea name="description" id="" class="form-control" required></textarea>
                        </div>

                    </div> <!-- END: Modal Body -->
                    <!-- BEGIN: Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal"
                                class="btn btn-outline-secondary w-20 mr-1">Cancel
                        </button>
                        <button type="submit" class="btn btn-primary w-20">Save</button>
                    </div> <!-- END: Modal Footer -->
                </form>
            </div>
        </div>
    </div> <!-- END: Modal Content -->

    <div id="addDeliverablesModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{route('deliverable.save')}}" method="post">
                    @csrf
                    <input type="hidden" name="commitment_id" id="addDeliverableBtnComId" value="">
                    <!-- BEGIN: Modal Header -->
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">
                            Add Deliverable
                        </h2>

                    </div> <!-- END: Modal Header -->
                    <!-- BEGIN: Modal Body -->
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-12 sm:col-span-12">
                            <label for="modal-form-1" class="form-label">Deliverable</label>
                            <input id="deliverable_title" type="text" class="form-control"
                                   name="deliverable_title" required>
                        </div>
                        <div class="col-span-12 sm:col-span-12">
                            <label for="modal-form-2" class="form-label">Description</label>
                            <textarea name="description" id="del_deliverable_title" class="form-control"
                                      required></textarea>
                        </div>
                    </div> <!-- END: Modal Body -->
                    <!-- BEGIN: Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal"
                                class="btn btn-outline-secondary w-20 mr-1">Cancel
                        </button>
                        <button type="submit" class="btn btn-primary w-20">Save</button>
                    </div> <!-- END: Modal Footer -->
                </form>
            </div>
        </div>
    </div> <!-- END: Modal Content -->

    <div id="editCommitmentModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{route('commitments.update')}}" method="post">
                    @csrf
                    <input type="hidden" name="commitment_id" id="comm-id" value="">
                    <!-- BEGIN: Modal Header -->
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Edit Commitment
                        </h2>

                    </div> <!-- END: Modal Header -->
                    <!-- BEGIN: Modal Body -->
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-12 sm:col-span-12">
                            <label for="modal-form-1" class="form-label">Commitment</label>
                            <input type="text" class="form-control"
                                   name="commitment_title" id="comm-title" required>
                        </div>
                        <div class="col-span-12 sm:col-span-12">
                            <label for="modal-form-2" class="form-label">Description</label>
                            <textarea name="description" id="comm-desc" class="form-control"
                                      required></textarea>
                        </div>
                    </div> <!-- END: Modal Body -->
                    <!-- BEGIN: Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal"
                                class="btn btn-outline-secondary w-20 mr-1">Cancel
                        </button>
                        <button type="submit" class="btn btn-primary w-20">Save</button>
                    </div> <!-- END: Modal Footer -->
                </form>
            </div>
        </div>
    </div> <!-- END: Modal Content -->

    <div id="viewDeliverablesModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-body" id="viewDeliverableLoadAre">

                </div>

                <div class="modal-footer">
                    <button type="button" data-tw-dismiss="modal"
                            class="btn btn-outline-secondary w-20 mr-1">Close
                    </button>
                </div>
            </div>
        </div>
    </div> <!-- END: Modal Content -->

    <div id="sectorHeadModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-body">

                    <h1>Sector Heads</h1>
                    <div class="overflow-x-auto">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th class="whitespace-nowrap">#</th>
                                <th class="whitespace-nowrap">First Name</th>
                                <th class="whitespace-nowrap">Last Name</th>
                                <th class="whitespace-nowrap">Username</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1</td>
                                <td>Angelina</td>
                                <td>Jolie</td>
                                <td>@angelinajolie</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Brad</td>
                                <td>Pitt</td>
                                <td>@bradpitt</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Charlie</td>
                                <td>Hunnam</td>
                                <td>@charliehunnam</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" data-tw-dismiss="modal"
                            class="btn btn-outline-secondary w-20 mr-1">Close
                    </button>
                </div>
            </div>
        </div>
    </div> <!-- END: Modal Content -->

@endsection

@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    {{--    comBudget--}}
    <script>
        $(function () {
            const comBudget = tailwind.Modal.getOrCreateInstance(document.querySelector("#comBudgetModal"));
            const uploadDocsModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#uploadDocsModal"));
            const addBudgetModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#addBudgetModal"));

            const editCommitmentModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#editCommitmentModal"));
            const addDeliverablesModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#addDeliverablesModal"));
            const viewDeliverablesModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#viewDeliverablesModal"));
            const addKPIModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#next-overlapping-modal-preview"));
            const commitmentBudgetModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#commitmentBudgetModal"));


            $("body").on('click', '.commitmentBudgetBtn', function () {
                year_budget = $(this).attr('year')
                $("#year_budget").val(year_budget);
                commitmentBudgetModal.show();
            });
            $("#addBudgetBtn").on('click', function () {
                addBudgetModal.show();
            });
            $("#uploadDocsBtn").on('click', function () {
                uploadDocsModal.show();
            });
            $(".comBudget").on('click', function () {
                sector_id = $(this).attr('sector-id');
                year = $(this).attr('year');
                $("#comBudgetLoadArea").html("<center><h1>Loading...</h1></center>");
                $("#comBudgetLoadArea").load("{{route('sectors.budget')}}?sector_id=" + sector_id + "&year=" + year, function () {
                    comBudget.show();
                });

            });


            $(".commitments").on('click', function (e) {
                // $("#loadArea").load("commitments.deliverables");
                loadCommitments($(this).attr('com-id'));
            });

            $('body').on('click', '#editCommitmentBtn', function () {
                com_id = $(this).attr('com-id');
                com_title = $(this).attr('com-title');
                com_description = $(this).attr('com-description');
                $("#comm-title").val(com_title);
                $("#comm-desc").text(com_description);
                $("#comm-id").val(com_id);
                editCommitmentModal.show();
            });

            $('body').on('click', '#addDeliverableBtn', function () {
                com_id = $(this).attr('com-id');
                $("#addDeliverableBtnComId").val(com_id);
                addDeliverablesModal.show();
            });

            $('body').on('click', '.viewDeliverable', function () {
                // viewDeliverableLoadAre
                $("#viewDeliverableLoadAre").load("{{route('deliverable.view')}}?id=" + $(this).attr('del-id'));
                viewDeliverablesModal.show();
            });

            $('body').on('click', '#addEditDeliverableBtn', function () {

                $('#deliverable_id').val($('#addKpiModalBtn').attr('del-id'));
                year = $('#year').val();
                actual_value = $('#actual_value').val();
                target = $('#target').val();
                measurement_unit = $('#measurement_unit').val();
                kpi_id = $('#kpi_id').val();
                deliverable_id = $('body').find('#addKpiModalBtn').attr('del-id');
                $.ajax({
                    type: 'get',
                    url: '{{route('deliverable.add.kpi')}}',
                    data: {
                        deliverable_id: deliverable_id,
                        kpi_id: kpi_id,
                        target: target,
                        actual_value: actual_value,
                        year: year
                    },
                    success: function (data) {
                        console.log(data);
                        if (data.status == 1) {
                            addKPIModal.hide();
                            $("#viewDeliverableLoadAre").load("{{route('deliverable.view')}}?id=" + deliverable_id);
                        } else {
                            $("#addKpiMsg").html(data.message);
                        }
                    }
                });
            });
        });


        function loadCommitments(id) {
            $.ajax({
                type: 'Post',
                url: "{{route("commitments.deliverables",[''])}}/" + id,
                data: {_token: '{{ csrf_token() }}'},
                success: function (data) {
                    $("#loadArea").html(data);
                }
            });
        }
    </script>
@endsection
