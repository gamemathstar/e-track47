@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <div class="mr-auto ml-3">
            <a href="{{ route('delivery.coordinator.final-review.commitment', $deliverable->commitment_id) }}"
               class="text-primary text-sm hover:underline">← Back to deliverables</a>
            <h2 class="text-lg font-medium mt-2">Final coordinator review</h2>
        </div>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            <div class="box p-5 rounded-md">
                <div class="text-slate-500 text-sm">{{ optional(optional($deliverable->commitment)->sector)->sector_name ?? '' }}</div>
                <div class="text-primary text-2xl mt-1">{{ $deliverable->deliverable }}</div>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12">
            @if($kpis->count())
                <table class="table table-bordered table-report mt-2">
                    <thead>
                    <tr>
                        <th class="whitespace-nowrap">#</th>
                        <th class="whitespace-nowrap">KPI</th>
                        <th class="whitespace-nowrap">Target</th>
                        <th class="whitespace-nowrap">Year</th>
                        <th class="whitespace-nowrap">1<sup>st</sup> QPT</th>
                        <th class="whitespace-nowrap">2<sup>nd</sup> QPT</th>
                        <th class="whitespace-nowrap">3<sup>rd</sup> QPT</th>
                        <th class="whitespace-nowrap">4<sup>th</sup> QPT</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($kpis as $kpi)
                        @php $tracks = $kpi->performanceTracking; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $kpi->kpi }}</td>
                            <td>{{ $kpi->target_value }} ({{ $kpi->unit_of_measurement }})</td>
                            <td>{{ $kpi->year ?? '—' }}</td>
                            @foreach([1, 2, 3, 4] as $qNum)
                                @php $t = $tracks->where('quarter', $qNum)->first(); @endphp
                                <td class="text-center">
                                    @if($t && $t->sector_head_approved_by && $t->actual_value)
                                        @if($t->isAwaitingCoordinatorFinalApproval())
                                            <a href="javascript:;"
                                               class="coordinator-review-link text-primary font-semibold hover:underline"
                                               data-track-id="{{ $t->id }}">
                                                {{ $t->actual_value }}
                                            </a>
                                        @else
                                            <span class="text-slate-600">{{ $t->actual_value }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="box p-8 text-center text-slate-500">No KPIs for this deliverable.</div>
            @endif
        </div>
    </div>

    <div id="coordinator-final-modal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('performance.tracking.coordinator.confirm') }}" method="post" id="coordinator-final-form">
                    @csrf
                    <input type="hidden" name="track_id" id="coordinator_track_id">
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Final review — <span id="coordinator_modal_title"></span></h2>
                    </div>
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-12">
                            <h3 class="font-semibold border-b pb-2 mb-3">Submission</h3>
                            <dl class="grid grid-cols-12 gap-2 text-sm">
                                <dt class="col-span-4 text-slate-500">KPI</dt>
                                <dd class="col-span-8" id="cd_kpi"></dd>
                                <dt class="col-span-4 text-slate-500">Target</dt>
                                <dd class="col-span-8" id="cd_target"></dd>
                                <dt class="col-span-4 text-slate-500">Quarter / Year</dt>
                                <dd class="col-span-8" id="cd_qy"></dd>
                                <dt class="col-span-4 text-slate-500">Tracking date</dt>
                                <dd class="col-span-8" id="cd_date"></dd>
                                <dt class="col-span-4 text-slate-500">Milestone</dt>
                                <dd class="col-span-8" id="cd_milestone"></dd>
                                <dt class="col-span-4 text-slate-500">Submitted value</dt>
                                <dd class="col-span-8 font-semibold" id="cd_actual"></dd>
                                <dt class="col-span-4 text-slate-500">Remarks</dt>
                                <dd class="col-span-8" id="cd_remarks"></dd>
                            </dl>
                        </div>
                        <div class="col-span-12">
                            <h3 class="font-semibold border-b pb-2 mb-3">Sector Head</h3>
                            <p class="text-sm" id="cd_sh"></p>
                        </div>
                        <div class="col-span-12">
                            <h3 class="font-semibold border-b pb-2 mb-3">Facilitator (accepted)</h3>
                            <dl class="grid grid-cols-12 gap-2 text-sm">
                                <dt class="col-span-4 text-slate-500">Reviewer</dt>
                                <dd class="col-span-8" id="cd_fac_name"></dd>
                                <dt class="col-span-4 text-slate-500">Delivery dept. value</dt>
                                <dd class="col-span-8" id="cd_dd_val"></dd>
                                <dt class="col-span-4 text-slate-500">Delivery dept. remark</dt>
                                <dd class="col-span-8" id="cd_dd_rem"></dd>
                            </dl>
                        </div>
                        <div class="col-span-12 border-t pt-4">
                            <label for="coordinator_decision" class="form-label">Your decision <span class="text-red-500">*</span></label>
                            <select name="coordinator_decision" id="coordinator_decision" class="form-control" required>
                                <option value="">Select…</option>
                                <option value="Accept">Approve (final)</option>
                                <option value="Reject">Reject</option>
                            </select>
                        </div>
                        <div class="col-span-12" id="coordinator_reject_wrap" style="display:none;">
                            <label for="coordinator_rejection_reason" class="form-label">Rejection reason <span class="text-red-500">*</span></label>
                            <textarea name="coordinator_rejection_reason" id="coordinator_rejection_reason" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal" class="btn btn-outline-secondary w-24 mr-1">Cancel</button>
                        <button type="submit" class="btn btn-primary w-28">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('dist/js/jquery.min.js') }}"></script>
    <script>
        (function () {
            var trackDetails = @json($coordinatorTrackDetails ?? []);

            function esc(s) {
                if (s === null || s === undefined) return '—';
                return String(s);
            }

            $(document).on('click', '.coordinator-review-link', function () {
                var id = $(this).data('track-id');
                var d = trackDetails[id];
                if (!d) return;

                $('#coordinator_track_id').val(id);
                $('#coordinator_modal_title').text(d.kpi_name + ' — ' + d.quarter + ' Q ' + d.year);
                $('#cd_kpi').text(d.kpi_name);
                $('#cd_target').text(d.target_value + ' ' + d.unit_of_measurement);
                $('#cd_qy').text('Q' + d.quarter + ' / ' + d.year);
                $('#cd_date').text(esc(d.tracking_date));
                $('#cd_milestone').text(esc(d.milestone));
                $('#cd_actual').text(esc(d.actual_value));
                $('#cd_remarks').text(esc(d.remarks));
                $('#cd_sh').text(
                    (d.sector_head_name || '—') + (d.sector_head_at ? ' · ' + d.sector_head_at : '')
                );
                $('#cd_fac_name').text(
                    (d.facilitator_name || '—') + (d.facilitator_at ? ' · ' + d.facilitator_at : '')
                );
                $('#cd_dd_val').text(esc(d.delivery_department_value));
                $('#cd_dd_rem').text(esc(d.delivery_department_remark));

                $('#coordinator_decision').val('');
                $('#coordinator_rejection_reason').val('');
                $('#coordinator_reject_wrap').hide();
                $('#coordinator_rejection_reason').removeAttr('required');

                tailwind.Modal.getOrCreateInstance(document.querySelector('#coordinator-final-modal')).show();
            });

            $('#coordinator_decision').on('change', function () {
                if ($(this).val() === 'Reject') {
                    $('#coordinator_reject_wrap').show();
                    $('#coordinator_rejection_reason').attr('required', 'required');
                } else {
                    $('#coordinator_reject_wrap').hide();
                    $('#coordinator_rejection_reason').removeAttr('required').val('');
                }
            });

            $('#coordinator-final-form').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find('button[type="submit"]');
                var orig = btn.html();
                btn.prop('disabled', true).html('Submitting…');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    success: function (res) {
                        if (res.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'Done', text: res.message || 'Saved.', timer: 1800, showConfirmButton: false })
                                    .then(function () { location.reload(); });
                            } else {
                                alert(res.message || 'Saved.');
                                location.reload();
                            }
                        } else {
                            btn.prop('disabled', false).html(orig);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Request failed.' });
                            } else {
                                alert(res.message || 'Request failed.');
                            }
                        }
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html(orig);
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Error', text: msg });
                        } else {
                            alert(msg);
                        }
                    }
                });
            });
        })();
    </script>
@endsection
