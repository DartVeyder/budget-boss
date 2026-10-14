@php
    $analysis = $analysis ?? [];
    if (empty($analysis)) {
        return;
    }

    $summary = $analysis['summary'] ?? [];
    $alloc503020 = $analysis['allocation_50_30_20'] ?? [];
    $alloc6Jars = $analysis['allocation_6_jars'] ?? [];
    $concentration = $analysis['concentration'] ?? [];
    $tips = $analysis['recommendations'] ?? [];
    $period = $analysis['period'] ?? [];

    $fmt = fn($v) => number_format((float)$v, 0, '.', ' ') . ' ₴';
    $fmtSigned = fn($v) => ($v > 0 ? '+' : ($v < 0 ? '−' : '')) . number_format(abs((float)$v), 0, '.', ' ') . ' ₴';

    $refIncome = (float)($summary['reference_income'] ?? 0);
    $overallStatus = $alloc503020['overall_status'] ?? 'optimal';
    $statusBadge = $alloc503020['status_badge'] ?? 'Збалансований';
    $statusColor = $alloc503020['status_color'] ?? '#10b981';
@endphp

<style>
    .bb-rec-container {
        font-family: inherit;
    }
    .bb-rec-main-card {
        background: #ffffff;
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .bb-rec-header {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
        color: #ffffff;
        padding: 1.5rem 1.75rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .bb-rec-nav-tabs {
        display: inline-flex;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(8px);
        padding: 4px;
        border-radius: 9999px;
        gap: 2px;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .bb-rec-tab-btn {
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.83rem;
        font-weight: 600;
        padding: 0.45rem 1.1rem;
        border-radius: 9999px;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }
    .bb-rec-tab-btn:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.1);
    }
    .bb-rec-tab-btn.active {
        background: #ffffff;
        color: #1e1b4b;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    .bb-bucket-card {
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .bb-bucket-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06);
    }
    .bb-rec-progress {
        position: relative;
        height: 10px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
    }
    .bb-rec-progress-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .bb-rec-marker {
        position: absolute;
        top: -2px;
        bottom: -2px;
        width: 2px;
        background: #0f172a;
        opacity: 0.45;
        z-index: 2;
    }
    .bb-cat-item-row {
        display: grid;
        grid-template-columns: 2.2rem 1.5fr 1fr 100px 110px;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        transition: background-color 0.15s ease;
    }
    .bb-cat-item-row:hover {
        background-color: #f8fafc;
    }
    @media (max-width: 768px) {
        .bb-cat-item-row {
            grid-template-columns: 1fr auto;
            gap: 0.5rem;
        }
        .bb-cat-item-hide-mobile {
            display: none !important;
        }
    }
    .bb-jar-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.85rem;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    .bb-jar-card:hover {
        border-color: #cbd5e1;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .bb-tip-card {
        border-radius: 0.95rem;
        border-left: 4px solid;
        background: #ffffff;
        padding: 1.15rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        border-top: 1px solid #f1f5f9;
        border-right: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }
</style>

<div class="bb-rec-container">
    <div class="bb-rec-main-card">
        {{-- Header Section --}}
        <div class="bb-rec-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-4">💡</span>
                        <h4 class="mb-0 fw-bold text-white">Аналіз доходів і витрат та рекомендації розподілу</h4>
                        <span class="badge rounded-pill px-3 py-1.5 ms-2"
                              style="background: {{ $statusColor }}25; color: #ffffff; border: 1px solid {{ $statusColor }}80; font-size: 0.78rem;">
                            {{ $statusBadge }}
                        </span>
                    </div>
                    <div class="text-white-50 small">
                        Розумний аналіз бюджету за {{ $period['is_current_month'] ? 'поточний місяць' : 'обраний період' }}
                        ({{ $period['start_formatted'] }} — {{ $period['end_formatted'] }}). Базовий дохід:
                        <strong class="text-white">{{ $fmt($refIncome) }}</strong>
                    </div>
                </div>

                {{-- Interactive Tabs Switcher --}}
                <div class="bb-rec-nav-tabs" role="tablist" id="bb-rec-tabs-nav">
                    <button type="button" class="bb-rec-tab-btn active" data-rec-target="tab-alloc">
                        <span>🎯</span> Розподіл 50/30/20
                    </button>
                    <button type="button" class="bb-rec-tab-btn" data-rec-target="tab-burn">
                        <span>🔥</span> На що йдуть витрати
                    </button>
                    <button type="button" class="bb-rec-tab-btn" data-rec-target="tab-jars">
                        <span>🏺</span> 6 глечиків
                    </button>
                    <button type="button" class="bb-rec-tab-btn" data-rec-target="tab-tips">
                        <span>🧠</span> Поради та план
                    </button>
                    <button type="button" class="bb-rec-tab-btn" data-rec-target="tab-calc">
                        <span>🧮</span> Калькулятор
                    </button>
                </div>
            </div>
        </div>

        <div class="p-4">
            {{-- ========================================================
                 PANEL 1: 50 / 30 / 20 ALLOCATION
                 ======================================================== --}}
            <div class="bb-rec-tab-panel" id="tab-alloc">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Класична формула фінансового успіху: 50 / 30 / 20</h5>
                        <div class="text-muted small">
                            Золотий стандарт фінансової грамотності: 50% на обов'язкове, 30% на бажання, 20% у накопичення.
                            Розраховано від місячного доходу: <strong>{{ $fmt($refIncome) }}</strong>.
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                            Фактичний рівень заощаджень:
                            <strong class="{{ ($summary['savings_rate'] ?? 0) >= 20 ? 'text-success' : 'text-danger' }}">
                                {{ $summary['savings_rate'] ?? 0 }}%
                            </strong>
                        </span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    {{-- 1. NEEDS (50%) --}}
                    @php $needs = $alloc503020['needs'] ?? []; @endphp
                    <div class="col-lg-4">
                        <div class="bb-bucket-card" style="border-top: 4px solid #3b82f6 !important;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-4">🏠</span>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem;">Обов'язкові потреби</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Ціль: 50% від доходу</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill {{ $needs['status'] === 'optimal' ? 'bg-success-subtle text-success' : ($needs['status'] === 'acceptable' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') }}">
                                    {{ $needs['status'] === 'optimal' ? 'У нормі' : ($needs['status'] === 'acceptable' ? 'Близько норми' : 'Перевитрата') }}
                                </span>
                            </div>

                            <div class="my-3">
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="text-muted small">Фактично витрачено:</span>
                                    <strong class="fs-5 text-dark">{{ $fmt($needs['actual_amount'] ?? 0) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-baseline text-muted small mb-2">
                                    <span>Рекомендований ліміт (50%):</span>
                                    <span>{{ $fmt($needs['target_amount'] ?? 0) }}</span>
                                </div>

                                <div class="bb-rec-progress mb-1">
                                    <div class="bb-rec-progress-fill"
                                         style="width: {{ min(100, $needs['actual_pct'] ?? 0) }}%; background: linear-gradient(90deg, #60a5fa, #3b82f6);"></div>
                                    <div class="bb-rec-marker" style="left: 50%;" title="Цільова норма 50%"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                    <span>0%</span>
                                    <span>факт: <strong>{{ $needs['actual_pct'] ?? 0 }}%</strong> (норма 50%)</span>
                                    <span>100%</span>
                                </div>
                            </div>

                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Різниця з нормою:</span>
                                    <strong style="color: {{ ($needs['diff'] ?? 0) <= 0 ? '#10b981' : '#ef4444' }};">
                                        {{ ($needs['diff'] ?? 0) <= 0 ? 'Запас ' . $fmt(abs($needs['diff'] ?? 0)) : 'Перевищення ' . $fmt($needs['diff'] ?? 0) }}
                                    </strong>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.72rem; line-height: 1.4;">
                                    {{ $needs['description'] ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. WANTS (30%) --}}
                    @php $wants = $alloc503020['wants'] ?? []; @endphp
                    <div class="col-lg-4">
                        <div class="bb-bucket-card" style="border-top: 4px solid #f59e0b !important;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-4">☕</span>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem;">Бажання та комфорт</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Ціль: до 30% від доходу</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill {{ $wants['status'] === 'optimal' ? 'bg-success-subtle text-success' : ($wants['status'] === 'acceptable' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') }}">
                                    {{ $wants['status'] === 'optimal' ? 'У нормі' : ($wants['status'] === 'acceptable' ? 'Близько норми' : 'Перевитрата') }}
                                </span>
                            </div>

                            <div class="my-3">
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="text-muted small">Фактично витрачено:</span>
                                    <strong class="fs-5 text-dark">{{ $fmt($wants['actual_amount'] ?? 0) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-baseline text-muted small mb-2">
                                    <span>Рекомендований ліміт (30%):</span>
                                    <span>{{ $fmt($wants['target_amount'] ?? 0) }}</span>
                                </div>

                                <div class="bb-rec-progress mb-1">
                                    <div class="bb-rec-progress-fill"
                                         style="width: {{ min(100, $wants['actual_pct'] ?? 0) }}%; background: linear-gradient(90deg, #fbbf24, #f59e0b);"></div>
                                    <div class="bb-rec-marker" style="left: 30%;" title="Цільова норма 30%"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                    <span>0%</span>
                                    <span>факт: <strong>{{ $wants['actual_pct'] ?? 0 }}%</strong> (норма 30%)</span>
                                    <span>100%</span>
                                </div>
                            </div>

                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Різниця з нормою:</span>
                                    <strong style="color: {{ ($wants['diff'] ?? 0) <= 0 ? '#10b981' : '#ef4444' }};">
                                        {{ ($wants['diff'] ?? 0) <= 0 ? 'Запас ' . $fmt(abs($wants['diff'] ?? 0)) : 'Перевищення ' . $fmt($wants['diff'] ?? 0) }}
                                    </strong>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.72rem; line-height: 1.4;">
                                    {{ $wants['description'] ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. SAVINGS (20%) --}}
                    @php $savings = $alloc503020['savings'] ?? []; @endphp
                    <div class="col-lg-4">
                        <div class="bb-bucket-card" style="border-top: 4px solid #10b981 !important;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-4">🛡️</span>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem;">Заощадження та резерв</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Ціль: від 20% від доходу</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill {{ $savings['status'] === 'great' ? 'bg-success-subtle text-success' : ($savings['status'] === 'acceptable' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') }}">
                                    {{ $savings['status'] === 'great' ? 'Перевиконано 🚀' : ($savings['status'] === 'acceptable' ? 'Задовільно' : 'Критично мало') }}
                                </span>
                            </div>

                            <div class="my-3">
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="text-muted small">Фактично збережено:</span>
                                    <strong class="fs-5 text-success">{{ $fmt($savings['actual_amount'] ?? 0) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-baseline text-muted small mb-2">
                                    <span>Рекомендована сума (20%):</span>
                                    <span>{{ $fmt($savings['target_amount'] ?? 0) }}</span>
                                </div>

                                <div class="bb-rec-progress mb-1">
                                    <div class="bb-rec-progress-fill"
                                         style="width: {{ min(100, $savings['actual_pct'] ?? 0) }}%; background: linear-gradient(90deg, #34d399, #10b981);"></div>
                                    <div class="bb-rec-marker" style="left: 20%;" title="Цільовий мінімум 20%"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                    <span>0%</span>
                                    <span>факт: <strong>{{ $savings['actual_pct'] ?? 0 }}%</strong> (норма 20%)</span>
                                    <span>100%</span>
                                </div>
                            </div>

                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Стан накопичень:</span>
                                    <strong style="color: {{ ($savings['diff'] ?? 0) >= 0 ? '#10b981' : '#ef4444' }};">
                                        {{ ($savings['diff'] ?? 0) >= 0 ? '+' . $fmt($savings['diff'] ?? 0) . ' до норми' : 'Не вистачає ' . $fmt(abs($savings['diff'] ?? 0)) }}
                                    </strong>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.72rem; line-height: 1.4;">
                                    {{ $savings['description'] ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Banner: Formula Breakdown --}}
                <div class="p-3 rounded-4 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <span class="fs-5">💡</span>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Як застосувати це у поточному місяці:</div>
                            <div class="text-muted small">
                                Отримавши виплату <strong class="text-dark">{{ $fmt($refIncome) }}</strong>, одразу відкладіть
                                <strong class="text-success">{{ $fmt($needs['target_amount'] ?? 0) }}</strong> на рахунок витрат,
                                <strong class="text-warning-emphasis">{{ $fmt($wants['target_amount'] ?? 0) }}</strong> на карту щоденних покупок,
                                та <strong class="text-primary">{{ $fmt($savings['target_amount'] ?? 0) }}</strong> у скарбничку/депозит.
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                            onclick="document.querySelector('[data-rec-target=\'tab-calc\']').click();">
                        Відкрити калькулятор →
                    </button>
                </div>
            </div>

            {{-- ========================================================
                 PANEL 2: EXPENSE CONCENTRATION ("На що більше йдуть витрати")
                 ======================================================== --}}
            <div class="bb-rec-tab-panel" id="tab-burn" style="display: none;">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Аналіз структури витрат: на що йдуть ваші кошти</h5>
                        <div class="text-muted small">
                            Ранжування категорій за сумою, часткою у бюджеті та виявлення фінансових витоків.
                        </div>
                    </div>
                    <div>
                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 small">
                            Топ-3 статті забирають <strong>{{ $concentration['top_3_share'] ?? 0 }}%</strong> усіх витрат
                        </span>
                    </div>
                </div>

                {{-- Summary Concentration Bar --}}
                <div class="p-3 rounded-3 mb-4 border" style="background: #faf5ff; border-color: #f3e8ff !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold text-dark small">
                            Концентрація витрат: Топ-3 статті споживають {{ $fmt($concentration['top_3_sum'] ?? 0) }} з {{ $fmt($summary['period_expenses'] ?? 0) }}
                        </span>
                        <span class="badge rounded-pill text-white" style="background:#7c3aed;">
                            Топ-5: {{ $concentration['top_5_share'] ?? 0 }}%
                        </span>
                    </div>
                    <div class="bb-rec-progress" style="height: 12px;">
                        <div class="bb-rec-progress-fill" style="width: {{ $concentration['top_3_share'] ?? 0 }}%; background: linear-gradient(90deg, #c084fc, #7c3aed);"
                             title="Топ-3: {{ $concentration['top_3_share'] ?? 0 }}%"></div>
                    </div>
                </div>

                {{-- Categories Table/List --}}
                <div class="border rounded-3 mb-4 overflow-hidden">
                    <div class="bb-cat-item-row bg-light border-bottom text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">
                        <span>#</span>
                        <span>Категорія</span>
                        <span class="text-end">Сума</span>
                        <span class="text-end bb-cat-item-hide-mobile">% Витрат</span>
                        <span class="text-end">Статус</span>
                    </div>

                    @foreach(($concentration['top_categories'] ?? []) as $idx => $cat)
                        @php
                            $burnBadge = match($cat['burn_level']) {
                                'critical' => ['label' => 'Домінантна', 'class' => 'bg-danger-subtle text-danger border border-danger-subtle'],
                                'high'     => ['label' => 'Висока',      'class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
                                'moderate' => ['label' => 'Помірна',     'class' => 'bg-primary-subtle text-primary border border-primary-subtle'],
                                default    => ['label' => 'Базова',      'class' => 'bg-secondary-subtle text-secondary'],
                            };
                            $bMeta = $cat['bucket_meta'];
                        @endphp
                        <div class="bb-cat-item-row border-bottom">
                            <span class="fw-bold text-muted small">{{ $idx + 1 }}</span>
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <span>{{ $bMeta['icon'] }}</span>
                                <div>
                                    <div class="fw-semibold text-dark text-truncate">{{ $cat['name'] }}</div>
                                    <span class="badge rounded-pill px-2 py-0.5" style="background: {{ $bMeta['bg'] }}; color: {{ $bMeta['color'] }}; font-size: 0.68rem;">
                                        {{ $bMeta['short_name'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-dark">{{ $fmt($cat['amount']) }}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $cat['tx_count'] }} опер. (сер. {{ $fmt($cat['avg_tx']) }})</div>
                            </div>
                            <div class="text-end bb-cat-item-hide-mobile">
                                <div class="fw-semibold text-dark">{{ $cat['share_of_expenses'] }}%</div>
                                @if($cat['share_of_income'])
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $cat['share_of_income'] }}% доходу</div>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge rounded-pill {{ $burnBadge['class'] }} px-2 py-1" style="font-size: 0.72rem;">
                                    {{ $burnBadge['label'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Leaks and Optimization Opportunities --}}
                <h6 class="fw-bold text-dark mb-3">🔍 Точки оптимізації та фінансові витоки</h6>
                <div class="row g-3">
                    @php $dining = $concentration['leaks']['dining'] ?? []; @endphp
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border bg-white h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-4">☕</span>
                                <div class="fw-bold text-dark">Кафе, ресторани та їжа</div>
                            </div>
                            <div class="fw-bolder text-dark fs-5 mb-1">{{ $fmt($dining['amount'] ?? 0) }}</div>
                            <div class="text-muted small mb-2">за {{ $dining['tx_count'] ?? 0 }} відвідувань / замовлень</div>
                            <div class="p-2 rounded-2 bg-success-subtle text-success small">
                                💡 Економія 20%: <strong>+{{ $fmt($dining['potential_monthly_savings'] ?? 0) }}/міс</strong>
                                ({{ $fmt($dining['potential_annual_savings'] ?? 0) }}/рік)
                            </div>
                        </div>
                    </div>

                    @php $subs = $concentration['leaks']['subscriptions'] ?? []; @endphp
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border bg-white h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-4">📱</span>
                                <div class="fw-bold text-dark">Регулярні підписки</div>
                            </div>
                            <div class="fw-bolder text-dark fs-5 mb-1">{{ $fmt($subs['amount'] ?? 0) }}</div>
                            <div class="text-muted small mb-2">щомісячна абонплата за сервіси</div>
                            <div class="p-2 rounded-2 bg-light text-muted small">
                                🗓️ Річна вартість підписок: <strong>{{ $fmt($subs['annual_amount'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>

                    @php $taxi = $concentration['leaks']['taxi'] ?? []; @endphp
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border bg-white h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-4">🚕</span>
                                <div class="fw-bold text-dark">Таксі та поїздки</div>
                            </div>
                            <div class="fw-bolder text-dark fs-5 mb-1">{{ $fmt($taxi['amount'] ?? 0) }}</div>
                            <div class="text-muted small mb-2">{{ $taxi['share_of_expenses'] ?? 0 }}% від загальних витрат</div>
                            <div class="p-2 rounded-2 bg-light text-muted small">
                                ⚖️ Оцініть альтернативи (громадський транспорт або піші прогулянки)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================================
                 PANEL 3: 6 JARS METHODOLOGY (Харв Екер)
                 ======================================================== --}}
            <div class="bb-rec-tab-panel" id="tab-jars" style="display: none;">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Система 6 глечиків (конвертів) Т. Харва Екера</h5>
                        <div class="text-muted small">
                            Метод цільового фондування: кожна гривня має своє призначення до того, як її витрачено.
                        </div>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                        База: <strong>{{ $fmt($alloc6Jars['income_base'] ?? $refIncome) }}</strong>
                    </span>
                </div>

                <div class="row g-3">
                    @foreach(($alloc6Jars['jars'] ?? []) as $jar)
                        <div class="col-md-4 col-sm-6">
                            <div class="bb-jar-card h-100 d-flex flex-column" style="border-top: 3px solid {{ $jar['color'] }} !important;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4">{{ $jar['icon'] }}</span>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $jar['name'] }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Частка: <strong>{{ $jar['percent'] }}%</strong></div>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill" style="background: {{ $jar['bg'] }}; color: {{ $jar['color'] }}; font-size: 0.72rem;">
                                        {{ $jar['code'] }}
                                    </span>
                                </div>

                                <div class="my-2">
                                    <div class="text-muted small">Рекомендовано виділити:</div>
                                    <div class="fw-bolder fs-5" style="color: {{ $jar['color'] }};">{{ $fmt($jar['target_amount']) }}</div>
                                </div>

                                <div class="mt-auto pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center text-muted small">
                                        <span>Фактично витрачено:</span>
                                        <strong class="text-dark">{{ $fmt($jar['actual_amount']) }}</strong>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.35;">
                                        {{ $jar['desc'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ========================================================
                 PANEL 4: SMART RECOMMENDATIONS & ACTION PLAN
                 ======================================================== --}}
            <div class="bb-rec-tab-panel" id="tab-tips" style="display: none;">
                <div class="mb-3">
                    <h5 class="fw-bold text-dark mb-1">Персональні рекомендації та фінансовий план дій</h5>
                    <div class="text-muted small">
                        Алгоритмічні підказки, розраховані на основі ваших фактичних транзакцій та звичок.
                    </div>
                </div>

                <div class="d-flex flex-column gap-3">
                    @foreach($tips as $tip)
                        @php
                            $borderColor = match($tip['type']) {
                                'danger'  => '#ef4444',
                                'warning' => '#f59e0b',
                                'success' => '#10b981',
                                'primary' => '#6366f1',
                                default   => '#3b82f6',
                            };
                            $badgeClass = match($tip['type']) {
                                'danger'  => 'bg-danger-subtle text-danger',
                                'warning' => 'bg-warning-subtle text-warning-emphasis',
                                'success' => 'bg-success-subtle text-success',
                                'primary' => 'bg-primary-subtle text-primary',
                                default   => 'bg-info-subtle text-info',
                            };
                        @endphp
                        <div class="bb-tip-card" style="border-left-color: {{ $borderColor }} !important;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-5">{{ $tip['icon'] }}</span>
                                    <strong class="text-dark" style="font-size: 1rem;">{{ $tip['title'] }}</strong>
                                </div>
                                @if(!empty($tip['badge']))
                                    <span class="badge rounded-pill {{ $badgeClass }} px-2.5 py-1 small">
                                        {{ $tip['badge'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-secondary small" style="white-space: pre-line; line-height: 1.6;">
                                {{ $tip['text'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ========================================================
                 PANEL 5: INTERACTIVE INCOME ALLOCATOR (CALCULATOR)
                 ======================================================== --}}
            <div class="bb-rec-tab-panel" id="tab-calc" style="display: none;">
                <div class="mb-3">
                    <h5 class="fw-bold text-dark mb-1">Інтерактивний калькулятор розподілу доходу</h5>
                    <div class="text-muted small">
                        Введіть будь-яку суму запланованого доходу або зарплати — калькулятор миттєво порахує точні суми для кожного конверта.
                    </div>
                </div>

                {{-- Input Controls --}}
                <div class="p-4 rounded-4 bg-light border mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="bb-calc-income-input" class="form-label fw-bold small text-uppercase text-muted">
                                Сума місячного доходу (₴)
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0 fw-bold text-muted">₴</span>
                                <input type="number" id="bb-calc-income-input" class="form-control bg-white border-start-0 fw-bolder fs-4 text-dark"
                                       value="{{ round($refIncome > 0 ? $refIncome : 50000) }}" step="500" min="1000">
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <span class="text-muted small me-2">Швидкі суми:</span>
                                @if($refIncome > 0)
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2"
                                            onclick="bbSetCalcIncome({{ round($refIncome) }});">
                                        Мій факт: {{ $fmt($refIncome) }}
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="bbSetCalcIncome(30000);">30 000 ₴</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="bbSetCalcIncome(50000);">50 000 ₴</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="bbSetCalcIncome(80000);">80 000 ₴</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="bbSetCalcIncome(120000);">120 000 ₴</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Results: 50 / 30 / 20 --}}
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-3">🎯 Розподіл за моделлю 50 / 30 / 20:</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 border bg-white h-100" style="border-left: 4px solid #3b82f6 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">🏠 50% Обов'язкове</span>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill">Needs</span>
                                </div>
                                <div class="fs-4 fw-bolder text-primary mb-1" id="bb-calc-needs">0 ₴</div>
                                <div class="text-muted small">≈ <span id="bb-calc-needs-week">0</span> ₴ на тиждень</div>
                                <div class="text-muted mt-2" style="font-size: 0.72rem;">Житло, комуналка, базові продукти, транспорт</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded-3 border bg-white h-100" style="border-left: 4px solid #f59e0b !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">☕ 30% Бажання</span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Wants</span>
                                </div>
                                <div class="fs-4 fw-bolder text-warning-emphasis mb-1" id="bb-calc-wants">0 ₴</div>
                                <div class="text-muted small">≈ <span id="bb-calc-wants-week">0</span> ₴ на тиждень</div>
                                <div class="text-muted mt-2" style="font-size: 0.72rem;">Кафе, розваги, одяг, підписки, дозвілля</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded-3 border bg-white h-100" style="border-left: 4px solid #10b981 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">🛡️ 20% Заощадження</span>
                                    <span class="badge bg-success-subtle text-success rounded-pill">Savings</span>
                                </div>
                                <div class="fs-4 fw-bolder text-success mb-1" id="bb-calc-savings">0 ₴</div>
                                <div class="text-muted small">≈ <span id="bb-calc-savings-year">0</span> ₴ за рік накопичень</div>
                                <div class="text-muted mt-2" style="font-size: 0.72rem;">Подушка безпеки, депозити, інвестиції</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Results: 6 Jars --}}
                <div>
                    <h6 class="fw-bold text-dark mb-3">🏺 Розподіл за системою 6 глечиків (конвертів):</h6>
                    <div class="row g-2">
                        <div class="col-md-4 col-sm-6">
                            <div class="p-2.5 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>🏠 NEC (55%)</span>
                                    <span class="fw-bold text-dark" id="bb-calc-jar-nec">0 ₴</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-2.5 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>📈 FFA (10%) Інвестиції</span>
                                    <span class="fw-bold text-dark" id="bb-calc-jar-ffa">0 ₴</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-2.5 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>🛠️ LTSS (10%) Резерв</span>
                                    <span class="fw-bold text-dark" id="bb-calc-jar-ltss">0 ₴</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-2.5 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>📚 EDU (10%) Освіта</span>
                                    <span class="fw-bold text-dark" id="bb-calc-jar-edu">0 ₴</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-2.5 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>🎉 PLAY (10%) Розваги</span>
                                    <span class="fw-bold text-dark" id="bb-calc-jar-play">0 ₴</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-2.5 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>🎁 GIVE (5%) Благодійність</span>
                                    <span class="fw-bold text-dark" id="bb-calc-jar-give">0 ₴</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        function initRecTabs() {
            var nav = document.getElementById('bb-rec-tabs-nav');
            if (!nav || nav.dataset.bbRecInit) return;
            nav.dataset.bbRecInit = '1';

            var buttons = nav.querySelectorAll('.bb-rec-tab-btn');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = btn.getAttribute('data-rec-target');
                    buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });

                    var panels = document.querySelectorAll('.bb-rec-tab-panel');
                    panels.forEach(function (p) {
                        p.style.display = p.id === targetId ? '' : 'none';
                    });
                });
            });

            var calcInput = document.getElementById('bb-calc-income-input');
            if (calcInput) {
                calcInput.addEventListener('input', runCalc);
                runCalc();
            }
        }

        function fmtMoney(num) {
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " ₴";
        }

        function runCalc() {
            var calcInput = document.getElementById('bb-calc-income-input');
            if (!calcInput) return;
            var val = parseFloat(calcInput.value) || 0;

            // 50 / 30 / 20
            var needs = val * 0.50;
            var wants = val * 0.30;
            var savings = val * 0.20;

            var elNeeds = document.getElementById('bb-calc-needs');
            var elWants = document.getElementById('bb-calc-wants');
            var elSavings = document.getElementById('bb-calc-savings');

            if (elNeeds) elNeeds.textContent = fmtMoney(needs);
            if (elWants) elWants.textContent = fmtMoney(wants);
            if (elSavings) elSavings.textContent = fmtMoney(savings);

            var elNeedsWk = document.getElementById('bb-calc-needs-week');
            var elWantsWk = document.getElementById('bb-calc-wants-week');
            var elSavYr = document.getElementById('bb-calc-savings-year');

            if (elNeedsWk) elNeedsWk.textContent = Math.round(needs / 4).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
            if (elWantsWk) elWantsWk.textContent = Math.round(wants / 4).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
            if (elSavYr) elSavYr.textContent = Math.round(savings * 12).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");

            // 6 Jars
            var jNec = document.getElementById('bb-calc-jar-nec');
            var jFfa = document.getElementById('bb-calc-jar-ffa');
            var jLtss = document.getElementById('bb-calc-jar-ltss');
            var jEdu = document.getElementById('bb-calc-jar-edu');
            var jPlay = document.getElementById('bb-calc-jar-play');
            var jGive = document.getElementById('bb-calc-jar-give');

            if (jNec) jNec.textContent = fmtMoney(val * 0.55);
            if (jFfa) jFfa.textContent = fmtMoney(val * 0.10);
            if (jLtss) jLtss.textContent = fmtMoney(val * 0.10);
            if (jEdu) jEdu.textContent = fmtMoney(val * 0.10);
            if (jPlay) jPlay.textContent = fmtMoney(val * 0.10);
            if (jGive) jGive.textContent = fmtMoney(val * 0.05);
        }

        window.bbSetCalcIncome = function (amount) {
            var calcInput = document.getElementById('bb-calc-income-input');
            if (calcInput) {
                calcInput.value = amount;
                runCalc();
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRecTabs);
        } else {
            initRecTabs();
        }
        document.addEventListener('turbo:load', initRecTabs);
    })();
</script>
