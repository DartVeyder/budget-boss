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
                <span class="text-muted small fw-semibold text-uppercase">Єдиний податок ({{ $report['single_tax_percent'] }}%)</span>
                <h3 class="fw-bold text-primary mb-1 mt-2">
                    {{ number_format($report['total_year_single_tax'], 2, '.', ' ') }} ₴
                </h3>
                <span class="text-muted small">+ Військовий збір ({{ $report['military_tax_percent'] ?? 1 }}%): <strong class="text-dark">{{ number_format($report['total_year_military_tax'] ?? 0, 2, '.', ' ') }} ₴</strong></span>
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
                <span class="text-white-50 small">ЄП ({{ number_format($report['total_year_single_tax'], 0, '.', ' ') }}) + ВЗ ({{ number_format($report['total_year_military_tax'] ?? 0, 0, '.', ' ') }}) + ЄСВ ({{ number_format($report['total_year_esv'], 0, '.', ' ') }}) ₴</span>
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
                        <th>Документи</th>
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
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    @if(($qData['documents_count'] ?? 0) > 0)
                                        <a href="{{ request()->fullUrlWithQuery(['doc_quarter' => $qNum]) }}#quarter-docs" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill text-decoration-none py-1 px-2" title="Переглянути документи за {{ $qNum }} кв.">
                                            📎 {{ $qData['documents_count'] }} док.
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted border rounded-pill py-1 px-2">0 док.</span>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-0" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; line-height: 1;" title="Додати документ до {{ $qNum }} кварталу" data-bs-toggle="modal" data-bs-target="#uploadDocModal" onclick="setModalQuarter({{ $qNum }})">
                                        +
                                    </button>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Дії
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
                                        <li>
                                            <form method="POST" action="{{ route('platform.fop.tax.pay') }}">
                                                @csrf
                                                <input type="hidden" name="quarter" value="{{ $qNum }}">
                                                <input type="hidden" name="year" value="{{ $report['year'] }}">
                                                <input type="hidden" name="tax_type" value="military_tax">
                                                <input type="hidden" name="amount" value="{{ $qData['military_tax'] }}">
                                                <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center" {{ $qData['military_tax'] <= 0 ? 'disabled' : '' }}>
                                                    <span>Сплатити ВЗ ({{ $qNum }} кв)</span>
                                                    <strong class="ms-2 text-dark">{{ number_format($qData['military_tax'], 2, '.', ' ') }} ₴</strong>
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
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#uploadDocModal" onclick="setModalQuarter({{ $qNum }})">
                                                📎 Додати документ до {{ $qNum }} кв.
                                            </button>
                                        </li>
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

    <!-- Quarterly Documents Section -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden" id="quarter-docs">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-folder-check text-primary" viewBox="0 0 16 16">
                        <path d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 3a1 1 0 0 1 1-1h3.672z"/>
                        <path d="M15.854 10.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708l1.146 1.147 2.646-2.647a.5.5 0 0 1 .708 0z"/>
                    </svg>
                    Документи та звітність за кварталами ({{ $report['year'] }})
                </h5>
                <span class="text-muted small">Податкові декларації, квитанції №1 / №2 від ДПС, платіжні доручення та банківські виписки</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2z"/>
                        <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                    </svg>
                    + Додати документ
                </button>
            </div>
        </div>

        <!-- Filter tabs for quarters -->
        <div class="bg-light px-4 py-2 border-bottom d-flex align-items-center flex-wrap gap-2">
            <span class="small text-muted me-2">Фільтр кварталу:</span>
            <div class="btn-group btn-group-sm">
                <a href="{{ request()->fullUrlWithQuery(['doc_quarter' => '']) }}#quarter-docs" 
                   class="btn {{ empty($docFilterQuarter) ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">
                    Всі ({{ $report['total_year_documents'] ?? 0 }})
                </a>
                @foreach([1, 2, 3, 4] as $q)
                    <a href="{{ request()->fullUrlWithQuery(['doc_quarter' => $q]) }}#quarter-docs" 
                       class="btn {{ ($docFilterQuarter == $q) ? 'btn-primary text-white' : 'btn-outline-secondary' }}">
                        {{ $q }} квартал ({{ $report['quarters'][$q]['documents_count'] ?? 0 }})
                    </a>
                @endforeach
            </div>
        </div>

        @if(isset($quarterDocuments) && $quarterDocuments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 110px;">Квартал</th>
                            <th style="width: 220px;">Тип документа</th>
                            <th>Назва та файл</th>
                            <th>Примітка</th>
                            <th style="width: 140px;">Дата</th>
                            <th class="text-end pe-4" style="width: 150px;">Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quarterDocuments as $doc)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark rounded-pill px-2 py-1">
                                        {{ $doc->quarter }} кв. {{ $doc->year }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $doc->type_badge_class }} rounded-pill px-2 py-1 small">
                                        {{ $doc->type_label }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-dark d-block">{{ $doc->title }}</strong>
                                        <span class="text-muted small d-inline-flex align-items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-paperclip text-primary" viewBox="0 0 16 16">
                                                <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                                            </svg>
                                            {{ $doc->original_name }} &bull; {{ $doc->formatted_size }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($doc->notes)
                                        <span class="small text-muted">{{ $doc->notes }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $doc->created_at?->format('d.m.Y H:i') }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <a href="{{ route('platform.fop.document.download', $doc->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Завантажити файл" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                            </svg>
                                            <span class="ms-1 d-none d-md-inline">Скачати</span>
                                        </a>
                                        <form method="POST" action="{{ route('platform.fop.document.delete', $doc->id) }}" onsubmit="return confirm('Ви впевнені, що хочете видалити цей документ?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;" title="Видалити документ">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-5 text-center">
                <div class="mb-3 text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-folder-plus text-primary-subtle" viewBox="0 0 16 16">
                        <path d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 3a1 1 0 0 1 1-1h3.672z"/>
                        <path d="M13.5 9a.5.5 0 0 1 .5.5V11h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V12h-1.5a.5.5 0 0 1 0-1H13V9.5a.5.5 0 0 1 .5-.5z"/>
                    </svg>
                </div>
                <h6 class="fw-bold text-dark mb-1">
                    @if(!empty($docFilterQuarter))
                        Немає документів за {{ $docFilterQuarter }} квартал {{ $report['year'] }} р.
                    @else
                        Немає прикріплених документів за {{ $report['year'] }} рік
                    @endif
                </h6>
                <p class="text-muted small mb-3" style="max-width: 500px; margin: 0 auto;">
                    Зберігайте податкові декларації, квитанції №1 та №2 від ДПС, платіжні доручення про сплату податків та первинні документи впорядковано за кварталами.
                </p>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadDocModal" onclick="setModalQuarter({{ $docFilterQuarter ?: 1 }})">
                    + Завантажити перший документ
                </button>
            </div>
        @endif
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

    <!-- Upload Quarter Document Modal -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="{{ route('platform.fop.document.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header border-bottom py-3 px-4">
                        <h5 class="modal-title fw-bold text-dark" id="uploadDocModalLabel">
                            📎 Додати документ до кварталу
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="year" value="{{ $report['year'] }}">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Квартал <span class="text-danger">*</span></label>
                                <select name="quarter" id="modalQuarterSelect" class="form-select" required>
                                    <option value="1">I квартал (Січ - Бер)</option>
                                    <option value="2">II квартал (Кві - Чер)</option>
                                    <option value="3">III квартал (Лип - Вер)</option>
                                    <option value="4">IV квартал (Жов - Гру)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Рік</label>
                                <input type="text" class="form-control" value="{{ $report['year'] }} рік" readonly disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Тип документа <span class="text-danger">*</span></label>
                            <select name="document_type" class="form-select" required>
                                <option value="declaration">📄 Податкова декларація платника ЄП (PDF, XML)</option>
                                <option value="receipt_1">📨 Квитанція №1 від ДПС (Доставка)</option>
                                <option value="receipt_2">✅ Квитанція №2 від ДПС (Прийнято)</option>
                                <option value="tax_payment">💳 Квитанція про сплату Єдиного податку (5%)</option>
                                <option value="military_tax_payment">🛡️ Квитанція про сплату Військового збору (1%)</option>
                                <option value="esv_payment">💼 Квитанція про сплату ЄСВ</option>
                                <option value="bank_statement">🏦 Банківська виписка за квартал</option>
                                <option value="act">📑 Акт наданих послуг / Рахунок / Договір</option>
                                <option value="other">📎 Інший документ</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Назва документа (необов'язково)</label>
                            <input type="text" name="title" class="form-control" placeholder="Наприклад: Декларація ЄП 1 кв. {{ $report['year'] }}">
                            <div class="form-text small">Якщо залишити порожнім, назва згенерується автоматично з обраного типу.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Оберіть файл <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" required accept=".pdf,.xml,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
                            <div class="form-text small">Підтримуються формати: PDF, XML, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP (до 25 МБ).</div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label small fw-semibold">Примітка або коментар (необов'язково)</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Номер документа, реєстраційний номер у ДПС тощо..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3 px-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Скасувати</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            Завантажити документ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function setModalQuarter(quarter) {
        var select = document.getElementById('modalQuarterSelect');
        if (select) {
            select.value = quarter;
        }
    }
    </script>
</div>
@endif
