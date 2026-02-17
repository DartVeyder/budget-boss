

<div class="container-fluid ">
    <div class="row " style="  margin: 0 -28px;" >
        @foreach ($bills as $key => $bill)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden position-relative h-100 text-white" 
                         style="background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); min-height: 220px;">
                        
                        <!-- Abstract Background Decoration -->
                        <div class="position-absolute top-0 end-0 bg-white opacity-10 rounded-circle" 
                             style="width: 200px; height: 200px; margin-top: -50px; margin-right: -50px; opacity: 0.05;"></div>
                        <div class="position-absolute bottom-0 start-0 bg-primary opacity-25 rounded-circle" 
                             style="width: 150px; height: 150px; margin-bottom: -40px; margin-left: -40px; filter: blur(50px);"></div>

                        <div class="card-body p-4 d-flex flex-column position-relative z-1">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                                         style="width: 48px; height: 48px; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
                                        <x-orchid-icon path="bs.wallet2" class="text-white" width="20" height="20"/>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0 text-white tracking-tight" style="font-size: 1.1rem;">{{$bill["name"]}}</h5>
                                        @if(isset($bill['bank_name']) && $bill['bank_name'])
                                            <small class="text-white-50" style="font-size: 0.85rem;">{{$bill['bank_name']}}</small>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-link text-white opacity-75 hover-opacity-100 p-0" 
                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <x-orchid-icon path="bs.three-dots-vertical" width="20" height="20"/>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 overflow-hidden">
                                        @if($key != 'binance')
                                        <li>
                                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('platform.bills.edit', $key) }}">
                                                <x-orchid-icon path="bs.pencil" class="text-muted"/> 
                                                <span>Редагувати</span>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form action="{{request()->url()}}\remove?id={{$key}}" method="POST">
                                                @csrf
                                                <button class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" type="submit">
                                                    <x-orchid-icon path="bs.trash3" class="text-danger"/> 
                                                    <span>Видалити</span>
                                                </button>
                                            </form>
                                        </li>
                                        @else
                                        <li><span class="dropdown-item-text text-muted small py-2">Системний рахунок</span></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            <!-- Balance Section -->
                            <div class="mt-auto mb-3">
                                <small class="text-uppercase text-white-50 fw-semibold" style="letter-spacing: 1px; font-size: 0.7rem;">Загальний баланс</small>
                                <h2 class="display-6 fw-bold mb-0 text-white">{{$bill["total"]["value"]}}</h2>
                            </div>

                            <!-- Footer / IBAN -->
                            @if(isset($bill['iban']) && $bill['iban'])
                                <div class="pt-3 border-top border-white border-opacity-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 text-white-50">
                                            <x-orchid-icon path="bs.credit-card" width="14" height="14"/>
                                            <span class="font-monospace small" style="letter-spacing: 1px;">
                                                {{ substr($bill['iban'], 0, 4) }} •••• {{ substr($bill['iban'], -4) }}
                                            </span>
                                        </div>
                                        <button class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" 
                                                onclick="navigator.clipboard.writeText('{{$bill['iban']}}')"
                                                title="Копіювати IBAN">
                                            <x-orchid-icon path="bs.clipboard" width="14" height="14"/>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
        @endforeach

    </div>
</div>
