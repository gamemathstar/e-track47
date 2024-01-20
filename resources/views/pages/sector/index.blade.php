@extends('layouts.app')
@section('content')
    @php
        $user = Auth::user();
    @endphp
    <h2 class="intro-y text-lg font-medium mt-10">
        Manage Sectors
    </h2>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 flex flex-wrap sm:flex-nowrap items-center mt-2">
            <button class="btn btn-primary shadow-md mr-2" data-tw-toggle="modal"
                    data-tw-target="#header-footer-modal-preview">Add Sector
            </button>

            <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{route('sectors.save')}}" method="post">
                            @csrf
                            <!-- BEGIN: Modal Header -->
                            <div class="modal-header">
                                <h2 class="font-medium text-base mr-auto">Add Sector</h2>

                            </div> <!-- END: Modal Header -->
                            <!-- BEGIN: Modal Body -->
                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="modal-form-1" class="form-label">Sector Name</label>
                                    <input id="modal-form-1" type="text" class="form-control" name="sector_name">
                                </div>
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="modal-form-2" class="form-label">Description</label>
                                    <textarea name="description" id="" class="form-control"></textarea>
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

        </div>

        <!-- BEGIN: Data List -->
        <div class="intro-y col-span-12 overflow-auto lg:overflow-visible">
            <table class="table table-report -mt-2">
                <thead>
                <tr>
                    <th class="whitespace-nowrap">#</th>
                    <th class="whitespace-nowrap">SECTOR NAME</th>
                    <th class="whitespace-nowrap">DESCRIPTION</th>
                    <th class="whitespace-nowrap">HEAD</th>
                    <th class="text-center whitespace-nowrap">ACTIONS</th>
                </tr>
                </thead>
                <tbody>
                @foreach($sectors as $sector)
                    <tr class="intro-x">
                        <td class="w-40">
                            {{$loop->iteration}}
                        </td>
                        <td>
                            <a
                                href="{{ $user->role==0?route("sectors.view",[$sector->id]):route("sectors.show",[$sector->id]) }}"
                                class="font-medium whitespace-nowrap">{{$sector->sector_name}}</a>
                            <div class="text-slate-500 text-xs whitespace-nowrap mt-0.5"></div>
                        </td>
                        <td class="text">
                            {{$sector->description}}
                        </td>
                        <td class="text">
                            @php
                                $head =  $sector->head();
                            @endphp
                            @if($head)
                                <a href="{{route('users.view',[$head->user_id])}}" class="text-primary/80">
                                    {{$head->full_name}}
                                </a>
                            @else
                                ---
                            @endif

                        </td>
                        <td class="table-report__action w-56">
                            <div class="flex justify-center items-center">
                                <a class="flex items-center mr-3" href="javascript:;" data-tw-toggle="modal"
                                   data-tw-target="#header-footer-modal-preview{{$sector->id}}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="check-square" data-lucide="check-square"
                                         class="lucide lucide-check-square w-4 h-4 mr-1">
                                        <polyline points="9 11 12 14 22 4"></polyline>
                                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                                    </svg>
                                    Edit
                                </a>
                                <a class="flex items-center mr-3  items-center text-success"
                                   href="{{route('sectors.view',[$sector->id])}}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="eye" data-lucide="eye"
                                         class="lucide lucide-eye block mx-auto">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    View
                                </a>
                                <a class="flex items-center text-danger" href="javascript:;" data-tw-toggle="modal"
                                   data-tw-target="#delete-modal-preview{{$sector->id}}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="trash-2" data-lucide="trash-2"
                                         class="lucide lucide-trash-2 w-4 h-4 mr-1">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path
                                            d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                    Delete
                                </a>
                            </div>

                            <div id="header-footer-modal-preview{{$sector->id}}" class="modal" tabindex="-1"
                                 aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{route('sectors.update')}}" method="post">
                                            @csrf
                                            <input type="hidden" name="id" value="{{$sector->id}}">
                                            <!-- BEGIN: Modal Header -->
                                            <div class="modal-header">
                                                <h2 class="font-medium text-base mr-auto">Edit Sector
                                                    ({{$sector->sector_name}})</h2>

                                            </div> <!-- END: Modal Header -->
                                            <!-- BEGIN: Modal Body -->
                                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                                <div class="col-span-12 sm:col-span-12">
                                                    <label for="modal-form-1" class="form-label">Sector Name</label>
                                                    <input id="modal-form-1" type="text" class="form-control"
                                                           value="{{$sector->sector_name}}" name="sector_name">
                                                </div>
                                                <div class="col-span-12 sm:col-span-12">
                                                    <label for="modal-form-2" class="form-label">Description</label>
                                                    <textarea name="description" id=""
                                                              class="form-control">{{$sector->description}}</textarea>
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
                            <div id="delete-modal-preview{{$sector->id}}" class="modal" tabindex="-1"
                                 aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-body p-0">
                                            <div class="p-5 text-center"><i data-lucide="x-circle"
                                                                            class="w-16 h-16 text-danger mx-auto mt-3"></i>
                                                <div class="text-3xl mt-5">Are you sure?</div>
                                                <div class="text-slate-500 mt-2">Do you really want to delete this
                                                    sector? <br>
                                                    <strong>{{$sector->sector_name}}</strong>
                                                </div>
                                            </div>
                                            <div class="px-5 pb-8 text-center">
                                                <button type="button" data-tw-dismiss="modal"
                                                        class="btn btn-outline-secondary w-24 mr-1">Cancel
                                                </button>
                                                <a href="{{ route('sectors.delete',[$sector->id]) }}"
                                                   class="btn btn-danger w-24">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
        <!-- END: Data List -->
    </div>
@endsection
