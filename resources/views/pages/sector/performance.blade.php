@php use Carbon\Carbon; @endphp
<table class="table table-bordered" width="100%" style="width: 100%">
    <tr>
        <th class="whitespace-nowrap">Tracking Date</th>
        <td class="whitespace-nowrap">
            {{ $track->tracking_date?Carbon::parse($track->tracking_date)->format('d M, Y'):'- - -' }}
        </td>
    </tr>
    <tr>
        <th class="whitespace-nowrap">Actual Value</th>
        <td class="whitespace-nowrap">
            {{ $track->actual_value }} ({{ $kpi->unit_of_measurement }})
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
</table>
