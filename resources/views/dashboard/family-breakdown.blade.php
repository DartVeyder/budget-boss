@if(!empty($is_sharing_budget) && !empty($family_breakdown))
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" 
     style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-left: 4px solid #4f46e5 !important;">
    <div class="card-body p-4">
        <!-- Top bar: Title & Filter Switcher -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 40px; height: 40px; background: rgba(79, 70, 229, 0.12); color: #4f46e5;">
                    <i class="icon-people fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        {{ __('Спільний бюджет: Учасники') }}
                    </h5>
                    <span class="text-muted small">
                        {{ __('Розділена інформація по кожному учаснику та швидке перемикання фільтрів') }}
                    </span>
                </div>
            </div>

            <!-- Quick Filter Pills -->
            <div class="d-flex flex-wrap align-items-center gap-1 bg-light p-1 rounded-pill border">
                <a href="{{ route('platform.main') }}" 
                   class="btn btn-sm rounded-pill px-3 fw-semibold {{ empty($filter_user_id) ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none' }}">
                    <i class="icon-grid me-1"></i> {{ __('Всі (Спільний)') }}
                </a>
                @foreach($shared_users as $su)
                    @php
                        $isCurrentActive = ($filter_user_id == $su->id);
                    @endphp
                    <a href="{{ route('platform.main', ['user_id' => $su->id]) }}" 
                       class="btn btn-sm rounded-pill px-3 fw-semibold {{ $isCurrentActive ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none' }}">
                        <i class="icon-user me-1"></i> {{ $su->name }} {{ $su->id === auth()->id() ? '('.__('Ви').')' : '' }}
                    </a>
                @endforeach
            </div>
        </div>

        @if(!empty($filter_user_id))
            @php
                $activeMember = $shared_users->firstWhere('id', $filter_user_id);
            @endphp
            <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 py-2 px-3 d-flex justify-content-between align-items-center mb-3">
                <div class="small text-primary-emphasis d-flex align-items-center gap-2">
                    <i class="icon-info"></i>
                    <span>
                        {{ __('Показано дані лише для:') }} 
                        <strong>{{ $activeMember ? $activeMember->name : '#' . $filter_user_id }}</strong>. 
                        {{ __('Рахунки, місячні підсумки, графіки та останні транзакції відфільтровано.') }}
                    </span>
                </div>
                <a href="{{ route('platform.main') }}" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2 fw-semibold">
                    {{ __('Показати весь спільний бюджет') }}
                </a>
            </div>
        @endif

        <!-- Member Breakdown Cards Grid -->
        <div class="row g-3">
            @foreach($family_breakdown as $item)
                @php
                    $userObj = $item['user'];
                    $isMe = $item['is_me'];
                    $isActive = $item['is_active'];
                    $netPositive = $item['month_net'] >= 0;
                    $userRoute = route('platform.main', ['user_id' => $userObj->id]);
                @endphp
                <div class="col-lg-6 col-12">
                    <div class="p-3 rounded-4 border h-100 transition-all {{ $isActive ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-white' }}"
                         style="border-width: {{ $isActive ? '2px' : '1px' }} !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 38px; height: 38px; background: {{ $isMe ? '#eef2ff' : '#fef3c7' }}; color: {{ $isMe ? '#4f46e5' : '#d97706' }};">
                                    {{ mb_strtoupper(mb_substr($userObj->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        {{ $userObj->name }}
                                        @if($isMe)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small ms-1">{{ __('Ви') }}</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill small ms-1">{{ __('Партнер') }}</span>
                                        @endif
                                    </h6>
                                    <span class="text-muted small">
                                        <i class="icon-wallet me-1"></i>{{ $item['bills_count'] }} {{ __('рахунків') }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-end">
                                <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Баланс рахунків') }}</div>
                                <div class="fw-bold text-dark" style="font-size: 1.1rem;">
                                    {{ \App\Services\Currency\Currency::convertValueToCurrency($item['total_balance']) }}
                                </div>
                            </div>
                        </div>

                        <!-- Current Month Stats for this Member -->
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4">
                                <div class="p-2 rounded-3 bg-light border-0">
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Дохід (міс.)') }}</div>
                                    <div class="fw-bold text-success" style="font-size: 0.9rem;">
                                        +{{ number_format($item['month_income'], 0, '.', ' ') }} ₴
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded-3 bg-light border-0">
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Витрати (міс.)') }}</div>
                                    <div class="fw-bold text-danger" style="font-size: 0.9rem;">
                                        -{{ number_format($item['month_expenses'], 0, '.', ' ') }} ₴
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded-3 bg-light border-0">
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Потік (міс.)') }}</div>
                                    <div class="fw-bold {{ $netPositive ? 'text-success' : 'text-danger' }}" style="font-size: 0.9rem;">
                                        {{ $netPositive ? '+' : '' }}{{ number_format($item['month_net'], 0, '.', ' ') }} ₴
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <a href="{{ route('platform.bills', ['user_id' => $userObj->id]) }}" class="text-muted small text-decoration-none">
                                <i class="icon-credit-card me-1"></i>{{ __('Рахунки учасника') }} &rarr;
                            </a>
                            @if(!$isActive)
                                <a href="{{ $userRoute }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 small">
                                    <i class="icon-filter me-1"></i>{{ __('Фільтрувати дашборд') }}
                                </a>
                            @else
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1 small">
                                    <i class="icon-check me-1"></i>{{ __('Перегляд активний') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
