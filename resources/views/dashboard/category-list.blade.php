@php
    $incomeItems = $charts['categories']['income'][0]['items'] ?? [];
    $expenseItems = $charts['categories']['expenses'][0]['items'] ?? [];

    $createdAt = request()->get('created_at', []);
    $startParam = $createdAt['start'] ?? '';
    $endParam = $createdAt['end'] ?? '';
    $billIdParam = request()->get('bill_id', '');
    $activeCategoryId = request()->get('category_id') ?? request()->get('transaction_category_id');
@endphp

<style>
    .bb-clickable-category {
        cursor: pointer;
        padding: 0.5rem 0.75rem;
        margin: -0.25rem -0.75rem;
        border-radius: 8px;
        transition: all 0.18s ease-in-out;
        user-select: none;
        position: relative;
    }
    .bb-clickable-category:hover {
        background-color: #f8fafc;
        transform: translateX(4px);
    }
    .bb-clickable-category.active-category {
        background-color: #eef2ff;
        box-shadow: 0 0 0 2px #4f46e5;
    }
    .bb-category-hint {
        opacity: 0;
        font-size: 0.725rem;
        color: #4f46e5;
        transition: opacity 0.18s ease-in-out;
        font-weight: 600;
        margin-left: 0.4rem;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }
    .bb-clickable-category:hover .bb-category-hint {
        opacity: 1;
    }

    /* Drawer Backdrop */
    .bb-drawer-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(2px);
        z-index: 99990;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease-in-out;
    }
    .bb-drawer-backdrop.show {
        opacity: 1;
        pointer-events: auto;
    }

    /* Slide-over Drawer */
    .bb-drawer {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 530px;
        max-width: 94vw;
        background-color: #ffffff;
        z-index: 99999;
        box-shadow: -10px 0 35px rgba(0, 0, 0, 0.18);
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }
    .bb-drawer.show {
        transform: translateX(0);
    }
    .bb-drawer-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #ffffff;
    }
    .bb-drawer-indicator {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #4f46e5;
        flex-shrink: 0;
    }
    .bb-drawer-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
    }
    .bb-drawer-close {
        background: transparent;
        border: none;
        font-size: 1.35rem;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        line-height: 1;
        transition: all 0.15s;
    }
    .bb-drawer-close:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .bb-drawer-metrics {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #edf2f7;
    }
    .bb-drawer-metric-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
    }
    .bb-drawer-metric-label {
        font-size: 0.725rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .bb-drawer-metric-val {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 0.2rem;
    }
    .bb-drawer-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem 1.5rem;
    }
    .bb-drawer-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #edf2f7;
        background-color: #ffffff;
    }
    .bb-tx-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.65rem;
        transition: all 0.15s ease;
    }
    .bb-tx-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
        background-color: #fbfbfe;
    }
    .bb-tx-date {
        font-size: 0.8rem;
        color: #64748b;
        font-family: monospace;
    }
    .bb-tx-desc {
        font-size: 0.9rem;
        font-weight: 500;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 65%;
    }
    .bb-tx-amount {
        font-size: 0.95rem;
        font-weight: 700;
    }
    .bb-tx-edit-link {
        font-size: 0.75rem;
        color: #4f46e5;
        text-decoration: none;
        opacity: 0.85;
    }
    .bb-tx-edit-link:hover {
        opacity: 1;
        text-decoration: underline;
    }

    /* Skeleton shimmer */
    .bb-skeleton {
        animation: bbShimmer 1.4s infinite ease-in-out;
        background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
        background-size: 200% 100%;
        border-radius: 6px;
    }
    @keyframes bbShimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .bb-skeleton-card {
        border: 1px solid #edf2f7;
        border-radius: 8px;
        padding: 0.85rem;
        margin-bottom: 0.65rem;
    }
</style>

@if(!empty($selected_category))
    <div class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-4 rounded-3 shadow-sm border-0" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #3730a3;">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 1.15rem;">🏷️</span>
            <span>Активний фільтр за категорією: <strong>{{ $selected_category->name }}</strong></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="javascript:void(0)" class="btn btn-sm btn-primary rounded-pill px-3 py-1" style="font-size: 0.8rem; background-color: #4f46e5; border-color: #4f46e5;" onclick="bbScrollToTransactions()">
                До таблиці транзакцій ↓
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" style="font-size: 0.8rem;" onclick="bbClearCategoryFilter()">
                ✕ Скинути
            </button>
        </div>
    </div>
@endif

<div class="row">
    <!-- Income Categories -->
    <div class="col-md-6 mb-4">
        <div class="bg-white rounded shadow-sm p-4 h-100 border-top border-3 border-success">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px;">{{ __('Income Categories') }}</h5>
                <span class="text-muted small" style="font-size: 0.775rem;">(клікніть для перегляду операцій)</span>
            </div>
            <div class="d-flex flex-column gap-3">
                @forelse($incomeItems as $item)
                    @php
                        $isActive = !empty($activeCategoryId) && !empty($item['id']) && (int)$activeCategoryId === (int)$item['id'];
                    @endphp
                    <div class="category-item bb-clickable-category {{ $isActive ? 'active-category' : '' }}"
                         data-category-id="{{ $item['id'] ?? '' }}"
                         data-category-name="{{ $item['name'] }}"
                         data-category-type="income"
                         data-category-amount="{{ $item['amount'] }}"
                         data-category-percent="{{ $item['percent'] }}"
                         data-category-color="{{ $item['color'] }}"
                         title="Натисніть для перегляду транзакцій категорії «{{ $item['name'] }}»">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-dark d-flex align-items-center">
                                {{ $item['name'] }}
                                <span class="bb-category-hint">Транзакції →</span>
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

    <!-- Expense Categories -->
    <div class="col-md-6 mb-4">
        <div class="bg-white rounded shadow-sm p-4 h-100 border-top border-3 border-danger">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px;">{{ __('Expense Categories') }}</h5>
                <span class="text-muted small" style="font-size: 0.775rem;">(клікніть для перегляду операцій)</span>
            </div>
            <div class="d-flex flex-column gap-3">
                @forelse($expenseItems as $item)
                    @php
                        $isActive = !empty($activeCategoryId) && !empty($item['id']) && (int)$activeCategoryId === (int)$item['id'];
                    @endphp
                    <div class="category-item bb-clickable-category {{ $isActive ? 'active-category' : '' }}"
                         data-category-id="{{ $item['id'] ?? '' }}"
                         data-category-name="{{ $item['name'] }}"
                         data-category-type="expenses"
                         data-category-amount="{{ $item['amount'] }}"
                         data-category-percent="{{ $item['percent'] }}"
                         data-category-color="{{ $item['color'] }}"
                         title="Натисніть для перегляду транзакцій категорії «{{ $item['name'] }}»">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-dark d-flex align-items-center">
                                {{ $item['name'] }}
                                <span class="bb-category-hint">Транзакції →</span>
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

<!-- Slide-over Drawer for Quick Viewing Category Transactions -->
<div class="bb-drawer-backdrop" id="bb-category-drawer-backdrop"></div>
<div class="bb-drawer" id="bb-category-drawer" aria-hidden="true">
    <div class="bb-drawer-header">
        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
            <span class="bb-drawer-indicator" id="bb-drawer-indicator"></span>
            <div style="min-width: 0;">
                <h5 class="bb-drawer-title mb-0 text-truncate" id="bb-drawer-title">Категорія</h5>
                <div class="mt-1" style="font-size: 0.775rem; color: #64748b;">
                    <span id="bb-drawer-period-badge" class="badge bg-light text-dark border">Період</span>
                    <span id="bb-drawer-bill-badge" class="badge bg-light text-dark border ms-1" style="display: none;">Рахунок</span>
                </div>
            </div>
        </div>
        <button type="button" class="bb-drawer-close" id="bb-drawer-close" title="Закрити (Esc)">✕</button>
    </div>

    <!-- Summary Metrics -->
    <div class="bb-drawer-metrics">
        <div class="bb-drawer-metric-box">
            <span class="bb-drawer-metric-label">Сума за період</span>
            <span class="bb-drawer-metric-val" id="bb-drawer-total">—</span>
        </div>
        <div class="bb-drawer-metric-box">
            <span class="bb-drawer-metric-label">Кількість операцій</span>
            <span class="bb-drawer-metric-val" id="bb-drawer-count">—</span>
        </div>
    </div>

    <!-- Content: list of transactions or skeleton -->
    <div class="bb-drawer-content" id="bb-drawer-content">
        <!-- Injected via JavaScript -->
    </div>

    <!-- Footer actions -->
    <div class="bb-drawer-footer">
        <a href="#" class="btn btn-primary w-100 mb-2 d-flex align-items-center justify-content-center gap-2 py-2" id="bb-drawer-registry-link" target="_blank">
            <span>Відкрити в загальному реєстрі транзакцій</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/><path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/></svg>
        </a>
        <button type="button" class="btn btn-outline-secondary w-100 py-1" id="bb-drawer-filter-on-page-btn" style="font-size: 0.85rem;">
            <span>Фільтрувати в таблиці на цій сторінці ↓</span>
        </button>
    </div>
</div>

<script>
    (function() {
        function initCategoryDrawer() {
            var clickableCategories = document.querySelectorAll('.bb-clickable-category');
            var drawer = document.getElementById('bb-category-drawer');
            var backdrop = document.getElementById('bb-category-drawer-backdrop');
            var closeBtn = document.getElementById('bb-drawer-close');
            var titleEl = document.getElementById('bb-drawer-title');
            var indicatorEl = document.getElementById('bb-drawer-indicator');
            var periodBadge = document.getElementById('bb-drawer-period-badge');
            var billBadge = document.getElementById('bb-drawer-bill-badge');
            var totalEl = document.getElementById('bb-drawer-total');
            var countEl = document.getElementById('bb-drawer-count');
            var contentEl = document.getElementById('bb-drawer-content');
            var registryLink = document.getElementById('bb-drawer-registry-link');
            var filterOnPageBtn = document.getElementById('bb-drawer-filter-on-page-btn');

            if (!drawer || !backdrop) return;

            var currentCategoryId = null;

            function openDrawer(categoryId, name, color, type, initialAmount) {
                currentCategoryId = categoryId;
                drawer.classList.add('show');
                backdrop.classList.add('show');
                document.body.style.overflow = 'hidden';

                titleEl.textContent = name;
                indicatorEl.style.backgroundColor = color || '#4f46e5';
                totalEl.textContent = initialAmount || 'Завантаження...';
                countEl.textContent = '...';

                // Read active filters from current URL
                var url = new URL(window.location.href);
                var start = url.searchParams.get('created_at[start]') || "{{ $startParam }}";
                var end = url.searchParams.get('created_at[end]') || "{{ $endParam }}";
                var billId = url.searchParams.get('bill_id') || "{{ $billIdParam }}";

                if (start && end) {
                    periodBadge.textContent = '📅 ' + start + ' — ' + end;
                } else {
                    periodBadge.textContent = '📅 Останній рік';
                }

                var billBtnText = document.getElementById('bb-bill-trigger-btn');
                if (billId && billBtnText) {
                    var billName = billBtnText.querySelector('.bb-bill-btn-text')?.textContent?.trim() || 'Рахунок #' + billId;
                    billBadge.textContent = '🏦 ' + billName;
                    billBadge.style.display = 'inline-block';
                } else {
                    billBadge.style.display = 'none';
                }

                // Setup fallback URL for registry
                var regUrl = new URL('/transactions', window.location.origin);
                if (categoryId) regUrl.searchParams.set('transaction_category_id', categoryId);
                if (start) regUrl.searchParams.set('created_at[start]', start);
                if (end) regUrl.searchParams.set('created_at[end]', end);
                if (billId) regUrl.searchParams.set('finance_bill_id', billId);
                registryLink.href = regUrl.toString();

                filterOnPageBtn.onclick = function() {
                    var pageUrl = new URL(window.location.href);
                    if (categoryId) {
                        pageUrl.searchParams.set('category_id', categoryId);
                    }
                    pageUrl.searchParams.delete('page');
                    window.location.href = pageUrl.toString();
                };

                // Show shimmer skeleton
                contentEl.innerHTML = `
                    <div class="bb-skeleton-card"><div class="bb-skeleton" style="height: 14px; width: 40%; margin-bottom: 8px;"></div><div class="bb-skeleton" style="height: 18px; width: 85%;"></div></div>
                    <div class="bb-skeleton-card"><div class="bb-skeleton" style="height: 14px; width: 35%; margin-bottom: 8px;"></div><div class="bb-skeleton" style="height: 18px; width: 70%;"></div></div>
                    <div class="bb-skeleton-card"><div class="bb-skeleton" style="height: 14px; width: 45%; margin-bottom: 8px;"></div><div class="bb-skeleton" style="height: 18px; width: 80%;"></div></div>
                `;

                // Fetch transactions via AJAX endpoint
                var apiUrl = new URL('/analytic/finance/category-transactions', window.location.origin);
                if (categoryId) apiUrl.searchParams.set('category_id', categoryId);
                if (start) apiUrl.searchParams.set('start', start);
                if (end) apiUrl.searchParams.set('end', end);
                if (billId) apiUrl.searchParams.set('bill_id', billId);
                if (type) apiUrl.searchParams.set('type', type);

                fetch(apiUrl.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) {
                    if (!res.ok) throw new Error('Помилка сервера: ' + res.status);
                    return res.json();
                })
                .then(function(data) {
                    totalEl.textContent = data.total_formatted || '0.00 ₴';
                    countEl.textContent = data.count || 0;

                    if (data.transactions_screen_url) {
                        registryLink.href = data.transactions_screen_url;
                    }

                    if (!data.transactions || data.transactions.length === 0) {
                        contentEl.innerHTML = `
                            <div class="text-center py-5 text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-inbox mb-2 text-secondary" viewBox="0 0 16 16">
                                    <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625A.5.5 0 0 1 16 8.5V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .11-.312l3.7-4.625zM3.81 5h8.38l-2.4-3H6.21L3.81 5zM1 8.5v4.5A1 1 0 0 0 2 14h12a1 1 0 0 0 1-1V8.5H11.5a.5.5 0 0 0-.5.5 2.5 2.5 0 0 1-5 0 .5.5 0 0 0-.5-.5H1z"/>
                                </svg>
                                <p class="mb-0 fw-semibold">Немає транзакцій</p>
                                <p class="text-muted small">За вибраний період та рахунок операцій у цій категорії не знайдено.</p>
                            </div>
                        `;
                        return;
                    }

                    var html = '';
                    data.transactions.forEach(function(tx) {
                        var amountClass = tx.type === 'income' ? 'text-success' : 'text-danger';
                        html += `
                            <div class="bb-tx-card">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bb-tx-date">${tx.date}</span>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">${tx.bill_name}</span>
                                    </div>
                                    <a href="${tx.edit_url}" class="bb-tx-edit-link" target="_blank" title="Редагувати в новій вкладці">
                                        ✏️ Редагувати
                                    </a>
                                </div>
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <span class="bb-tx-desc" title="${tx.description}">${tx.description}</span>
                                    <span class="bb-tx-amount ${amountClass}">${tx.formatted_amount}</span>
                                </div>
                            </div>
                        `;
                    });

                    contentEl.innerHTML = html;
                })
                .catch(function(err) {
                    contentEl.innerHTML = `
                        <div class="alert alert-danger my-3" role="alert">
                            <strong>Помилка завантаження:</strong> ${err.message}
                        </div>
                    `;
                });
            }

            function closeDrawer() {
                drawer.classList.remove('show');
                backdrop.classList.remove('show');
                document.body.style.overflow = '';
            }

            clickableCategories.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    var categoryId = item.getAttribute('data-category-id');
                    var name = item.getAttribute('data-category-name');
                    var color = item.getAttribute('data-category-color');
                    var type = item.getAttribute('data-category-type');
                    var amount = item.getAttribute('data-category-amount');
                    openDrawer(categoryId, name, color, type, amount);
                });
            });

            closeBtn.addEventListener('click', closeDrawer);
            backdrop.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && drawer.classList.contains('show')) {
                    closeDrawer();
                }
            });
        }

        // Global functions for on-page filter
        window.bbClearCategoryFilter = function() {
            var url = new URL(window.location.href);
            url.searchParams.delete('category_id');
            url.searchParams.delete('transaction_category_id');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        };

        window.bbScrollToTransactions = function() {
            var accordionBtn = document.querySelector('.accordion-button');
            if (accordionBtn) {
                if (accordionBtn.classList.contains('collapsed')) {
                    accordionBtn.click();
                }
                accordionBtn.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        // Auto-expand accordion if category is filtered on page
        var currentParams = new URLSearchParams(window.location.search);
        if (currentParams.has('category_id') || currentParams.has('transaction_category_id')) {
            setTimeout(function() {
                var accordionBtn = document.querySelector('.accordion-button');
                if (accordionBtn && accordionBtn.classList.contains('collapsed')) {
                    accordionBtn.click();
                }
            }, 300);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCategoryDrawer);
        } else {
            initCategoryDrawer();
        }
        document.addEventListener('turbo:load', initCategoryDrawer);
        document.addEventListener('turbolinks:load', initCategoryDrawer);
    })();
</script>
