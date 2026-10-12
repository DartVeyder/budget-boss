@if($type == 'income' || (float)$amount > 0)
    <span class="text-success text-nowrap d-inline-block" style="font-size: 18px; font-weight: 600;">+{{ abs((float)$amount) }} {{$symbol}}</span>
@else
    <span class="text-danger text-nowrap d-inline-block" style="font-size: 18px; font-weight: 600;">{{$amount}} {{$symbol}}</span>
@endif

