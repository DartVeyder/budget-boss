<div class="container-fluid ">
    <div class="row " style="  margin: 0 -28px;" >
        @foreach ($bills as $key => $bill)
                <div class="col-md-4  ">
                    <div class="bill-card p-3 bg-white rounded m-2" style="height: 180px">
                        <button data-controller="button" data-turbo="true" class="btn btn-link float-end" type="submit" form="post-form" formaction="{{request()->url()}}\remove?id={{$key}}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" class="me-2" viewBox="0 0 16 16" role="img" path="bs.trash3" componentname="orchid-icon">
                                <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"></path>
                            </svg>
                        </button>
                        <h4>
                            {{$bill["name"]}}
                        </h4>
                        <span style="margin-top: 25px;font-size: 28px;text-align: center;display: block;">{{$bill["total"]["value"]}}</span>
                    </div>
                </div>
        @endforeach

    </div>
</div>
