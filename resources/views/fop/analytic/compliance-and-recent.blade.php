<div class="row g-4 mb-4">
    <!-- Compliance Widget -->
    <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-file-earmark-check text-primary" viewBox="0 0 16 16">
                                <path d="M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l3-3z"/>
                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                            </svg>
                            Закриття актами
                        </h5>
                        <span class="badge {{ $compliance['compliance_percent'] >= 80 ? 'bg-success' : ($compliance['compliance_percent'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }} rounded-pill px-2.5 py-1">
                            {{ $compliance['compliance_percent'] }}%
                        </span>
                    </div>

                    <p class="text-muted small mb-4">
                        Відсоток надходжень, за якими вже сформовано та підписано Акти наданих послуг.
                    </p>

                    <div class="d-flex justify-content-center mb-3">
                        <div class="position-relative d-inline-flex align-items-center justify-content-center" style="width: 130px; height: 130px;">
                            <svg class="w-100 h-100" viewBox="0 0 36 36">
                                <path class="text-secondary" stroke-width="3.5" stroke="#e2e8f0" fill="none"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-primary" stroke-width="3.5" stroke-dasharray="{{ $compliance['compliance_percent'] }}, 100" stroke="#4f46e5" stroke-linecap="round" fill="none"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="position-absolute text-center">
                                <span class="fs-4 fw-bolder text-dark">{{ $compliance['compliance_percent'] }}%</span>
                                <span class="d-block text-muted" style="font-size: 0.7rem;">закрито</span>
                            </div>
                        </div>
                    </div>

                    <div class="list-group list-group-flush border-top pt-2">
                        <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Закритих оплат:</span>
                            <strong class="text-success">{{ $compliance['closed_tx'] }} ({{ number_format($compliance['closed_amount'], 2, '.', ' ') }} ₴)</strong>
                        </div>
                        <div class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Очікують актів:</span>
                            <strong class="{{ $compliance['unclosed_tx'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $compliance['unclosed_tx'] }} ({{ number_format($compliance['unclosed_amount'], 2, '.', ' ') }} ₴)</strong>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2">
                    <a href="{{ route('platform.acts') }}" class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                        Перейти до розділу «Акти послуг» &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-lg-8">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-clock-history text-primary" viewBox="0 0 16 16">
                            <path d="m8.515 1.019-.57-.597a.5.5 0 0 0-.707 0l-.57.597a7.5 7.5 0 1 0 1.847 0zM8 15a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13z"/>
                            <path d="M7.5 3a.5.5 0 0 0-.5.5v5.21l3.248 1.856a.5.5 0 0 0 .496-.868L8 8.01V3.5a.5.5 0 0 0-.5-.5z"/>
                        </svg>
                        Останні надходження ФОП
                    </h5>
                    <p class="text-muted small mb-0">Хронологія останніх зарахувань на рахунок ФОП та статус документів.</p>
                </div>
                <a href="{{ route('platform.transactions') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    Всі транзакції &rarr;
                </a>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small text-muted">
                            <tr>
                                <th class="border-0 ps-3">Дата</th>
                                <th class="border-0">Замовник / Контрагент</th>
                                <th class="border-0 text-end">Сума</th>
                                <th class="border-0 text-center">Акт</th>
                                <th class="border-0 text-end pe-3">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td class="ps-3 text-muted small" style="white-space: nowrap;">
                                        <div class="fw-semibold text-dark">{{ $tx->created_at ? $tx->created_at->format('d.m.Y') : '—' }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $tx->created_at ? $tx->created_at->format('H:i') : '' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $tx->customer?->name ?? ($tx->source_name ?: 'Без замовника') }}
                                        </div>
                                        @if($tx->counterparty)
                                            <span class="text-muted small d-inline-block text-truncate" style="max-width: 260px;" title="{{ $tx->counterparty->name }}">
                                                <i class="bi bi-person-badge"></i> {{ $tx->counterparty->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-success fs-6" style="white-space: nowrap;">
                                        +{{ number_format($tx->currency_amount, 2, '.', ' ') }} ₴
                                    </td>
                                    <td class="text-center" style="white-space: nowrap;">
                                        @if($tx->act)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                                <i class="bi bi-file-earmark-check me-0.5"></i>Акт №{{ $tx->act->act_number }}
                                            </span>
                                        @else
                                            <a href="{{ route('platform.acts.create', ['transaction_id' => $tx->id]) }}" 
                                               class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill text-decoration-none hover-primary">
                                                + Створити акт
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3" style="white-space: nowrap;">
                                        <a href="{{ route('platform.transactions.edit', $tx->id) }}" class="btn btn-sm btn-outline-secondary p-1 px-2 rounded-3" title="Редагувати">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Немає операцій
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
