@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto ml-3">Commitment</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">Performance Awaiting Confirmations</div>
                </div>
                <button class="btn btn-primary w-24 float-right">Files</button>
                <br><br>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="rounded-md">
                <a href="javascript:;" class="btn btn-primary ml-3" data-tw-toggle="modal"
                   data-tw-target="#header-footer-modal-preview">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Add New
                </a>
                @if($performanceTrackings->count())
                    <table class="table table-report mt-2">
                            <thead>
                            <tr>
                                <th>Sector</th>
                                <th>Commitment</th>
                                <th>Deliverable</th>
                                <th>KPI</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($performanceTrackings as $tracking)
                                <tr>
                                    <td>{{ $tracking->kpi->deliverable->commitment->sector->sector_name }}</td>
                                    <td>{{ $tracking->kpi->deliverable->commitment->name }}</td>
                                    <td>{{ $tracking->kpi->deliverable->deliverable }}</td>
                                    <td>{{ $tracking->kpi->kpi }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updatePerformanceModal" onclick="openUpdateModal('{{ $tracking->id }}')">Update</button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    @else
                    <center>
                        Click <em class="text-success">Add New </em> to add deliverable.
                    </center>
                @endif

            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                {{--TODO: Add First Chart Here--}}
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                {{--TODO: Add Second Chart Here--}}
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                {{--TODO: Add Third Chart Here--}}
            </div>
        </div>
    </div>
    <!-- Add this modal at the end of your blade file -->
    <div class="modal fade" id="updatePerformanceModal" tabindex="-1" aria-labelledby="updatePerformanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePerformanceModalLabel">Update Performance Tracking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form to update performance tracking -->
                    <form id="updatePerformanceForm">
                        @csrf
                        <div class="mb-3">
                            <label for="delivery_department_value" class="form-label">Delivery Department Value</label>
                            <input type="text" class="form-control" id="delivery_department_value" name="delivery_department_value" required>
                        </div>
                        <div class="mb-3">
                            <label for="delivery_department_remark" class="form-label">Delivery Department Remark</label>
                            <textarea class="form-control" id="delivery_department_remark" name="delivery_department_remark"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="confirmation_status" class="form-label">Confirmation Status</label>
                            <select class="form-select" id="confirmation_status" name="confirmation_status" required>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Not Confirmed">Not Confirmed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        const editCommitmentModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#editCommitmentModal"));

        function openUpdateModal(performanceId) {
            // Set the performance ID in a hidden input inside the form
            $('#updatePerformanceForm').append('<input type="hidden" name="performance_id" value="' + performanceId + '">');
            // Open the modal
            $('#updatePerformanceModal').modal('show');
        }
        $('#updatePerformanceForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route("update.performance") }}',
                data: $(this).serialize(),
                success: function(response) {
                    // Handle success, close modal, update UI, etc.
                    $('#updatePerformanceModal').modal('hide');
                    // You may want to refresh or update the table here
                },
                error: function(error) {
                    // Handle errors, show error messages, etc.
                }
            });
        });
        $(function () {
        })
    </script>
@endsection
