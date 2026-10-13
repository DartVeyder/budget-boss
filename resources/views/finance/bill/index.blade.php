<div class="container-fluid">
    @if(!empty($groupedData['is_shared']))
        <!-- Shared Budget User Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-light">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small fw-semibold me-2"><i class="bi bi-funnel me-1"></i>Фільтр:</span>
                    <a href="{{ route('platform.bills') }}" 
                       class="btn btn-sm {{ empty($filter_user_id) ? 'btn-primary shadow-sm' : 'btn-outline-secondary' }} rounded-pill px-3">
                        <i class="bi bi-people me-1"></i> Всі рахунки
                    </a>
                    @foreach($groupedData['shared_users'] as $userMember)
                        <a href="{{ route('platform.bills', ['user_id' => $userMember->id]) }}" 
                           class="btn btn-sm {{ $filter_user_id == $userMember->id ? 'btn-primary shadow-sm' : 'btn-outline-secondary' }} rounded-pill px-3">
                            <i class="bi bi-person me-1"></i> {{ $userMember->name }} {{ $userMember->id === \Illuminate\Support\Facades\Auth::id() ? '(Я)' : '' }}
                        </a>
                    @endforeach
                </div>
                <div class="d-flex align-items-center gap-2 small">
                    <span class="text-muted">Спільний капітал:</span>
                    <strong class="fs-6 text-dark">{{ $groupedData['grand_total_formatted'] }}</strong>
                </div>
            </div>
        </div>

        @if(empty($filter_user_id))
            <!-- Grouped by user view -->
            @foreach($groupedData['groups'] as $group)
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $group['is_self'] ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} px-2 py-1 rounded-pill">
                                <i class="bi bi-person-fill me-1"></i>{{ $group['user_name'] }} {{ $group['is_self'] ? '(Мої рахунки)' : '(Партнер)' }}
                            </span>
                            <span class="text-muted small">({{ $group['bills_count'] }} рахунків)</span>
                        </div>
                        <div class="small text-muted">
                            Підсумок: <strong class="text-dark">{{ $group['total_formatted'] }}</strong>
                        </div>
                    </div>

                    <div class="row" style="margin: 0 -15px;">
                        @foreach ($group['bills'] as $key => $bill)
                            @include('finance.bill.partials.card', ['key' => $key, 'bill' => $bill])
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if(isset($bills['binance']))
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill">
                            <i class="bi bi-currency-bitcoin me-1"></i>Криптоактиви (Binance)
                        </span>
                    </div>
                    <div class="row" style="margin: 0 -15px;">
                        @include('finance.bill.partials.card', ['key' => 'binance', 'bill' => $bills['binance']])
                    </div>
                </div>
            @endif
        @else
            <!-- Filtered single user view -->
            <div class="row" style="margin: 0 -15px;">
                @foreach ($bills as $key => $bill)
                    @include('finance.bill.partials.card', ['key' => $key, 'bill' => $bill])
                @endforeach
            </div>
        @endif
    @else
        <!-- Standard single user grid -->
        <div class="row" style="margin: 0 -15px;">
            @foreach ($bills as $key => $bill)
                @include('finance.bill.partials.card', ['key' => $key, 'bill' => $bill])
            @endforeach
        </div>
    @endif
</div>
