<h1><strong>Budget/Target For Commitments in {{$year}}</strong></h1>
<hr>
<br>

<button class="btn btn-primary btn-sm commitmentBudgetBtn"
        year="{{$year}}">
    Add Budget/Target
</button>
<table class="table table-bordered">
    <tr>
        <th>#</th>
        <th>Commitment</th>
        <th>Allocation</th>
    </tr>

    @foreach($budgets as $budget)
        <tr>
            <td>{{$loop->iteration}}</td>
            <td>{{$budget->commitment_title}}</td>
            <td>{{$budget->amount}}</td>
        </tr>
    @endforeach
</table>
