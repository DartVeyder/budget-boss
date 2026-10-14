@php
    $budget = $monthlyBudget ?? null;
    if (!$budget) {
        return;
    }

    $fmt = fn($v) => number_format((float)$v, 0, '.', ' ') . ' ₴';
    $curMonth = $budget['current_month'] ?? [];
    $prevMonth = $budget['prev_month'] ?? [];
    $p503020 = $budget['plan_50_30_20'] ?? [];
    $jars = $budget['plan_6_jars'] ?? [];
    $envelopes = $budget['envelopes'] ?? [];

    $prevIncome = (float)($prevMonth['income'] ?? 0);
    $needs = $p503020['needs'] ?? [];
    $wants = $p503020['wants'] ?? [];
    $savings = $p503020['savings'] ?? [];
@endphp

<style>
    .bb-dash-rec-card {
        background: #ffffff;
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
        margin-bottom: 2rem;
        overflow: hidden;
    }
    .bb-dash-rec-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
        color: #ffffff;
        padding: 1.35rem 1.75rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .bb-dash-env-card {
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 1.15rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .bb-dash-env-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.06);
    }
    .bb-dash-progress-track {
        height: 8px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin: 0.5rem 0;
    }
    .bb-dash-progress-bar {
        height: 100%;
        border-radius: 999px;
        transition: width 0.6s ease;
    }
    .bb-step-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.78rem;
        color: #ffffff;
        flex-shrink: 0;
    }
</style>

<div class="bb-dash-rec-card">
    {{-- Header --}}
    <div class="bb-dash-rec-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fs-4">🎯</span>
                    <h5 class="mb-0 fw-bold text-white">
                        Бюджет на {{ $curMonth['name'] }} з доходів за {{ $prevMonth['name'] }}
                    </h5>
                    <span class="badge rounded-pill bg-primary bg-opacity-25 text-white border border-primary border-opacity-50 px-2.5 py-1 small">
                        День {{ $curMonth['days_elapsed'] }} з {{ $curMonth['days_total'] }} (залишилось {{ $curMonth['days_left'] }})
                    </span>
                </div>
                <div class="text-white-50 small">
                    Принцип життя на дохід минулого місяця (Zero-Based Budgeting): ресурсна база —
                    <strong class="text-white">{{ $fmt($prevIncome) }}</strong>. Розподіл «що і куди» на поточний місяць:
                </div>
            </div>

            <div>
                <a href="{{ route('platform.analytic.finance') }}" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold text-primary">
                    Повна аналітика та калькулятор →
                </a>
            </div>
        </div>
    </div>

    <div class="p-4">
        {{-- 3 Core Envelopes --}}
        <div class="row g-3 mb-4">
            {{-- 1. Needs --}}
            <div class="col-lg-4">
                <div class="bb-dash-env-card" style="border-top: 4px solid #3b82f6 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-5">🏠</span>
                            <strong class="text-dark">Обов'язкове (50%)</strong>
                        </div>
                        <span class="badge rounded-pill {{ ($needs['spent'] ?? 0) <= ($needs['allocated'] ?? 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} small">
                            {{ ($needs['spent'] ?? 0) <= ($needs['allocated'] ?? 0) ? 'У ліміті' : 'Перевитрата' }}
                        </span>
                    </div>

                    <div class="my-2">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Виділено з доходу:</span>
                            <strong class="text-dark">{{ $fmt($needs['allocated'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Витрачено в {{ $curMonth['name'] }}:</span>
                            <span class="fw-bold text-primary">{{ $fmt($needs['spent'] ?? 0) }}</span>
                        </div>

                        <div class="bb-dash-progress-track">
                            <div class="bb-dash-progress-bar"
                                 style="width: {{ min(100, $needs['percent_used'] ?? 0) }}%; background: #3b82f6;"></div>
                        </div>

                        <div class="d-flex justify-content-between small text-muted" style="font-size: 0.72rem;">
                            <span>Використано: {{ $needs['percent_used'] ?? 0 }}%</span>
                            <span>Залишок: <strong style="color: {{ ($needs['remaining'] ?? 0) >= 0 ? '#10b981' : '#ef4444' }};">{{ $fmt($needs['remaining'] ?? 0) }}</strong></span>
                        </div>
                    </div>

                    <div class="mt-auto pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                        💡 До кінця місяця можна витрачати до <strong>{{ $fmt($needs['safe_daily'] ?? 0) }} / день</strong>
                    </div>
                </div>
            </div>

            {{-- 2. Wants --}}
            <div class="col-lg-4">
                <div class="bb-dash-env-card" style="border-top: 4px solid #f59e0b !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-5">☕</span>
                            <strong class="text-dark">Бажання та комфорт (30%)</strong>
                        </div>
                        <span class="badge rounded-pill {{ ($wants['spent'] ?? 0) <= ($wants['allocated'] ?? 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} small">
                            {{ ($wants['spent'] ?? 0) <= ($wants['allocated'] ?? 0) ? 'У ліміті' : 'Перевитрата' }}
                        </span>
                    </div>

                    <div class="my-2">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Виділено з доходу:</span>
                            <strong class="text-dark">{{ $fmt($wants['allocated'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Витрачено в {{ $curMonth['name'] }}:</span>
                            <span class="fw-bold text-warning-emphasis">{{ $fmt($wants['spent'] ?? 0) }}</span>
                        </div>

                        <div class="bb-dash-progress-track">
                            <div class="bb-dash-progress-bar"
                                 style="width: {{ min(100, $wants['percent_used'] ?? 0) }}%; background: #f59e0b;"></div>
                        </div>

                        <div class="d-flex justify-content-between small text-muted" style="font-size: 0.72rem;">
                            <span>Використано: {{ $wants['percent_used'] ?? 0 }}%</span>
                            <span>Залишок: <strong style="color: {{ ($wants['remaining'] ?? 0) >= 0 ? '#10b981' : '#ef4444' }};">{{ $fmt($wants['remaining'] ?? 0) }}</strong></span>
                        </div>
                    </div>

                    <div class="mt-auto pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                        💡 Безпечний темп: <strong>{{ $fmt($wants['safe_weekly'] ?? 0) }} / тиждень</strong> ({{ $fmt($wants['safe_daily'] ?? 0) }} / день)
                    </div>
                </div>
            </div>

            {{-- 3. Savings --}}
            <div class="col-lg-4">
                <div class="bb-dash-env-card" style="border-top: 4px solid #10b981 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-5">🛡️</span>
                            <strong class="text-dark">Заощадження (20%)</strong>
                        </div>
                        <span class="badge rounded-pill bg-success-subtle text-success small">
                            {{ ($savings['saved'] ?? 0) >= ($savings['allocated'] ?? 0) ? 'Виконано 🚀' : 'У процесі' }}
                        </span>
                    </div>

                    <div class="my-2">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Ціль накопичень:</span>
                            <strong class="text-dark">{{ $fmt($savings['allocated'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Фактично збережено:</span>
                            <span class="fw-bold text-success">{{ $fmt($savings['saved'] ?? 0) }}</span>
                        </div>

                        <div class="bb-dash-progress-track">
                            <div class="bb-dash-progress-bar"
                                 style="width: {{ min(100, $savings['percent_used'] ?? 0) }}%; background: #10b981;"></div>
                        </div>

                        <div class="d-flex justify-content-between small text-muted" style="font-size: 0.72rem;">
                            <span>Прогрес цілі: {{ $savings['percent_used'] ?? 0 }}%</span>
                            <span>{{ ($savings['remaining'] ?? 0) <= 0 ? '✓ Ціль закрита' : 'Залишилось: ' . $fmt($savings['remaining']) }}</span>
                        </div>
                    </div>

                    <div class="mt-auto pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                        🛡️ Відкладати одразу в подушку безпеки або депозит
                    </div>
                </div>
            </div>
        </div>

        {{-- "Що куди": Concrete Instructions Banner --}}
        <div class="p-3.5 rounded-4 bg-light border">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="fs-5">📋</span>
                <h6 class="mb-0 fw-bold text-dark">План розподілу коштів на поточний місяць («Що і куди»):</h6>
            </div>

            <div class="d-flex flex-column gap-2.5">
                @foreach($envelopes as $env)
                    <div class="p-2.5 rounded-3 bg-white border d-flex align-items-start gap-3">
                        <div class="bb-step-badge" style="background: {{ $env['color'] }};">
                            {{ $env['step'] }}
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0.5">
                                <strong class="text-dark">{{ $env['title'] }}: {{ $fmt($env['amount']) }}</strong>
                                <span class="badge rounded-pill small" style="background: {{ $env['bg'] }}; color: {{ $env['color'] }};">
                                    {{ $env['destination'] }}
                                </span>
                            </div>
                            <div class="text-muted small" style="font-size: 0.8rem;">
                                {{ $env['note'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
