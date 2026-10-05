<div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        ФОП &bull; {{ $fop->fopGroup?->name ?? '3 група (5%)' }}
                    </span>
                    @if($fop->is_active)
                        <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 small">Активний</span>
                    @endif
                </div>
                <h3 class="fw-bold mb-1 text-white d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-briefcase text-indigo-400" viewBox="0 0 16 16" style="color: #818cf8;">
                        <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v8A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5zm1.886 6.914L15 7.151V12.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V7.15l6.614 1.764a1.5 1.5 0 0 0 .772 0zM1.5 4h13a.5.5 0 0 1 .5.5v1.616L8.129 7.948a.5.5 0 0 1-.258 0L1 6.116V4.5a.5.5 0 0 1 .5-.5z"/>
                    </svg>
                    {{ $fop->name }}
                </h3>
                <div class="text-white-50 small d-flex flex-wrap gap-3 mt-1">
                    <span>ІПН: <strong class="text-white">{{ $fop->ipn ?? '—' }}</strong></span>
                    <span>Рахунок: <strong class="text-white">{{ $fop->bill?->name ?? 'Монобанк' }}</strong></span>
                    @if($fop->iban)
                        <span class="d-none d-md-inline">IBAN: <strong class="text-white">{{ $fop->iban }}</strong></span>
                    @endif
                </div>
            </div>

            <!-- Filters for Year and Quarter -->
            <div class="d-flex flex-column align-items-end gap-2">
                <!-- Years -->
                <div class="btn-group shadow-sm" role="group" aria-label="Year filter">
                    @foreach($availableYears as $y)
                        <a href="{{ route('platform.fop.analytic', ['year' => $y, 'quarter' => $selectedQuarter]) }}" 
                           class="btn btn-sm {{ $selectedYear == $y ? 'btn-primary fw-bold text-white' : 'btn-outline-light text-white-50' }} px-3">
                            {{ $y }}
                        </a>
                    @endforeach
                </div>

                <!-- Quarters -->
                <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Quarter filter">
                    <a href="{{ route('platform.fop.analytic', ['year' => $selectedYear]) }}" 
                       class="btn {{ empty($selectedQuarter) ? 'btn-light text-dark fw-bold' : 'btn-outline-light text-white-50' }} px-2.5">
                        Весь рік
                    </a>
                    @for($q = 1; $q <= 4; $q++)
                        <a href="{{ route('platform.fop.analytic', ['year' => $selectedYear, 'quarter' => $q]) }}" 
                           class="btn {{ $selectedQuarter == $q ? 'btn-light text-dark fw-bold' : 'btn-outline-light text-white-50' }} px-2.5">
                            Q{{ $q }}
                        </a>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>
