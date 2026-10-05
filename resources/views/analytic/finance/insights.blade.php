@php
    $ins = $insights ?? [];
    $fc = $ins['forecast'] ?? null;
    $fund = $ins['fund'] ?? null;
    $changes = $ins['category_changes'] ?? [];
    $fmt = fn($v) => number_format((float)$v, 0, '.', ' ') . ' ₴';
    $fmtSigned = fn($v) => ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . number_format(abs((float)$v), 0, '.', ' ') . ' ₴';

    $fundStatus = [
        'critical' => ['label' => 'Критично мало',     'color' => '#ef4444', 'bg' => '#fef2f2', 'emoji' => '🚨'],
        'low'      => ['label' => 'Замало',            'color' => '#f59e0b', 'bg' => '#fffbeb', 'emoji' => '⚠️'],
        'ok'       => ['label' => 'Непогано',          'color' => '#0284c7', 'bg' => '#f0f9ff', 'emoji' => '👍'],
        'great'    => ['label' => 'Надійна подушка',   'color' => '#10b981', 'bg' => '#f0fdf4', 'emoji' => '🛡️'],
        'unknown'  => ['label' => 'Недостатньо даних', 'color' => '#64748b', 'bg' => '#f8fafc', 'emoji' => '❔'],
    ];
    $fs = $fundStatus[$fund['status'] ?? 'unknown'];
@endphp

<style>
    .bb-ins-card { border: 0; border-radius: 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04); background: #fff; }
    .bb-ins-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
    .bb-ins-box { background: #f8fafc; border: 1px solid #eef2f7; border-radius: .75rem; padding: .7rem .85rem; height: 100%; }
    .bb-ins-box-val { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: .15rem; }
    .bb-ins-box-sub { font-size: .75rem; color: #64748b; }
    .bb-ins-track { position: relative; height: 12px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
    .bb-ins-fill { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 999px; transition: width .8s cubic-bezier(.16,1,.3,1); }
    .bb-ins-marker { position: absolute; top: -3px; bottom: -3px; width: 2px; background: #0f172a; opacity: .35; }
    .bb-ins-tabs { display: inline-flex; background: #f1f5f9; border-radius: 999px; padding: 3px; }
    .bb-ins-tab { border: 0; background: transparent; font-size: .8rem; font-weight: 600; color: #64748b; padding: .3rem .9rem; border-radius: 999px; transition: all .15s; }
    .bb-ins-tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
    .bb-chg-row { display: grid; grid-template-columns: minmax(140px, 1.3fr) 2fr minmax(150px, auto); gap: 1rem; align-items: center; }
    .bb-chg-bars { display: flex; align-items: center; height: 10px; }
    .bb-chg-half { flex: 1; display: flex; height: 100%; }
    .bb-chg-half.left { justify-content: flex-end; }
    .bb-chg-axis { width: 2px; height: 18px; background: #cbd5e1; flex-shrink: 0; }
    .bb-chg-bar { height: 100%; border-radius: 999px; }
    @media (max-width: 768px) { .bb-chg-row { grid-template-columns: 1fr; gap: .35rem; } }
</style>

<div class="row g-3 mb-4">
    {{-- ============ 1. Month-end forecast ============ --}}
    @if($fc)
        @php
            $overAvg = $fc['vs_avg_percent'];
            $netPositive = $fc['projected_net'] >= 0;
            $paceOk = $fc['current_daily'] <= $fc['safe_daily'] || $fc['days_left'] === 0;
        @endphp
        <div class="col-xl-7">
            <div class="bb-ins-card h-100 p-4 d-flex flex-column" style="border-top: 4px solid #8b5cf6 !important;">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="bb-ins-label">🔮 Прогноз на кінець місяця</div>
                        <div class="fw-bold text-dark mt-1" style="font-size: 1.05rem;">{{ $fc['month_label'] }}</div>
                    </div>
                    <span class="badge rounded-pill border" style="background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe !important;">
                        День {{ $fc['days_elapsed'] }} з {{ $fc['days_total'] }} · залишилось {{ $fc['days_left'] }}
                    </span>
                </div>

                <div class="d-flex flex-wrap align-items-end gap-4 mb-2">
                    <div>
                        <div class="text-muted small">Витрачено</div>
                        <div class="fw-bolder text-dark" style="font-size: 1.6rem;">{{ $fmt($fc['spent']) }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Прогноз витрат</div>
                        <div class="fw-bolder" style="font-size: 1.6rem; color:#7c3aed;">≈ {{ $fmt($fc['projected_expenses']) }}</div>
                    </div>
                    @if($overAvg !== null)
                        <span class="badge rounded-pill mb-2 {{ $overAvg > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}"
                              title="Середні витрати за {{ $fc['history_months'] }} міс.: {{ $fmt($fc['avg_monthly_expenses']) }}">
                            {{ $overAvg > 0 ? '+' : '' }}{{ $overAvg }}% до середнього ({{ $fmt($fc['avg_monthly_expenses']) }})
                        </span>
                    @endif
                </div>

                {{-- Spent vs projected + month progress marker --}}
                <div class="bb-ins-track mb-1" title="Витрачено {{ $fc['spent_share'] }}% від прогнозу · минуло {{ $fc['month_progress'] }}% місяця">
                    <div class="bb-ins-fill" style="width: 100%; background: #ede9fe;"></div>
                    <div class="bb-ins-fill" style="width: {{ $fc['spent_share'] }}%; background: linear-gradient(90deg,#a78bfa,#7c3aed);"></div>
                    <div class="bb-ins-marker" style="left: {{ $fc['month_progress'] }}%;"></div>
                </div>
                <div class="d-flex justify-content-between text-muted mb-3" style="font-size:.72rem;">
                    <span>0</span>
                    <span>▲ сьогодні ({{ $fc['month_progress'] }}% місяця)</span>
                    <span>{{ $fmt($fc['projected_expenses']) }}</span>
                </div>

                <div class="row g-2 mt-auto">
                    <div class="col-sm-4">
                        <div class="bb-ins-box">
                            <div class="bb-ins-label" style="font-size:.66rem;">Очікуваний дохід</div>
                            <div class="bb-ins-box-val text-success">{{ $fmt($fc['projected_income']) }}</div>
                            <div class="bb-ins-box-sub">факт: {{ $fmt($fc['earned']) }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="bb-ins-box" style="{{ $netPositive ? '' : 'background:#fef2f2;border-color:#fecaca;' }}">
                            <div class="bb-ins-label" style="font-size:.66rem;">Залишок на кінець</div>
                            <div class="bb-ins-box-val" style="color: {{ $netPositive ? '#10b981' : '#ef4444' }};">{{ $fmtSigned($fc['projected_net']) }}</div>
                            <div class="bb-ins-box-sub">{{ $netPositive ? 'місяць у плюсі' : 'ризик мінуса' }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="bb-ins-box">
                            <div class="bb-ins-label" style="font-size:.66rem;">Можна витрачати/день</div>
                            <div class="bb-ins-box-val">{{ $fmt($fc['safe_daily']) }}</div>
                            <div class="bb-ins-box-sub">
                                зараз: <strong style="color: {{ $paceOk ? '#10b981' : '#ef4444' }};">{{ $fmt($fc['current_daily']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-muted mt-3" style="font-size:.7rem;">
                    Темп = поточний темп місяця, зважений із середнім за {{ $fc['history_months'] }} міс. Дохід не екстраполюється: береться більше з факту та середнього. Прогноз завжди для поточного місяця, незалежно від обраного періоду.
                </div>
            </div>
        </div>
    @endif

    {{-- ============ 3. Emergency fund ============ --}}
    @if($fund)
        @php
            $markerPct = $fund['target_months'] > 0 ? (3 / $fund['target_months']) * 100 : 50;
        @endphp
        <div class="col-xl-5">
            <div class="bb-ins-card h-100 p-4 d-flex flex-column" style="border-top: 4px solid {{ $fs['color'] }} !important;">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="bb-ins-label">🛟 Фінансова подушка</div>
                    <span class="badge rounded-pill" style="background: {{ $fs['bg'] }}; color: {{ $fs['color'] }}; border: 1px solid {{ $fs['color'] }}33;">
                        {{ $fs['emoji'] }} {{ $fs['label'] }}
                    </span>
                </div>

                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fw-bolder" style="font-size: 2.4rem; line-height: 1; color: {{ $fs['color'] }};">
                        {{ $fund['months'] !== null ? number_format($fund['months'], 1, ',', ' ') : '—' }}
                    </span>
                    <span class="text-muted fw-semibold">міс.</span>
                </div>
                <div class="text-muted small mb-3">стільки можна прожити без доходу при поточних витратах</div>

                <div class="bb-ins-track mb-1">
                    <div class="bb-ins-fill" style="width: {{ $fund['progress'] }}%; background: {{ $fs['color'] }};"></div>
                    <div class="bb-ins-marker" style="left: {{ $markerPct }}%;" title="Мінімум: 3 міс."></div>
                </div>
                <div class="d-flex justify-content-between text-muted mb-3" style="font-size:.72rem;">
                    <span>0</span>
                    <span style="margin-left: {{ $markerPct - 50 }}%;">3 міс.</span>
                    <span>{{ $fund['target_months'] }} міс. (ціль)</span>
                </div>

                <div class="mt-auto d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Капітал{{ !empty($selected_bill) ? ' (рахунок)' : '' }}</span>
                        <strong class="text-dark">{{ $fmt($fund['capital']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Сер. витрати / міс. <span style="font-size:.7rem;">(за {{ $fund['history_months'] }} міс.)</span></span>
                        <strong class="text-dark">{{ $fmt($fund['avg_monthly_expenses']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Ціль ({{ $fund['target_months'] }} міс.)</span>
                        <strong class="text-dark">{{ $fmt($fund['target_amount']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ $fund['shortfall'] > 0 ? 'Не вистачає до цілі' : 'Ціль досягнута' }}</span>
                        <strong style="color: {{ $fund['shortfall'] > 0 ? '#f59e0b' : '#10b981' }};">
                            {{ $fund['shortfall'] > 0 ? $fmt($fund['shortfall']) : '✓' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ============ 2. Category changes ============ --}}
@if(!empty($changes))
    <div class="bb-ins-card p-4 mb-4" id="bb-category-changes">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <div class="bb-ins-label">📊 Зміни по категоріях</div>
                @if(!empty($kpi['period']))
                    <div class="text-muted mt-1" style="font-size:.78rem;">
                        {{ $kpi['period']['start'] }} — {{ $kpi['period']['end'] }}
                        <span class="mx-1">vs</span>
                        {{ $kpi['period']['prev_start'] }} — {{ $kpi['period']['prev_end'] }}
                    </div>
                @endif
            </div>
            <div class="bb-ins-tabs" role="tablist">
                <button type="button" class="bb-ins-tab active" data-chg-tab="expenses">Витрати</button>
                <button type="button" class="bb-ins-tab" data-chg-tab="income">Доходи</button>
            </div>
        </div>

        @foreach(['expenses', 'income'] as $chgType)
            @php $set = $changes[$chgType] ?? ['rows' => [], 'total_diff' => 0, 'total_cur' => 0, 'total_prev' => 0]; @endphp
            <div class="bb-chg-panel" data-chg-panel="{{ $chgType }}" style="{{ $chgType === 'expenses' ? '' : 'display:none;' }}">
                @if(empty($set['rows']))
                    <div class="text-center text-muted py-4">{{ __('No data') }}</div>
                @else
                    <div class="d-flex justify-content-between text-muted mb-2 px-1" style="font-size:.72rem;">
                        <span>{{ $chgType === 'expenses' ? '← зменшились' : '← впали' }}</span>
                        <span>Разом: {{ $fmt($set['total_prev']) }} → <strong class="text-dark">{{ $fmt($set['total_cur']) }}</strong>
                            ({{ $fmtSigned($set['total_diff']) }})</span>
                        <span>{{ $chgType === 'expenses' ? 'зросли →' : 'зросли →' }}</span>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($set['rows'] as $row)
                            @php
                                $color = $row['is_favorable'] ? '#10b981' : '#ef4444';
                                $clickable = !empty($row['id']);
                            @endphp
                            <div class="bb-chg-row {{ $clickable ? 'bb-clickable-category' : '' }}"
                                 @if($clickable)
                                     data-category-id="{{ $row['id'] }}"
                                     data-category-name="{{ $row['name'] }}"
                                     data-category-type="{{ $chgType }}"
                                     data-category-amount="{{ $fmt($row['current']) }}"
                                     data-category-color="{{ $color }}"
                                     title="Натисніть для перегляду транзакцій «{{ $row['name'] }}»"
                                 @endif
                                 style="{{ $clickable ? '' : 'padding: .5rem .75rem; margin: -.25rem -.75rem;' }}">
                                <div style="min-width:0;">
                                    <div class="fw-semibold text-dark text-truncate">{{ $row['name'] }}</div>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        {{ $fmt($row['prev']) }} → <strong class="text-dark">{{ $fmt($row['current']) }}</strong>
                                    </div>
                                </div>
                                <div class="bb-chg-bars">
                                    <div class="bb-chg-half left">
                                        @if($row['diff'] < 0)
                                            <div class="bb-chg-bar" style="width: {{ $row['bar'] }}%; background: {{ $color }};"></div>
                                        @endif
                                    </div>
                                    <div class="bb-chg-axis"></div>
                                    <div class="bb-chg-half">
                                        @if($row['diff'] > 0)
                                            <div class="bb-chg-bar" style="width: {{ $row['bar'] }}%; background: {{ $color }};"></div>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold" style="color: {{ $color }};">{{ $fmtSigned($row['diff']) }}</div>
                                    <div style="font-size:.75rem;">
                                        @if($row['is_new'])
                                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">нова</span>
                                        @elseif($row['is_gone'])
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">зникла</span>
                                        @elseif($row['percent'] !== null)
                                            <span style="color: {{ $color }};">{{ $row['percent'] > 0 ? '+' : '' }}{{ $row['percent'] }}%</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <script>
        (function () {
            function initChgTabs() {
                var root = document.getElementById('bb-category-changes');
                if (!root || root.dataset.bbInit) return;
                root.dataset.bbInit = '1';
                root.querySelectorAll('[data-chg-tab]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var t = btn.getAttribute('data-chg-tab');
                        root.querySelectorAll('[data-chg-tab]').forEach(function (b) { b.classList.toggle('active', b === btn); });
                        root.querySelectorAll('[data-chg-panel]').forEach(function (p) {
                            p.style.display = p.getAttribute('data-chg-panel') === t ? '' : 'none';
                        });
                    });
                });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initChgTabs); else initChgTabs();
            document.addEventListener('turbo:load', initChgTabs);
        })();
    </script>
@endif
