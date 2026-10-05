@php
    $billItems = $charts['expenses']['bill'][0]['items'] ?? [];
    $sourceItems = $charts['expenses']['source'][0]['items'] ?? [];
    $topSources = $top_sources_monthly ?? [];

    $createdAt = request()->get('created_at', []);
    $startParam = $createdAt['start'] ?? '';
    $endParam = $createdAt['end'] ?? '';
    $billIdParam = request()->get('bill_id', '');
@endphp

<style>
    /* Custom thin scrollbar */
    .bb-custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .bb-custom-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .bb-custom-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .bb-custom-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Clickable source item styles */
    .bb-clickable-source {
        cursor: pointer;
        padding: 0.5rem 0.75rem;
        margin: -0.25rem -0.75rem;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
        user-select: none;
        position: relative;
    }
    .bb-clickable-source:hover {
        background-color: #fef3c7;
        transform: translateX(4px);
    }
    .bb-clickable-source.active-source {
        background-color: #fde68a;
        box-shadow: 0 0 0 2px #f59e0b;
    }
    .bb-source-hint {
        opacity: 0;
        font-size: 0.725rem;
        color: #d97706;
        transition: opacity 0.2s ease-in-out;
        font-weight: 600;
        margin-left: 0.4rem;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }
    .bb-clickable-source:hover .bb-source-hint {
        opacity: 1;
    }

    /* Monobank Dark Container */
    .bb-mono-card {
        background: #18181b;
        color: #f4f4f5;
        border-radius: 20px;
        padding: 1.75rem;
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.25);
        border: 1px solid #27272a;
        position: relative;
        overflow: hidden;
    }
    .bb-mono-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #f59e0b, #ea580c, #ec4899);
    }

    /* Pill badge at top of chart */
    .bb-mono-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(39, 39, 42, 0.95);
        border: 1px solid rgba(82, 82, 91, 0.8);
        color: #f4f4f5;
        padding: 0.45rem 1.35rem;
        border-radius: 9999px;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
    }

    /* Monobank Chart Area */
    .bb-mono-chart-container {
        position: relative;
        height: 210px;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .bb-mono-grid-lines {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        pointer-events: none;
    }
    .bb-mono-grid-row {
        display: flex;
        align-items: center;
        width: 100%;
        height: 0;
    }
    .bb-mono-grid-label {
        width: 70px;
        min-width: 70px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #a1a1aa;
        text-align: right;
        padding-right: 12px;
        user-select: none;
    }
    .bb-mono-grid-line {
        flex-grow: 1;
        border-top: 1px dashed rgba(255, 255, 255, 0.18);
    }
    .bb-mono-grid-line.bottom-line {
        border-top: 1px solid rgba(255, 255, 255, 0.25);
    }

    /* Bars container */
    .bb-mono-bars-wrap {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 82px;
        right: 15px;
        display: flex;
        justify-content: space-around;
        align-items: flex-end;
    }
    .bb-mono-bar-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
        position: relative;
        flex: 1;
        max-width: 60px;
        cursor: pointer;
    }
    .bb-mono-bar-pill {
        width: 32px;
        border-radius: 4px 4px 0 0;
        background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 35%, #ea580c 100%);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        min-height: 4px;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    }
    .bb-mono-bar-col:hover .bb-mono-bar-pill {
        filter: brightness(1.2);
        transform: scaleY(1.03);
        box-shadow: 0 0 18px rgba(245, 158, 11, 0.55);
    }

    /* Bar Tooltip */
    .bb-mono-bar-tooltip {
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%) translateY(-8px);
        background: rgba(15, 23, 42, 0.95);
        color: #ffffff;
        padding: 5px 9px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.18s ease-in-out, transform 0.18s ease-in-out;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.15);
        z-index: 10;
    }
    .bb-mono-bar-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: rgba(15, 23, 42, 0.95);
    }
    .bb-mono-bar-col:hover .bb-mono-bar-tooltip {
        opacity: 1;
        transform: translateX(-50%) translateY(-12px);
    }

    /* X Axis Months */
    .bb-mono-xaxis {
        display: flex;
        margin-left: 82px;
        margin-right: 15px;
        justify-content: space-around;
        padding-top: 10px;
    }
    .bb-mono-month-label {
        flex: 1;
        max-width: 60px;
        text-align: center;
        font-size: 0.85rem;
        font-weight: 500;
        color: #a1a1aa;
        text-transform: lowercase;
        user-select: none;
        transition: color 0.18s ease-in-out;
    }
    .bb-mono-bar-col:hover ~ .bb-mono-xaxis .bb-mono-month-label {
        color: #ffffff;
    }

    /* Source Selector Tabs */
    .bb-source-tab-btn {
        background: #27272a;
        color: #d4d4d8;
        border: 1px solid #3f3f46;
        padding: 0.45rem 1rem;
        border-radius: 9999px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.18s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .bb-source-tab-btn:hover {
        background: #3f3f46;
        color: #ffffff;
        border-color: #71717a;
    }
    .bb-source-tab-btn.active {
        background: linear-gradient(135deg, #f59e0b, #ea580c);
        color: #ffffff;
        border-color: #f59e0b;
        box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
    }

    /* Merchant Logo Badge */
    .bb-merchant-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #047857;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.35);
        border: 2px solid rgba(255, 255, 255, 0.2);
        font-size: 1.5rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .bb-merchant-avatar.spar-logo {
        background: #ffffff;
        color: #047857;
        border: 2px solid #047857;
    }

    /* Monobank Slide-over Drawer */
    .bb-source-drawer-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(3px);
        z-index: 99990;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease-in-out;
    }
    .bb-source-drawer-backdrop.show {
        opacity: 1;
        pointer-events: auto;
    }
    .bb-source-drawer {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 560px;
        max-width: 95vw;
        background-color: #18181b;
        color: #f4f4f5;
        z-index: 99999;
        box-shadow: -10px 0 35px rgba(0, 0, 0, 0.4);
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }
    .bb-source-drawer.show {
        transform: translateX(0);
    }
    .bb-source-drawer-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #27272a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #18181b;
    }
    .bb-source-drawer-close {
        background: #27272a;
        border: none;
        color: #a1a1aa;
        font-size: 1.2rem;
        line-height: 1;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.15s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
    }
    .bb-source-drawer-close:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }
    .bb-source-drawer-body {
        padding: 1.5rem;
        overflow-y: auto;
        flex-grow: 1;
    }
    .bb-source-drawer-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #27272a;
        background-color: #121214;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Transactions list inside drawer */
    .bb-source-tx-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        transition: background-color 0.15s ease-in-out;
        border-radius: 6px;
    }
    .bb-source-tx-item:hover {
        background-color: rgba(255, 255, 255, 0.04);
    }
    .bb-source-tx-amount {
        font-weight: 700;
        color: #ef4444;
        font-size: 0.95rem;
        text-align: right;
    }
</style>

<!-- Dedicated Section: Monthly Expenses Breakdown by Sources (Monobank Style) -->
@if(!empty($topSources))
<div class="row mb-4" id="bb-monthly-sources-section">
    <div class="col-12">
        <div class="bb-mono-card">
            <!-- Header with Title & Source Tabs -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-size: 1.25rem;">📈</span>
                        <h4 class="fw-bold mb-0 text-white" style="letter-spacing: 0.3px;">Помісячні витрати по джерелах</h4>
                        <span class="badge bg-dark border border-secondary text-warning rounded-pill px-2 py-1 small">
                            Monobank Style
                        </span>
                    </div>
                    <p class="text-secondary small mb-0">Наочна помісячна динаміка витрат у стилі Monobank</p>
                </div>

                <!-- Tabs for top sources -->
                <div class="d-flex flex-wrap gap-2" id="bb-source-tabs">
                    @foreach($topSources as $idx => $ts)
                        <button type="button" 
                                class="bb-source-tab-btn {{ $idx === 0 ? 'active' : '' }}" 
                                data-target-source-id="{{ $ts['source']['id'] }}">
                            @if(strtolower($ts['source']['name']) === 'spar')
                                🌲
                            @else
                                💳
                            @endif
                            {{ $ts['source']['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Pre-rendered source cards -->
            @foreach($topSources as $idx => $srcData)
                @php
                    $isSpar = strtolower($srcData['source']['name']) === 'spar';
                    $months = $srcData['months'] ?? [];
                    $ticks = $srcData['ticks'] ?? [];
                    $totalSpent = $srcData['total_spent'] ?? 0;
                    $periodLabel = $srcData['period']['label'] ?? 'за півроку';
                    $monthsCount = count($months);
                    $avgMonth = $monthsCount > 0 ? round($totalSpent / $monthsCount, 2) : 0;
                @endphp
                <div class="bb-source-chart-panel" 
                     id="bb-source-panel-{{ $srcData['source']['id'] }}" 
                     style="{{ $idx === 0 ? 'display: block;' : 'display: none;' }}">
                    
                    <!-- Center Merchant & Total Pill -->
                    <div class="text-center my-3">
                        <div class="d-inline-flex flex-column align-items-center">
                            <!-- Merchant Avatar -->
                            <div class="mb-2">
                                @if($isSpar)
                                    <div class="bb-merchant-avatar spar-logo" title="SPAR">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 100 100" fill="#047857">
                                            <circle cx="50" cy="50" r="46" fill="#047857" />
                                            <circle cx="50" cy="50" r="39" fill="#ffffff" />
                                            <polygon points="50,20 62,38 56,38 67,54 60,54 72,72 28,72 40,54 33,54 44,38 38,38" fill="#047857"/>
                                            <rect x="47" y="72" width="6" height="8" fill="#047857"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="bb-merchant-avatar">
                                        {{ mb_substr($srcData['source']['name'], 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <h3 class="fw-bold text-white mb-2">{{ $srcData['source']['name'] }}</h3>

                            <!-- Pill: "Витратили 8 804.21 ₴ за півроку" -->
                            <div class="bb-mono-pill mb-3">
                                <span>Витратили <strong class="text-warning">{{ $srcData['total_formatted'] }}</strong> {{ $periodLabel }}</span>
                            </div>

                            <!-- Fast KPIs -->
                            <div class="d-flex flex-wrap justify-content-center gap-4 text-secondary small">
                                <div>Середнє: <span class="text-light fw-semibold">{{ number_format($avgMonth, 2, '.', ' ') }} ₴ / міс.</span></div>
                                <div>•</div>
                                <div>Покупок: <span class="text-light fw-semibold">{{ $srcData['total_count'] ?? 0 }}</span></div>
                                <div>•</div>
                                <div>Період: <span class="text-light fw-semibold">{{ $srcData['period']['start'] }} — {{ $srcData['period']['end'] }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- The Monobank Vertical Bar Chart -->
                    <div class="bb-mono-chart-container">
                        <!-- Y-axis scale lines -->
                        <div class="bb-mono-grid-lines">
                            <div class="bb-mono-grid-row">
                                <span class="bb-mono-grid-label">{{ $ticks['formatted_max'] ?? '4 000 ₴' }}</span>
                                <div class="bb-mono-grid-line"></div>
                            </div>
                            <div class="bb-mono-grid-row">
                                <span class="bb-mono-grid-label">{{ $ticks['formatted_mid'] ?? '2 000 ₴' }}</span>
                                <div class="bb-mono-grid-line"></div>
                            </div>
                            <div class="bb-mono-grid-row">
                                <span class="bb-mono-grid-label">{{ $ticks['zero'] ?? '0 ₴' }}</span>
                                <div class="bb-mono-grid-line bottom-line"></div>
                            </div>
                        </div>

                        <!-- Vertical Amber Bars -->
                        <div class="bb-mono-bars-wrap">
                            @foreach($months as $m)
                                <div class="bb-mono-bar-col" title="{{ $m['full_label'] }}: {{ $m['formatted_amount'] }}">
                                    <!-- Tooltip -->
                                    <div class="bb-mono-bar-tooltip">
                                        <div>{{ $m['full_label'] }}</div>
                                        <div class="text-warning fw-bold">{{ $m['formatted_amount'] }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ $m['count'] }} покупок</div>
                                    </div>
                                    <!-- Bar pill -->
                                    <div class="bb-mono-bar-pill" style="height: {{ max(4, $m['height_percent']) }}%;"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- X-axis Month Labels -->
                    <div class="bb-mono-xaxis">
                        @foreach($months as $m)
                            <div class="bb-mono-month-label">{{ $m['short_label'] }}</div>
                        @endforeach
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex justify-content-center gap-3 mt-4 pt-2">
                        <button type="button" 
                                class="btn btn-outline-light btn-sm rounded-pill px-4 bb-open-source-drawer-btn" 
                                data-source-id="{{ $srcData['source']['id'] }}"
                                data-source-name="{{ $srcData['source']['name'] }}">
                            📋 Список покупок {{ $srcData['source']['name'] }}
                        </button>
                        <a href="{{ route('platform.transactions', ['filter' => ['transaction_source_id' => $srcData['source']['id']]]) }}" 
                           class="btn btn-warning btn-sm rounded-pill px-4 text-dark fw-semibold" 
                           target="_blank">
                            🔗 Всі транзакції в системі
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- Expenses By Bills -->
    <div class="col-md-6 mb-4">
        <div class="bg-white rounded shadow-sm p-4 h-100 border-top border-3 border-danger d-flex flex-column">
            <h5 class="text-muted mb-4 fw-bold text-uppercase" style="letter-spacing: 0.5px;">{{ __('По рахунках (Витрати)') }}</h5>
            <div class="d-flex flex-column gap-3 bb-custom-scroll" style="max-height: 440px; overflow-y: auto; padding-right: 6px;">
                @forelse($billItems as $item)
                    <div class="category-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-dark">{{ $item['name'] }}</span>
                            <span class="fw-bold" style="color: {{ $item['color'] }};">{{ $item['amount'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 8px; background-color: #f1f5f9; border-radius: 10px;">
                                <div class="progress-bar rounded-pill" role="progressbar" 
                                     style="width: {{ $item['percent'] }}%; background-color: {{ $item['color'] }};" 
                                     aria-valuenow="{{ $item['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <span class="text-muted small fw-medium" style="min-width: 45px; text-align: right;">{{ $item['percent'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-inbox mb-2" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625A.5.5 0 0 1 16 8.5V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .11-.312l3.7-4.625zM3.81 5h8.38l-2.4-3H6.21L3.81 5zM1 8.5v4.5A1 1 0 0 0 2 14h12a1 1 0 0 0 1-1V8.5H11.5a.5.5 0 0 0-.5.5 2.5 2.5 0 0 1-5 0 .5.5 0 0 0-.5-.5H1z"/>
                        </svg>
                        <p class="mb-0">{{ __('No data') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Expenses By Sources (Clickable for Monobank view) -->
    <div class="col-md-6 mb-4">
        <div class="bg-white rounded shadow-sm p-4 h-100 border-top border-3 border-danger d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-muted fw-bold text-uppercase mb-0" style="letter-spacing: 0.5px;">{{ __('По джерелах (Витрати)') }}</h5>
                <a href="#bb-monthly-sources-section" class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 small rounded-pill fw-medium text-decoration-none" title="Перейти до помісячного графіку">
                    📊 Monobank View
                </a>
            </div>
            <div class="d-flex flex-column gap-3 bb-custom-scroll" style="max-height: 440px; overflow-y: auto; padding-right: 6px;">
                @forelse($sourceItems as $item)
                    <div class="category-item bb-clickable-source" 
                         data-source-id="{{ $item['id'] ?? '' }}" 
                         data-source-name="{{ $item['name'] }}"
                         title="Натисніть для перегляду помісячних витрат по {{ $item['name'] }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-dark d-flex align-items-center">
                                {{ $item['name'] }}
                                <span class="bb-source-hint">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zm6-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V7zm6-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/>
                                    </svg>
                                    Помісячно
                                </span>
                            </span>
                            <span class="fw-bold" style="color: {{ $item['color'] }};">{{ $item['amount'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 8px; background-color: #f1f5f9; border-radius: 10px;">
                                <div class="progress-bar rounded-pill" role="progressbar" 
                                     style="width: {{ $item['percent'] }}%; background-color: {{ $item['color'] }};" 
                                     aria-valuenow="{{ $item['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <span class="text-muted small fw-medium" style="min-width: 45px; text-align: right;">{{ $item['percent'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-inbox mb-2" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625A.5.5 0 0 1 16 8.5V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .11-.312l3.7-4.625zM3.81 5h8.38l-2.4-3H6.21L3.81 5zM1 8.5v4.5A1 1 0 0 0 2 14h12a1 1 0 0 0 1-1V8.5H11.5a.5.5 0 0 0-.5.5 2.5 2.5 0 0 1-5 0 .5.5 0 0 0-.5-.5H1z"/>
                        </svg>
                        <p class="mb-0">{{ __('No data') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Slide-over Monobank Drawer for Any Expense Source -->
<div class="bb-source-drawer-backdrop" id="bb-source-drawer-backdrop"></div>
<div class="bb-source-drawer" id="bb-source-drawer" aria-hidden="true">
    <div class="bb-source-drawer-header">
        <div class="d-flex align-items-center gap-3">
            <div id="bb-drawer-source-avatar" class="bb-merchant-avatar" style="width: 42px; height: 42px; font-size: 1.1rem;">
                S
            </div>
            <div>
                <h5 class="fw-bold mb-0 text-white" id="bb-drawer-source-title">Джерело</h5>
                <span class="text-secondary small">Помісячна аналітика та транзакції</span>
            </div>
        </div>
        <button type="button" class="bb-source-drawer-close" id="bb-source-drawer-close" aria-label="Закрити">
            ✕
        </button>
    </div>

    <div class="bb-source-drawer-body" id="bb-source-drawer-body">
        <!-- Content will be injected dynamically -->
        <div class="text-center py-5" id="bb-source-drawer-loading">
            <div class="spinner-border text-warning mb-3" role="status">
                <span class="visually-hidden">Завантаження...</span>
            </div>
            <p class="text-secondary small mb-0">Завантажуємо помісячні витрати...</p>
        </div>
        
        <div id="bb-source-drawer-content" style="display: none;">
            <!-- Rendered by JS -->
        </div>
    </div>

    <div class="bb-source-drawer-footer">
        <span class="text-secondary small" id="bb-source-drawer-count">—</span>
        <div class="d-flex gap-2">
            <a href="#" id="bb-source-drawer-all-link" class="btn btn-warning btn-sm rounded-pill px-3 text-dark fw-semibold" target="_blank">
                🔗 Всі транзакції
            </a>
            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" id="bb-source-drawer-close-btn">
                Закрити
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Source tabs in on-page card
    const sourceTabs = document.querySelectorAll('.bb-source-tab-btn');
    sourceTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            sourceTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const targetSourceId = this.getAttribute('data-target-source-id');
            document.querySelectorAll('.bb-source-chart-panel').forEach(function (p) {
                p.style.display = 'none';
            });
            const activePanel = document.getElementById('bb-source-panel-' + targetSourceId);
            if (activePanel) {
                activePanel.style.display = 'block';
            }
        });
    });

    // 2. Drawer management
    const drawer = document.getElementById('bb-source-drawer');
    const backdrop = document.getElementById('bb-source-drawer-backdrop');
    const closeBtn = document.getElementById('bb-source-drawer-close');
    const closeBtnFooter = document.getElementById('bb-source-drawer-close-btn');

    function openDrawer(sourceId, sourceName) {
        if (!sourceId) return;

        drawer.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Set title
        document.getElementById('bb-drawer-source-title').textContent = sourceName || ('Джерело #' + sourceId);
        const avatar = document.getElementById('bb-drawer-source-avatar');
        if (sourceName && sourceName.toLowerCase() === 'spar') {
            avatar.className = 'bb-merchant-avatar spar-logo';
            avatar.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 100 100" fill="#047857">
                <circle cx="50" cy="50" r="46" fill="#047857" />
                <circle cx="50" cy="50" r="39" fill="#ffffff" />
                <polygon points="50,20 62,38 56,38 67,54 60,54 72,72 28,72 40,54 33,54 44,38 38,38" fill="#047857"/>
                <rect x="47" y="72" width="6" height="8" fill="#047857"/>
            </svg>`;
        } else {
            avatar.className = 'bb-merchant-avatar';
            avatar.textContent = sourceName ? sourceName.charAt(0).toUpperCase() : 'S';
        }

        const allLink = document.getElementById('bb-source-drawer-all-link');
        allLink.href = '{{ route("platform.transactions") }}?filter[transaction_source_id]=' + sourceId;

        // Show loading, hide content
        const loading = document.getElementById('bb-source-drawer-loading');
        const content = document.getElementById('bb-source-drawer-content');
        loading.style.display = 'block';
        content.style.display = 'none';

        // Parse query params from current URL
        const urlParams = new URLSearchParams(window.location.search);
        const start = urlParams.get('created_at[start]') || '{{ $startParam }}';
        const end = urlParams.get('created_at[end]') || '{{ $endParam }}';
        const billId = urlParams.get('bill_id') || '{{ $billIdParam }}';

        let fetchUrl = '{{ route("platform.analytic.finance.source-monthly") }}?source_id=' + encodeURIComponent(sourceId);
        if (start && end) {
            fetchUrl += '&start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
        }
        if (billId) {
            fetchUrl += '&bill_id=' + encodeURIComponent(billId);
        }

        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Помилка завантаження');
            return response.json();
        })
        .then(data => {
            renderDrawerContent(data);
            loading.style.display = 'none';
            content.style.display = 'block';
        })
        .catch(err => {
            loading.innerHTML = `<div class="text-danger py-4">Не вдалося завантажити дані (${err.message}). Спробуйте ще раз.</div>`;
        });
    }

    function closeDrawer() {
        drawer.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (closeBtnFooter) closeBtnFooter.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });

    // 3. Attach click handler to source items in the list
    document.querySelectorAll('.bb-clickable-source').forEach(function (el) {
        el.addEventListener('click', function () {
            const srcId = this.getAttribute('data-source-id');
            const srcName = this.getAttribute('data-source-name');
            if (srcId) {
                // If on-page tab exists for this source, activate it too
                const tabBtn = document.querySelector(`.bb-source-tab-btn[data-target-source-id="${srcId}"]`);
                if (tabBtn) {
                    tabBtn.click();
                }
                openDrawer(srcId, srcName);
            }
        });
    });

    // 4. Attach click handler to buttons inside on-page panel
    document.querySelectorAll('.bb-open-source-drawer-btn').forEach(function (el) {
        el.addEventListener('click', function () {
            const srcId = this.getAttribute('data-source-id');
            const srcName = this.getAttribute('data-source-name');
            if (srcId) {
                openDrawer(srcId, srcName);
            }
        });
    });

    // 5. Render drawer content dynamically
    function renderDrawerContent(data) {
        const countEl = document.getElementById('bb-source-drawer-count');
        const transactions = data.transactions || [];
        countEl.textContent = `Знайдено ${transactions.length} транзакцій`;

        const months = data.months || [];
        const ticks = data.ticks || { formatted_max: '4 000 ₴', formatted_mid: '2 000 ₴', zero: '0 ₴' };
        const period = data.period || {};
        const totalFormatted = data.total_formatted || '0.00 ₴';
        const periodLabel = period.label || 'за півроку';

        let html = `
            <!-- Monobank Chart in Drawer -->
            <div class="text-center my-2">
                <div class="bb-mono-pill mb-3">
                    <span>Витратили <strong class="text-warning">${totalFormatted}</strong> ${periodLabel}</span>
                </div>
            </div>

            <div class="bb-mono-chart-container mb-3" style="height: 180px;">
                <div class="bb-mono-grid-lines">
                    <div class="bb-mono-grid-row">
                        <span class="bb-mono-grid-label">${ticks.formatted_max}</span>
                        <div class="bb-mono-grid-line"></div>
                    </div>
                    <div class="bb-mono-grid-row">
                        <span class="bb-mono-grid-label">${ticks.formatted_mid}</span>
                        <div class="bb-mono-grid-line"></div>
                    </div>
                    <div class="bb-mono-grid-row">
                        <span class="bb-mono-grid-label">${ticks.zero}</span>
                        <div class="bb-mono-grid-line bottom-line"></div>
                    </div>
                </div>

                <div class="bb-mono-bars-wrap">
                    ${months.map(m => `
                        <div class="bb-mono-bar-col" title="${m.full_label}: ${m.formatted_amount}">
                            <div class="bb-mono-bar-tooltip">
                                <div>${m.full_label}</div>
                                <div class="text-warning fw-bold">${m.formatted_amount}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">${m.count} покупок</div>
                            </div>
                            <div class="bb-mono-bar-pill" style="height: ${Math.max(4, m.height_percent)}%;"></div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="bb-mono-xaxis mb-4">
                ${months.map(m => `
                    <div class="bb-mono-month-label">${m.short_label}</div>
                `).join('')}
            </div>

            <!-- Transactions list -->
            <h6 class="text-uppercase fw-bold text-secondary mb-3" style="font-size: 0.75rem; letter-spacing: 0.6px;">
                Останні транзакції джерела
            </h6>
        `;

        if (transactions.length === 0) {
            html += `
                <div class="text-center py-4 text-secondary">
                    <p class="mb-0">За обраний період транзакцій не знайдено</p>
                </div>
            `;
        } else {
            html += `<div class="d-flex flex-column gap-1">`;
            transactions.forEach(t => {
                html += `
                    <div class="bb-source-tx-item">
                        <div class="d-flex flex-column">
                            <span class="fw-semibold text-light mb-1">${t.description || 'Витрата'}</span>
                            <div class="d-flex gap-2 text-secondary small">
                                <span>${t.date}</span>
                                <span>•</span>
                                <span class="badge bg-dark border border-secondary text-light">${t.category_name}</span>
                                <span>•</span>
                                <span>${t.bill_name}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="bb-source-tx-amount">${t.formatted_amount}</span>
                            <a href="${t.edit_url}" class="text-secondary hover-text-light" title="Редагувати" target="_blank">
                                ✏️
                            </a>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        }

        document.getElementById('bb-source-drawer-content').innerHTML = html;
    }
});
</script>
