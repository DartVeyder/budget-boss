<div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-semibold text-muted small me-1">Рік:</span>
            <div class="btn-group btn-group-sm">
                @php
                    $currentY = (int)request()->get('year', Carbon\Carbon::now()->year);
                    $currentQ = request()->get('quarter', '');
                @endphp
                @foreach([Carbon\Carbon::now()->year - 2, Carbon\Carbon::now()->year - 1, Carbon\Carbon::now()->year] as $y)
                    <a href="{{ request()->fullUrlWithQuery(['year' => $y]) }}" 
                       class="btn {{ ($currentY == $y) ? 'btn-primary text-white' : 'btn-outline-secondary' }}">
                        {{ $y }}
                    </a>
                @endforeach
            </div>

            <span class="fw-semibold text-muted small ms-3 me-1">Період:</span>
            <div class="btn-group btn-group-sm">
                <a href="{{ request()->fullUrlWithQuery(['quarter' => null]) }}" 
                   class="btn {{ ($currentQ === '' || $currentQ === null) ? 'btn-dark text-white' : 'btn-outline-secondary' }}">
                    Весь рік
                </a>
                @foreach([1 => '1 кв.', 2 => '2 кв.', 3 => '3 кв.', 4 => '4 кв.'] as $qNum => $qTitle)
                    <a href="{{ request()->fullUrlWithQuery(['quarter' => $qNum]) }}" 
                       class="btn {{ ((string)$currentQ === (string)$qNum) ? 'btn-dark text-white' : 'btn-outline-secondary' }}">
                        {{ $qTitle }}
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <span class="text-muted small">
                Платник: <strong>{{ $fop->name }}</strong> (ІПН: {{ $fop->ipn }})
            </span>
        </div>
    </div>
</div>
