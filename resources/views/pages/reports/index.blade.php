@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .merged {
            background-color: #e0e0e0;
            text-align: center;
        }
    </style>
@endsection

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Reports
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('reports.comprehensive') }}" class="btn btn-primary mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="bar-chart-3"></i>
                Comprehensive KPI Report
            </a>
        </div>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">Generate Report</div>
                </div>
                <form id="reportForm">
                    @csrf
                    <div class="grid grid-cols-12 gap-4 gap-y-3 mt-3">
                        <div class="col-span-3 sm:col-span-3">
                            <label for="start_month" class="form-label">Start Month</label>
                            <select name="start_month" id="start_month" class="form-control">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-span-3 sm:col-span-3">
                            <label for="end_month" class="form-label">End Month</label>
                            <select name="end_month" id="end_month" class="form-control">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-span-3 sm:col-span-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" name="year" id="year" value="{{ date('Y') }}" class="form-control"
                                   min="2020" max="2030">
                        </div>
                        <div class="col-span-3 sm:col-span-3 mt-5">
                            <button type="submit" class="btn btn-primary w-52" id="generateBtn">
                                <span class="btn-text">Generate</span>
                                <span class="btn-loading" style="display: none;">
                                    <i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>
                                    Generating...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AJAX Results Container -->
    <div id="reportResults" class="intro-y grid grid-cols-12 gap-5 mt-5" style="display: none;">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box pt-5 pl-5 pr-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">Generated Report</div>
                </div>
                <ul class="nav nav-link-tabs flex-col sm:flex-row justify-center lg:justify-start text-center"
                    role="tablist">
                    <li id="profile-tab" class="nav-item" role="presentation">
                        <a href="javascript:;" class="nav-link py-4 flex items-center active"
                           data-tw-target="#profile"
                           aria-controls="profile" aria-selected="true" role="tab">
                            <i class="w-4 h-4 mr-2" data-lucide="bar-chart-2"></i>
                            Overall Grand Summary
                        </a>
                    </li>
                    <li id="change-photo-tab" class="nav-item" role="presentation">
                        <a href="javascript:;" class="nav-link py-4 flex items-center"
                           data-tw-target="#change-photo" aria-selected="false" role="tab">
                            <i class="w-4 h-4 mr-2" data-lucide="bar-chart-2"></i>
                            MDA Sector Summary
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>


    <div class="tab-content mt-5" id="reportTabContent">
        <!-- Tab content will be loaded here via AJAX -->
    </div>
    </div>
    <!-- Error Alert Container -->
    <div id="errorAlert" class="mt-3 alert alert-danger" style="display: none;">
        <ul class="mb-0" id="errorList">
        </ul>
    </div>

@endsection
@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
            // Handle form submission
            $('#reportForm').on('submit', function (e) {
                e.preventDefault();

                // Show loading state
                $('#generateBtn .btn-text').hide();
                $('#generateBtn .btn-loading').show();
                $('#generateBtn').prop('disabled', true);

                // Hide previous results and errors
                $('#reportResults').hide();
                $('#errorAlert').hide();

                // Get form data
                var formData = {
                    start_month: $('#start_month').val(),
                    end_month: $('#end_month').val(),
                    year: $('#year').val(),
                    _token: $('input[name="_token"]').val()
                };

                // Make AJAX request
                $.ajax({
                    url: '{{ route("reports.generate") }}',
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        // Hide loading state
                        $('#generateBtn .btn-text').show();
                        $('#generateBtn .btn-loading').hide();
                        $('#generateBtn').prop('disabled', false);

                        if (response.success) {
                            // Display the report content
                            $('#reportTabContent').html(response.html);
                            $('#reportResults').show();

                            // Scroll to results
                            $('html, body').animate({
                                scrollTop: $('#reportResults').offset().top - 100
                            }, 500);
                        } else {
                            // Show error message
                            showError(response.message || 'An error occurred while generating the report.');
                        }
                    },
                    error: function (xhr) {
                        // Hide loading state
                        $('#generateBtn .btn-text').show();
                        $('#generateBtn .btn-loading').hide();
                        $('#generateBtn').prop('disabled', false);

                        if (xhr.status === 422) {
                            // Validation errors
                            var errors = xhr.responseJSON.errors;
                            var errorMessages = [];
                            for (var field in errors) {
                                errorMessages.push(errors[field][0]);
                            }
                            showError(errorMessages);
                        } else {
                            // General error
                            showError('An error occurred while generating the report. Please try again.');
                        }
                    }
                });
            });

            // Function to show error messages
            function showError(messages) {
                var errorList = $('#errorList');
                errorList.empty();

                if (Array.isArray(messages)) {
                    messages.forEach(function (message) {
                        errorList.append('<li>' + message + '</li>');
                    });
                } else {
                    errorList.append('<li>' + messages + '</li>');
                }

                $('#errorAlert').show();

                // Scroll to error
                $('html, body').animate({
                    scrollTop: $('#errorAlert').offset().top - 100
                }, 500);
            }
        });
    </script>
@endsection
