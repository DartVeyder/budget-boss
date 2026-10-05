<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-calendar4-week text-primary" viewBox="0 0 16 16">
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm13 3H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z"/>
                    <path d="M11 7.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm9 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                </svg>
                Поквартальний фінансовий звіт &bull; {{ $selectedYear }} рік
            </h5>
            <p class="text-muted small mb-0">Розподіл доходу, нараховані та сплачені податки за кожний квартал.</p>
        </div>
        <div>
            <a href="{{ route('platform.fop.tax', ['year' => $selectedYear]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                Податковий календар &rarr;
            </a>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-3 border-0">Квартал</th>
                        <th class="border-0 text-end">Дохід</th>
                        <th class="border-0 text-end">Накопичено</th>
                        <th class="border-0 text-center">Оплат</th>
                        <th class="border-0 text-end">ЄП (5%)</th>
                        <th class="border-0 text-end">ВЗ (1%)</th>
                        <th class="border-0 text-end">ЄСВ</th>
                        <th class="border-0 text-end fw-bold">Всього податків</th>
                        <th class="border-0 text-end">Сплачено</th>
                        <th class="border-0 text-end fw-bold text-primary">Чистий дохід</th>
                        <th class="pe-3 border-0 text-center">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalIncome = 0;
                        $totalSingleTax = 0;
                        $totalMilitaryTax = 0;
                        $totalEsv = 0;
                        $totalTax = 0;
                        $totalPaid = 0;
                        $totalNet = 0;
                        $totalTx = 0;
                    @endphp
                    @foreach($quarterly as $q)
                        @php
                            $totalIncome += $q['income'];
                            $totalSingleTax += $q['single_tax'];
                            $totalMilitaryTax += $q['military_tax'];
                            $totalEsv += $q['esv'];
                            $totalTax += $q['total_tax'];
                            $totalPaid += $q['tax_paid'];
                            $totalNet += $q['net_income'];
                            $totalTx += $q['tx_count'];
                        @endphp
                        <tr class="{{ $selectedQuarter == $q['quarter'] ? 'table-primary-subtle' : '' }}">
                            <td class="ps-3 fw-bold text-dark">
                                <span class="badge bg-secondary-subtle text-secondary me-1">Q{{ $q['quarter'] }}</span>
                                {{ $q['label'] }}
                            </td>
                            <td class="text-end fw-semibold text-dark">
                                {{ number_format($q['income'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-end text-muted small">
                                {{ number_format($q['cumulative_income'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $q['tx_count'] }}</span>
                            </td>
                            <td class="text-end text-muted">
                                {{ number_format($q['single_tax'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-end text-muted">
                                {{ number_format($q['military_tax'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-end text-muted">
                                {{ number_format($q['esv'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-end fw-bold text-warning" style="color: #ea580c !important;">
                                {{ number_format($q['total_tax'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-end text-success fw-semibold">
                                {{ number_format($q['tax_paid'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="text-end fw-bold text-primary">
                                {{ number_format($q['net_income'], 2, '.', ' ') }} ₴
                            </td>
                            <td class="pe-3 text-center">
                                @if($q['income'] == 0)
                                    <span class="badge bg-light text-muted border">Немає доходу</span>
                                @elseif($q['is_paid'])
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                        <i class="bi bi-check2-circle me-1"></i>Сплачено
                                    </span>
                                @elseif($q['tax_paid'] > 0)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">
                                        Частково
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                        Очікує сплати
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3 text-uppercase">Разом за рік:</td>
                        <td class="text-end text-success fs-6">{{ number_format($totalIncome, 2, '.', ' ') }} ₴</td>
                        <td class="text-end text-muted">—</td>
                        <td class="text-center">{{ $totalTx }}</td>
                        <td class="text-end">{{ number_format($totalSingleTax, 2, '.', ' ') }} ₴</td>
                        <td class="text-end">{{ number_format($totalMilitaryTax, 2, '.', ' ') }} ₴</td>
                        <td class="text-end">{{ number_format($totalEsv, 2, '.', ' ') }} ₴</td>
                        <td class="text-end text-warning fs-6" style="color: #ea580c !important;">{{ number_format($totalTax, 2, '.', ' ') }} ₴</td>
                        <td class="text-end text-success fs-6">{{ number_format($totalPaid, 2, '.', ' ') }} ₴</td>
                        <td class="text-end text-primary fs-6">{{ number_format($totalNet, 2, '.', ' ') }} ₴</td>
                        <td class="pe-3 text-center">
                            @if($totalPaid >= $totalTax && $totalTax > 0)
                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1">Все сплачено</span>
                            @elseif($totalTax > $totalPaid)
                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1">Борг: {{ number_format($totalTax - $totalPaid, 0, '.', ' ') }} ₴</span>
                            @else
                                <span class="badge bg-secondary text-white rounded-pill px-2.5 py-1">0 ₴</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
