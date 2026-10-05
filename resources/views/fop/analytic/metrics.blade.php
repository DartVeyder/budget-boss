<div class="row g-3 mb-4">
    <!-- Card 1: Gross Income -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border-top: 4px solid #10b981 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        {{ $kpi['quarter'] ? "Дохід за Q{$kpi['quarter']} {$kpi['year']}" : "Дохід за {$kpi['year']} рік" }}
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(16, 185, 129, 0.15); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#10b981" class="bi bi-wallet2" viewBox="0 0 16 16">
                            <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-dark mb-1">
                    {{ number_format($kpi['total_income'], 2, '.', ' ') }} ₴
                </h3>

                <div class="d-flex align-items-center gap-2 mt-2">
                    @if($kpi['growth_percent'] > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small px-2">
                            <i class="bi bi-arrow-up-right me-0.5"></i>+{{ $kpi['growth_percent'] }}%
                        </span>
                    @elseif($kpi['growth_percent'] < 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small px-2">
                            <i class="bi bi-arrow-down-right me-0.5"></i>{{ $kpi['growth_percent'] }}%
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small px-2">0%</span>
                    @endif
                    <span class="text-muted small">до мин. періоду ({{ number_format($kpi['prev_income'], 0, '.', ' ') }} ₴)</span>
                </div>

                <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                    <span>Оплат: <strong class="text-dark">{{ $kpi['tx_count'] }}</strong></span>
                    <span>Сер. чек: <strong class="text-dark">{{ number_format($kpi['avg_income'], 2, '.', ' ') }} ₴</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Net Income (after taxes) -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%); border-top: 4px solid #3b82f6 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        Чистий прибуток
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(59, 130, 246, 0.15); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#3b82f6" class="bi bi-cash-stack" viewBox="0 0 16 16">
                            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-primary mb-1">
                    {{ number_format($kpi['net_income'], 2, '.', ' ') }} ₴
                </h3>

                <div class="text-muted small mt-2">
                    Залишок після ЄП, ВЗ та ЄСВ
                </div>

                <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                    <span>Ефективна ставка: <strong class="text-dark">{{ $kpi['effective_tax_rate'] }}%</strong></span>
                    <span>Рентабельність: <strong class="text-success">{{ $kpi['total_income'] > 0 ? round(($kpi['net_income'] / $kpi['total_income']) * 100, 1) : 0 }}%</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Taxes Accrued & Paid -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%); border-top: 4px solid #f97316 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        Податкове навантаження
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(249, 115, 22, 0.15); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#f97316" class="bi bi-receipt" viewBox="0 0 16 16">
                            <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27zm.216 1.05v12.888l.292-.292a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.292.292V1.556l-.292.292a.5.5 0 0 1-.708 0l-.646-.647-.646.647a.5.5 0 0 1-.708 0l-.646-.647-.646.647a.5.5 0 0 1-.708 0l-.646-.647-.646.647a.5.5 0 0 1-.708 0l-.646-.647-.646.647a.5.5 0 0 1-.708 0l-.646-.647-.646.647a.5.5 0 0 1-.708 0l-.292-.292z"/>
                        </svg>
                    </div>
                </div>

                <h3 class="fw-bolder text-warning mb-1" style="color: #ea580c !important;">
                    {{ number_format($kpi['total_taxes_accrued'], 2, '.', ' ') }} ₴
                </h3>

                <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="badge bg-success-subtle text-success rounded-pill small px-2">Сплачено: {{ number_format($kpi['taxes_paid'], 0, '.', ' ') }} ₴</span>
                    @if($kpi['total_taxes_accrued'] > $kpi['taxes_paid'])
                        <span class="badge bg-danger-subtle text-danger rounded-pill small px-2">До сплати: {{ number_format($kpi['total_taxes_accrued'] - $kpi['taxes_paid'], 0, '.', ' ') }} ₴</span>
                    @endif
                </div>

                <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                    <span>ЄП 5%: <strong class="text-dark">{{ number_format($kpi['single_tax'], 0, '.', ' ') }} ₴</strong></span>
                    <span>ВЗ 1%: <strong class="text-dark">{{ number_format($kpi['military_tax'], 0, '.', ' ') }} ₴</strong></span>
                    <span>ЄСВ: <strong class="text-dark">{{ number_format($kpi['esv_total'], 0, '.', ' ') }} ₴</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: FOP Account & Annual Limit -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%); border-top: 4px solid #8b5cf6 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        Річний ліміт та баланс
                    </span>
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(139, 92, 246, 0.15); width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#8b5cf6" class="bi bi-bank" viewBox="0 0 16 16">
                            <path d="m8 0 6.61 3h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.382l.5 2a.498.498 0 0 1-.485.618H.5a.5.5 0 0 1-.485-.618l.5-2A.5.5 0 0 1 1 13V6H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 3h.89L8 0zM3.777 3h8.447L8 1.05 3.777 3zM2 6v7h1V6H2zm2 0v7h2.5V6H4zm3.5 0v7h1V6h-1zm2 0v7H12V6H9.5zM13 6v7h1V6h-1zm2-1V4H1v1h14zm-.39 9H1.39l-.25 1h13.72l-.25-1z"/>
                        </svg>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-baseline mb-1">
                    <h3 class="fw-bolder text-dark mb-0">
                        {{ number_format($kpi['bill_balance'], 2, '.', ' ') }} ₴
                    </h3>
                    <span class="text-muted small">на рахунку ФОП</span>
                </div>

                <div class="mt-2">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Ліміт ({{ $kpi['limit_progress']['percent'] }}%):</span>
                        <strong class="text-dark">{{ number_format($kpi['limit_progress']['remaining'], 0, '.', ' ') }} ₴ вільно</strong>
                    </div>
                    <div class="progress rounded-pill shadow-inner" style="height: 8px; background-color: #e9ecef;">
                        <div class="progress-bar rounded-pill {{ $kpi['limit_progress']['percent'] >= 85 ? 'bg-danger' : ($kpi['limit_progress']['percent'] >= 65 ? 'bg-warning' : 'bg-primary') }}" 
                             role="progressbar" 
                             style="width: {{ min(100, $kpi['limit_progress']['percent']) }}%;" 
                             aria-valuenow="{{ $kpi['limit_progress']['percent'] }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                    <span>Повний ліміт: <strong>{{ number_format($kpi['limit_progress']['limit'], 0, '.', ' ') }} ₴</strong></span>
                    <a href="{{ route('platform.fop.tax') }}" class="text-primary text-decoration-none fw-semibold">Деталі &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
