@if($amount > 0)
    <span class="text-success" style="font-size: 18px; font-weight: 600; ">{{$amount}}</span>
@else
    <span class="text-danger" style="font-size: 18px; font-weight: 600; ">{{$amount}}</span>
@endif