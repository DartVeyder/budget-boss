<div class="row g-3 mb-4">
    <!-- Card 1: Balance (Net Flow) -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%); border-top: 4px solid #6366f1 !important;">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        {{ __('Баланс (Чистий потік)') }}
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(99, 102, 241, 0.12); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#6366f1" class="bi bi-wallet2" viewBox="0 0 16 16">
                            <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-dark mb-1">
                    {{ number_format($kpi['balance']['current'], 0, '.', ' ') }} ₴
                </h3>

                <div class="d-flex align-items-center gap-2 mt-2">
                    @php $bTrend = $kpi['balance']['trend']; @endphp
                    @if($bTrend['percent'] > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small px-2">
                            <i class="bi bi-arrow-up-right me-0.5"></i>+{{ $bTrend['percent'] }}%
                        </span>
                    @elseif($bTrend['percent'] < 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small px-2">
                            <i class="bi bi-arrow-down-right me-0.5"></i>{{ $bTrend['percent'] }}%
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small px-2">0%</span>
                    @endif
                    <span class="text-muted small" title="Попередній період: {{ $kpi['period']['prev_start'] }} — {{ $kpi['period']['prev_end'] }}">
                        до мин. періоду ({{ $bTrend['formatted_prev'] }})
                    </span>
                </div>

                <div class="mt-auto pt-3 border-top d-flex justify-content-between text-muted small mt-3">
                    <span>Рівень заощаджень:</span>
                    <strong class="{{ $kpi['balance']['savings_rate'] >= 20 ? 'text-success' : 'text-dark' }}">
                        {{ $kpi['balance']['savings_rate'] }}%
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Income -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border-top: 4px solid #10b981 !important;">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        {{ __('Доходи') }}
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(16, 185, 129, 0.12); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#10b981" class="bi bi-graph-up-arrow" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5Z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-dark mb-1 text-success">
                    +{{ number_format($kpi['income']['current'], 0, '.', ' ') }} ₴
                </h3>

                <div class="d-flex align-items-center gap-2 mt-2">
                    @php $iTrend = $kpi['income']['trend']; @endphp
                    @if($iTrend['percent'] > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small px-2">
                            <i class="bi bi-arrow-up-right me-0.5"></i>+{{ $iTrend['percent'] }}%
                        </span>
                    @elseif($iTrend['percent'] < 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small px-2">
                            <i class="bi bi-arrow-down-right me-0.5"></i>{{ $iTrend['percent'] }}%
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small px-2">0%</span>
                    @endif
                    <span class="text-muted small">
                        до мин. періоду ({{ $iTrend['formatted_prev'] }})
                    </span>
                </div>

                <div class="mt-auto pt-3 border-top d-flex justify-content-between text-muted small mt-3">
                    <span>Оплат: <strong class="text-dark">{{ $kpi['income']['count'] }}</strong></span>
                    <span>Сер. чек: <strong class="text-dark">{{ number_format($kpi['income']['avg'], 0, '.', ' ') }} ₴</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Expenses -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #fff1f2 100%); border-top: 4px solid #ef4444 !important;">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        {{ __('Витрати') }}
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(239, 68, 68, 0.12); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#ef4444" class="bi bi-credit-card-2-back" viewBox="0 0 16 16">
                            <path d="M11 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1z"/>
                            <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm13 2v5H1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm-1 9H2a1 1 0 0 1-1-1v-1h14v1a1 1 0 0 1-1 1z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-dark mb-1 text-danger">
                    -{{ number_format($kpi['expenses']['current'], 0, '.', ' ') }} ₴
                </h3>

                <div class="d-flex align-items-center gap-2 mt-2">
                    @php $eTrend = $kpi['expenses']['trend']; @endphp
                    @if($eTrend['diff'] < 0)
                        {{-- Витрати зменшилися — це позитивно! --}}
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small px-2" title="Витрачено на {{ $eTrend['formatted_diff'] }} менше ніж у минулому періоді">
                            <i class="bi bi-arrow-down-right me-0.5"></i>{{ $eTrend['percent'] }}%
                        </span>
                    @elseif($eTrend['diff'] > 0)
                        {{-- Витрати зросли --}}
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small px-2" title="Витрачено на {{ $eTrend['formatted_diff'] }} більше ніж у минулому періоді">
                            <i class="bi bi-arrow-up-right me-0.5"></i>+{{ $eTrend['percent'] }}%
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small px-2">0%</span>
                    @endif
                    <span class="text-muted small">
                        до мин. періоду ({{ $eTrend['formatted_prev'] }})
                    </span>
                </div>

                <div class="mt-auto pt-3 border-top d-flex justify-content-between text-muted small mt-3">
                    <span>Операцій: <strong class="text-dark">{{ $kpi['expenses']['count'] }}</strong></span>
                    <span>На день: <strong class="text-dark">{{ number_format($kpi['expenses']['daily_burn'], 0, '.', ' ') }} ₴</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Financial Health & Period Summary -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%); border-top: 4px solid #0284c7 !important;">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        {{ __('Збережено з доходів') }}
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(2, 132, 199, 0.12); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#0284c7" class="bi bi-piggy-bank" viewBox="0 0 16 16">
                            <path d="M5 6.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm1.138-1.496A6.613 6.613 0 0 1 7.964 4.5c.666 0 1.303.097 1.893.273a.5.5 0 0 0 .286-.958A7.602 7.602 0 0 0 7.964 3.5c-.734 0-1.441.103-2.102.292a.5.5 0 1 0 .276.962z"/>
                            <path fill-rule="evenodd" d="M7.964 1.527c-2.977 0-5.571 1.704-6.32 4.125h-.5a1 1 0 0 0-1 1v.77a2.5 2.5 0 0 0 .5 1.625l.407.544a4.015 4.015 0 0 0-.256 1.43v1.5a1 1 0 0 0 1 1h.5v1.5a1 1 0 0 0 1 1h1.5a1 1 0 0 0 1-1v-1.5h3.072v1.5a1 1 0 0 0 1 1h1.5a1 1 0 0 0 1-1v-1.5h.5a1 1 0 0 0 1-1v-1.5c0-.498-.093-.974-.256-1.43l.407-.544A2.5 2.5 0 0 0 16 7.42V6.65a1 1 0 0 0-1-1h-.5c-.749-2.421-3.343-4.125-6.32-4.125zm0 1c2.518 0 4.708 1.455 5.344 3.528H2.62c.636-2.073 2.826-3.528 5.344-3.528zm-5.068 5.6a1.5 1.5 0 0 1-.3-1.077v-.05h10.744v.05a1.5 1.5 0 0 1-.3 1.077l-.604.805a.5.5 0 0 0-.1.3v2.245a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5V8.832a.5.5 0 0 0-.1-.3l-.604-.805z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-dark mb-1 text-primary" style="color: #0284c7 !important;">
                    {{ number_format(max(0, $kpi['balance']['current']), 0, '.', ' ') }} ₴
                </h3>

                <!-- Savings progress bar -->
                <div class="mt-2">
                    <div class="progress rounded-pill" style="height: 8px; background-color: #e0f2fe;">
                        <div class="progress-bar rounded-pill" role="progressbar" 
                             style="width: {{ min(100, max(0, $kpi['balance']['savings_rate'])) }}%; background-color: #0284c7;" 
                             aria-valuenow="{{ $kpi['balance']['savings_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-3 border-top d-flex justify-content-between text-muted small mt-3">
                    <span>Період: <strong class="text-dark">{{ $kpi['period']['days'] }} днів</strong></span>
                    <span class="text-truncate ms-1" title="Попередній період: {{ $kpi['period']['prev_start'] }} — {{ $kpi['period']['prev_end'] }}">
                        Мин: <strong class="text-dark">{{ $kpi['period']['prev_start'] }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
