@if(isset($report))
<div class="mb-4">
    <!-- Header with Year Filter & Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3">
                <span class="text-muted small fw-semibold text-uppercase">Річний дохід ФОП</span>
                <h3 class="fw-bold text-success mb-1 mt-2">
                    {{ number_format($report['total_year_income'], 2, '.', ' ') }} ₴
                </h3>
                <span class="text-muted small">За {{ $report['year'] }} календарний рік</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3">
                <span class="text-muted small fw-semibold text-uppercase">Єдиний податок (разом)</span>
                <h3 class="fw-bold text-primary mb-1 mt-2">
                    {{ number_format($report['total_year_single_tax'], 2, '.', ' ') }} ₴
                </h3>
                <span class="text-muted small">Ставка {{ $report['single_tax_percent'] }}%</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3">
                <span class="text-muted small fw-semibold text-uppercase">ЄСВ за рік (разом)</span>
                @if($report['is_esv_exempt'])
                    <h3 class="fw-bold text-muted mb-1 mt-2">0.00 ₴</h3>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill small">Звільнено від сплати</span>
                @else
                    <h3 class="fw-bold text-info mb-1 mt-2">
                        {{ number_format($report['total_year_esv'], 2, '.', ' ') }} ₴
                    </h3>
                    <span class="text-muted small">{{ number_format($report['monthly_esv'], 2, '.', ' ') }} ₴ / місяць</span>
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                <span class="small fw-semibold text-uppercase text-white-50">Всього податків за рік</span>
                <h3 class="fw-bold text-white mb-1 mt-2">
                    {{ number_format($report['total_year_taxes'], 2, '.', ' ') }} ₴
                </h3>
                <span class="text-white-50 small">ЄП + Військовий збір + ЄСВ</span>
            </div>
        </div>
    </div>

    <!-- Quarters Accordion / Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-calendar-range text-primary" viewBox="0 0 16 16">
                        <path d="M9 7a1 1 0 0 1 1-1h5v2h-5a1 1 0 0 1-1-1zM1 9h4a1 1 0 0 1 0 2H1V9z"/>
                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                    </svg>
                    Податковий розрахунок та календар за кварталами ({{ $report['year'] }})
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Оберіть рік:</span>
                <div class="btn-group btn-group-sm">
                    @foreach([Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year, Carbon\Carbon::now()->year + 1] as $y)
                        <a href="{{ request()->fullUrlWithQuery(['year' => $y]) }}" 
                           class="btn {{ ($report['year'] == $y) ? 'btn-primary text-white' : 'btn-outline-secondary' }}">
                            {{ $y }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Квартал / Період</th>
                        <th>Дохід за квартал</th>
                        <th>Єдиний податок ({{ $report['single_tax_percent'] }}%)</th>
                        <th>Військовий збір (1%)</th>
                        <th>ЄСВ</th>
                        <th>Разом до сплати</th>
                        <th>Найближчий дедлайн</th>
                        <th class="text-end pe-4">Дія</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['quarters'] as $qNum => $qData)
                        <tr class="{{ $qData['is_current'] ? 'table-primary-subtle' : '' }}">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $qData['is_current'] ? 'bg-primary' : 'bg-secondary' }} rounded-circle p-2" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                        {{ $qNum }}
                                    </span>
                                    <div>
                                        <strong class="text-dark d-block">{{ $qData['name'] }}</strong>
                                        <span class="text-muted" style="font-size: 0.75rem;">
                                            {{ $qData['start_date']->format('d.m') }} — {{ $qData['end_date']->format('d.m.Y') }}
                                        </span>
                                    </div>
                                    @if($qData['is_current'])
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-1" style="font-size: 0.7rem;">Поточний</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong class="text-success">{{ number_format($qData['income'], 2, '.', ' ') }} ₴</strong>
                            </td>
                            <td>
                                <strong>{{ number_format($qData['single_tax'], 2, '.', ' ') }} ₴</strong>
                            </td>
                            <td>
                                <span class="text-muted">{{ number_format($qData['military_tax'], 2, '.', ' ') }} ₴</span>
                            </td>
                            <td>
                                @if($report['is_esv_exempt'])
                                    <span class="badge bg-light text-muted border rounded-pill small">Звільнено</span>
                                @else
                                    <span class="text-muted">{{ number_format($qData['esv'], 2, '.', ' ') }} ₴</span>
                                @endif
                            </td>
                            <td>
                                <h6 class="fw-bold text-dark mb-0">
                                    {{ number_format($qData['total_tax'], 2, '.', ' ') }} ₴
                                </h6>
                            </td>
                            <td>
                                @php
                                    $singleTaxDeadline = $qData['deadlines']['single_tax'];
                                    $esvDeadline = $qData['deadlines']['esv'];
                                    $daysLeft = $singleTaxDeadline['days_left'];
                                @endphp
                                <div>
                                    <span class="small fw-semibold d-block">
                                        ЄП: {{ $singleTaxDeadline['date']->format('d.m.Y') }}
                                    </span>
                                    @if(!$report['is_esv_exempt'])
                                    <span class="small text-muted d-block" style="font-size: 0.75rem;">
                                        ЄСВ: {{ $esvDeadline['date']->format('d.m.Y') }}
                                    </span>
                                    @endif
                                    @if($daysLeft > 0 && $daysLeft <= 14)
                                        <span class="badge bg-warning text-dark rounded-pill" style="font-size: 0.7rem;">Залишилось {{ $daysLeft }} дн.</span>
                                    @elseif($daysLeft > 14)
                                        <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.7rem;">Через {{ $daysLeft }} дн.</span>
                                    @elseif($qData['is_past'] && $daysLeft < 0)
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.7rem;">Минув</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Сплатити
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <form method="POST" action="{{ route('platform.fop.tax.pay') }}">
                                                @csrf
                                                <input type="hidden" name="quarter" value="{{ $qNum }}">
                                                <input type="hidden" name="year" value="{{ $report['year'] }}">
                                                <input type="hidden" name="tax_type" value="single_tax">
                                                <input type="hidden" name="amount" value="{{ $qData['single_tax'] }}">
                                                <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center" {{ $qData['single_tax'] <= 0 ? 'disabled' : '' }}>
                                                    <span>Сплатити ЄП ({{ $qNum }} кв)</span>
                                                    <strong class="ms-2 text-primary">{{ number_format($qData['single_tax'], 2, '.', ' ') }} ₴</strong>
                                                </button>
                                            </form>
                                        </li>
                                        @if(!$report['is_esv_exempt'])
                                        <li>
                                            <form method="POST" action="{{ route('platform.fop.tax.pay') }}">
                                                @csrf
                                                <input type="hidden" name="quarter" value="{{ $qNum }}">
                                                <input type="hidden" name="year" value="{{ $report['year'] }}">
                                                <input type="hidden" name="tax_type" value="esv">
                                                <input type="hidden" name="amount" value="{{ $qData['esv'] }}">
                                                <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center">
                                                    <span>Сплатити ЄСВ ({{ $qNum }} кв)</span>
                                                    <strong class="ms-2 text-info">{{ number_format($qData['esv'], 2, '.', ' ') }} ₴</strong>
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item small text-muted" href="{{ route('platform.fop.ledger', ['year' => $report['year'], 'quarter' => $qNum]) }}">
                                                📖 Переглянути записи за {{ $qNum }} кв.
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Declaration Cumulative Summary Block -->
    @if(isset($declaration))
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">ДПС України &bull; Спрощена система оподаткування</span>
                <h5 class="fw-bold text-dark mb-0 mt-1 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-file-earmark-text text-primary" viewBox="0 0 16 16">
                        <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                        <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                    </svg>
                    Показники для Податкової декларації платника Єдиного Податку (Форма F0103308)
                </h5>
            </div>
            <div class="btn-group btn-group-sm">
                @foreach([1 => 'I кв.', 2 => 'Півріччя', 3 => '9 місяців', 4 => 'Рік'] as $qId => $qLabel)
                    <a href="{{ request()->fullUrlWithQuery(['declaration_quarter' => $qId]) }}" 
                       class="btn {{ ($declaration['quarter'] == $qId) ? 'btn-dark text-white' : 'btn-outline-secondary' }}">
                        {{ $qLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        <p class="text-muted small mb-3">
            Податкова декларація платника єдиного податку заповнюється <strong>наростаючим підсумком</strong> з 1 січня звітного року. Нижче наведено готові розрахункові суми для заповнення граф декларації за обраний період (<strong>{{ $declaration['quarter_title'] }} {{ $declaration['year'] }} р.</strong>):
        </p>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 140px;">Рядок декларації</th>
                        <th>Назва показника декларації</th>
                        <th style="width: 130px;" class="text-center">Ставка</th>
                        <th style="width: 220px;" class="text-end">Значення для внесення (грн)</th>
                    </tr>
                </thead>
                <tbody class="align-middle">
                    <tr>
                        <td class="fw-bold text-center">Рядок 06</td>
                        <td>Обсяг доходу за звітний (податковий) період, що оподатковується за ставкою {{ $declaration['rate_percent'] }}% (наростаючим підсумком)</td>
                        <td class="text-center">{{ $declaration['rate_percent'] }}%</td>
                        <td class="text-end fw-bold text-success fs-6">
                            {{ number_format($declaration['line_income'], 2, '.', ' ') }} ₴
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-center">Рядок 11</td>
                        <td>Нараховано всього за звітний (податковий) період (р. 06 &times; {{ $declaration['rate_percent'] }}%)</td>
                        <td class="text-center">—</td>
                        <td class="text-end fw-bold text-dark fs-6">
                            {{ number_format($declaration['line_accrued_tax'], 2, '.', ' ') }} ₴
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-center">Рядок 12</td>
                        <td>Нараховано за попередній звітний (податковий) період (значення рядка 11 попередньої декларації)</td>
                        <td class="text-center">—</td>
                        <td class="text-end text-muted fs-6">
                            {{ number_format($declaration['line_previous_tax'], 2, '.', ' ') }} ₴
                        </td>
                    </tr>
                    <tr class="table-primary-subtle">
                        <td class="fw-bold text-center text-primary">Рядок 14</td>
                        <td>
                            <strong>Сума єдиного податку, яка підлягає нарахуванню та сплаті в бюджет за підсумками поточного звітного періоду (р. 11 - р. 12)</strong>
                        </td>
                        <td class="text-center">—</td>
                        <td class="text-end fw-bold text-primary fs-5">
                            {{ number_format($declaration['line_payable_tax'], 2, '.', ' ') }} ₴
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-center text-muted">Військовий збір</td>
                        <td>Військовий збір платника єдиного податку 3 групи (1% від доходу за період)</td>
                        <td class="text-center">1%</td>
                        <td class="text-end fw-semibold text-dark fs-6">
                            {{ number_format($declaration['military_tax'], 2, '.', ' ') }} ₴
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif
