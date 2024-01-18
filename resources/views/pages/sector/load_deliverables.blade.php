<div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
    <div class="font-medium text-base truncate">
        <a href="javascript:;" class="flex items-center ml-auto text-primary"
           id="editCommitmentBtn" com-title="{{$commitment->commitment_title}}"
           com-description="{{$commitment->description}}" com-id="{{$commitment->id}}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Edit Commitment
        </a>
    </div>
    <a href="javascript:;" class="flex items-center ml-auto text-primary"  id="addDeliverableBtn" com-id="{{$commitment->id}}">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        Add Deliverables
    </a>
    <hr>
</div>
<div class="text-center text-primary text-2xl pb-5">
    {{$commitment->title()}}
    <hr>
</div>
<div class="overflow-auto lg:overflow-visible -mt-3">
    <table class="table table-striped">
        <thead>
        <tr>
            <th class="whitespace-nowrap !py-5">#</th>
            <th class="whitespace-nowrap text">Deliverable</th>
{{--            <th class="whitespace-nowrap text">Description</th>--}}
            <th class="whitespace-nowrap text">KPI's</th>
            <th class="whitespace-nowrap text">Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($commitment->deliverables as $deliverable)
            <tr>
                <td class="!py-4">
                    {{$loop->iteration}}
                </td>
                <td class="text">
                    <a href="javascript:;" class="viewDeliverable" del-id="{{$deliverable->id}}">
                        {{$deliverable->title(48)}}
                    </a>
                </td>
{{--                <td class="text">{{$deliverable->description}}</td>--}}
                <td class="text">
                    <a href="javascript:;" class="viewDeliverable" del-id="{{$deliverable->id}}">
                    {{$deliverable->kpis->count()}}
                    </a>
                </td>
                <td class="text">
                    <button id="" class="btn btn-primary btn-sm shadow-md mr-2 viewDeliverable" del-id="{{$deliverable->id}}">View</button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
