<div class="row g-4 mb-4">
    <!-- Customers Breakdown -->
    <div class="col-lg-7">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-people text-primary" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 1 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                        </svg>
                        Аналітика по замовниках
                    </h5>
                    <p class="text-muted small mb-0">Частка доходу та стан підписання актів за обраний період.</p>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-muted">
                            <tr>
                                <th class="border-0 ps-3">Замовник</th>
                                <th class="border-0 text-end">Сума доходу</th>
                                <th class="border-0 text-center" style="width: 140px;">Частка (%)</th>
                                <th class="border-0 text-center">Оплат</th>
                                <th class="border-0 text-center">Актів</th>
                                <th class="border-0 text-end pe-3">Остання оплата</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $c)
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: {{ $c['color'] }};"></span>
                                            @if($c['customer_id'])
                                                <a href="{{ route('platform.customers.edit', $c['customer_id']) }}" class="text-decoration-none text-dark hover-primary">
                                                    {{ $c['name'] }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ $c['name'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        {{ $c['formatted_amount'] }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background-color: #f1f5f9;">
                                                <div class="progress-bar rounded-pill" role="progressbar" 
                                                     style="width: {{ $c['percent'] }}%; background-color: {{ $c['color'] }};" 
                                                     aria-valuenow="{{ $c['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="text-muted small fw-semibold" style="min-width: 40px;">{{ $c['percent'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $c['tx_count'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $c['acts_count'] > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' }}">
                                            {{ $c['acts_count'] }} акт.
                                        </span>
                                    </td>
                                    <td class="text-end text-muted small pe-3">
                                        {{ $c['last_payment_date'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Немає даних за обраний період
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Counterparties Breakdown -->
    <div class="col-lg-5">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-badge text-primary" viewBox="0 0 16 16">
                            <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0h-7zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2.5z"/>
                        </svg>
                        Контрагенти-платники
                    </h5>
                    <p class="text-muted small mb-0">ФОП та юридичні особи, від яких надійшли кошти.</p>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-muted position-sticky top-0 bg-white" style="z-index: 1;">
                            <tr>
                                <th class="border-0 ps-2">Платник / ІПН</th>
                                <th class="border-0 text-end">Сума</th>
                                <th class="border-0 text-center">Оплат</th>
                                <th class="border-0 text-end pe-2">Частка</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($counterparties as $cp)
                                <tr>
                                    <td class="ps-2">
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="{{ $cp['name'] }}">
                                            {{ $cp['name'] }}
                                        </div>
                                        <span class="text-muted small">ІПН: {{ $cp['ipn'] }}</span>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        {{ $cp['formatted_amount'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $cp['tx_count'] }}</span>
                                    </td>
                                    <td class="text-end pe-2">
                                        <span class="fw-bold text-primary small">{{ $cp['percent'] }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Немає даних
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
