@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto ml-3">Performance Action</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">{{$sector->sector_name}}</div>
                </div>
                <br><br>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="rounded-md">
                @if($performanceTrackings->count())
                    <table class="table table-report mt-2">
                        <thead>
                        <tr>
                            <th>Commitment</th>
                            <th>KPIs</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($performanceTrackings as $tracking)
                            <tr>
                                <td>{{ $tracking->name }}</td>
                                <td>{{ $tracking->count }} awaiting action</td>
                                <td>
                                    <a href="{{route('delivery.awaiting.verification.comm.view',[$tracking->id])}}" class="btn btn-primary" >
                                        View
                                    </a>
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
        const updatePerformanceModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#updatePerformanceModal"));
        // updateModal
        function openUpdateModal(performanceId) {
            // Set the performance ID in a hidden input inside the form
            $('#updatePerformanceForm').append('<input type="hidden" name="performance_id" value="' + performanceId + '">');
            // Open the modal
            // $('#updatePerformanceModal').modal('show');
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
            $(".updateModal").on('click',function (){
                updatePerformanceModal.show();
            });
        })
    </script>
@endsection
