@php use Carbon\Carbon; @endphp
<table class="table table-bordered" width="100%" style="width: 100%">
    <tr>
        <th class="whitespace-nowrap">Tracking Date</th>
        <td class="whitespace-nowrap">
            {{ $track->tracking_date?Carbon::parse($track->tracking_date)->format('d M, Y'):'- - -' }}
        </td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Delivery Value</th>
        <td class="whitespace-nowrap">
            {{ $track->actual_value }} ({{ $kpi->unit_of_measurement }})
        </td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Milestone</th>
        <td class="whitespace-nowrap">
            {{ $track->milestone }} ({{ $kpi->unit_of_measurement }})
        </td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Remarks</th>
        <td>{{ $track->remarks }}</td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Delivery Department Value</th>
        <td class="whitespace-nowrap">
            {{ $track->delivery_department_value?$track->delivery_department_value :'- - -' }}
            {{ $track->delivery_department_value? '(' . $kpi->unit_of_measurement . ')' : '' }}
        </td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Delivery Department Remark</th>
        <td>{{ $track->delivery_department_remark?$track->delivery_department_remark:'- - -' }}</td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Status</th>
        <td>{{ $track->confirmation_status }}</td>
    </tr>
    @if($track->facilitator_decision)
        <tr>
            <th class="whitespace-nowrap {{ $track->facilitator_decision === 'Reject' ? 'text-danger' : 'text-success' }}">Facilitator Decision</th>
            <td class="{{ $track->facilitator_decision === 'Reject' ? 'text-danger' : 'text-success' }} font-semibold">
                {{ $track->facilitator_decision === 'Reject' ? 'Rejected' : 'Accepted' }}
                @if($track->facilitator_confirmed_at)
                    <small class="text-slate-500 block mt-1">
                        ({{ Carbon::parse($track->facilitator_confirmed_at)->format('d M, Y h:i A') }})
                    </small>
                @endif
            </td>
        </tr>
        @if($track->facilitator_decision === 'Reject' && $track->facilitator_rejection_reason)
            <tr>
                <th class="whitespace-nowrap text-danger">Rejection Reason</th>
                <td class="text-danger">{{ $track->facilitator_rejection_reason }}</td>
            </tr>
        @endif
    @endif
</table>
