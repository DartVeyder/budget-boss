@if($type == 'income')
    <span class="text-success" style="font-size: 18px; font-weight: 600; ">+{{$amount }} {{$symbol}}</span>
@else
    <span class="text-danger" style="font-size: 18px; font-weight: 600; ">{{$amount}} {{$symbol}}</span>
@endif
