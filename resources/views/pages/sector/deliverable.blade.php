<div class="intro-y box px-5 pt-5 mt-5">
    <div class="flex flex-col lg:flex-row border-b border-slate-200/60 dark:border-darkmode-400 pb-5 -mx-5">
        <div
            class="mt-6 lg:mt-0 flex-1 px-5 border-l border-r border-slate-200/60 dark:border-darkmode-400 border-t lg:border-t-0 pt-5 lg:pt-0">
            <div class="font-medium text-center lg:text-left lg:mt-3">{{$deliverable->title()}}</div>
            <div class="flex flex-col justify-center items-center lg:items-start mt-4">
                <div class="truncate sm:whitespace-normal flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         icon-name="mail" data-lucide="mail" class="lucide lucide-mail w-4 h-4 mr-2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    {{$deliverable->description}}
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-col lg:flex-row border-b border-slate-200/60 dark:border-darkmode-400 pb-5 -mx-5">
        <div class="mt-6 lg:mt-0 flex-1 px-5 border-l border-r border-slate-200/60 dark:border-darkmode-400 border-t lg:border-t-0 pt-5 lg:pt-0">

            <div class="flex">
                <!-- Left-aligned content -->
                <div class="mr-4">

                </div>

                <!-- Right-aligned content using ml-auto -->
                <div class="ml-auto">
                    <a href="javascript:;" id="addKpiModalBtn" data-tw-toggle="modal" data-tw-target="#next-overlapping-modal-preview"  class="flex items-center ml-auto text-primary p-5" del-id="{{$deliverable->id}}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Add KPI
                    </a>
                </div>
            </div>

            <hr>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="whitespace-nowrap">#</th>
                        <th class="whitespace-nowrap">KPI</th>
                        <th class="whitespace-nowrap">Unit Of Measurement</th>
                        <th class="whitespace-nowrap">Target</th>
                        <th class="whitespace-nowrap">Actual Value</th>
                        <th class="whitespace-nowrap">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($deliverable->__kpis() as $kpi)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$kpi->kpi}}</td>
                                <td>{{$kpi->unit_of_measurement}}</td>
                                <td>{{$kpi->target}}</td>
                                <td>{{$kpi->actual_value}}</td>
                                <td>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

