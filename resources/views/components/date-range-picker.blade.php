@php
    $createdAt  = request()->get('created_at', []);
    $startParam = $createdAt['start'] ?? null;
    $endParam   = $createdAt['end']   ?? null;
    $hasFilter  = !empty($startParam) && !empty($endParam);

    $startDateObj   = null;
    $endDateObj     = null;
    $formattedRange = '';
    $presetBadge    = null;
    $startVal       = '';
    $endVal         = '';

    if ($hasFilter) {
        try {
            $startDateObj   = \Carbon\Carbon::parse($startParam);
            $endDateObj     = \Carbon\Carbon::parse($endParam);
            $formattedRange = $startDateObj->format('d.m.Y') . ' — ' . $endDateObj->format('d.m.Y');
            $startVal       = $startDateObj->format('Y-m-d');
            $endVal         = $endDateObj->format('Y-m-d');

            $today = \Carbon\Carbon::today();
            if ($startVal === $today->format('Y-m-d') && $endVal === $today->format('Y-m-d')) {
                $presetBadge = 'Сьогодні';
            } elseif ($startVal === $today->copy()->subDay()->format('Y-m-d') && $endVal === $today->copy()->subDay()->format('Y-m-d')) {
                $presetBadge = 'Вчора';
            } elseif ($startVal === $today->copy()->subDays(6)->format('Y-m-d') && $endVal === $today->format('Y-m-d')) {
                $presetBadge = 'Останні 7 днів';
            } elseif ($startVal === $today->copy()->subDays(29)->format('Y-m-d') && $endVal === $today->format('Y-m-d')) {
                $presetBadge = 'Останні 30 днів';
            } elseif ($startVal === $today->copy()->startOfMonth()->format('Y-m-d') && $endVal === $today->copy()->endOfMonth()->format('Y-m-d')) {
                $presetBadge = 'Цей місяць';
            } elseif ($startVal === $today->copy()->subMonth()->startOfMonth()->format('Y-m-d') && $endVal === $today->copy()->subMonth()->endOfMonth()->format('Y-m-d')) {
                $presetBadge = 'Минулий місяць';
            } elseif ($startVal === $today->copy()->startOfYear()->format('Y-m-d') && $endVal === $today->copy()->endOfYear()->format('Y-m-d')) {
                $presetBadge = 'Цей рік';
            } elseif ($startVal === $today->copy()->subYear()->startOfYear()->format('Y-m-d') && $endVal === $today->copy()->subYear()->endOfYear()->format('Y-m-d')) {
                $presetBadge = 'Минулий рік';
            }
        } catch (\Exception $e) {
            $formattedRange = 'Некоректний період';
        }
    } else {
        $formattedRange = 'Останній рік (за замовчуванням)';
        $startVal = \Carbon\Carbon::now()->subYear()->format('Y-m-d');
        $endVal   = \Carbon\Carbon::now()->format('Y-m-d');
    }

    $baseUrl = request()->url();

    // Accounts / Bills data
    $billIdParam = request()->get('bill_id');
    $billIdParam = (!empty($billIdParam) && is_numeric($billIdParam)) ? (int)$billIdParam : null;

    $billsList = $bills ?? (\App\Models\FinanceBill::where('user_id', auth()->id() ?? 0)->with('currency')->orderBy('name')->get());
    $activeBill = $selectedBill ?? ($billIdParam ? $billsList->firstWhere('id', $billIdParam) : null);

    if (!function_exists('bbGetBillIconInfo')) {
        function bbGetBillIconInfo($billName) {
            $lower = mb_strtolower($billName);
            if (str_contains($lower, 'готівка') || str_contains($lower, 'наличные') || str_contains($lower, 'cash')) {
                return ['icon' => '💵', 'bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Готівка'];
            }
            if (str_contains($lower, 'моно') || str_contains($lower, 'mono')) {
                return ['icon' => '🐱', 'bg' => '#f3f4f6', 'color' => '#111827', 'label' => 'Монобанк'];
            }
            if (str_contains($lower, 'приват') || str_contains($lower, 'privat')) {
                return ['icon' => '💚', 'bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => 'ПриватБанк'];
            }
            if (str_contains($lower, 'львів') || str_contains($lower, 'lviv')) {
                return ['icon' => '🏛️', 'bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'Банк Львів'];
            }
            if (str_contains($lower, 'фоп') || str_contains($lower, 'fop')) {
                return ['icon' => '💼', 'bg' => '#fef3c7', 'color' => '#d97706', 'label' => 'ФОП'];
            }
            return ['icon' => '💳', 'bg' => '#f8fafc', 'color' => '#475569', 'label' => 'Рахунок'];
        }
    }
@endphp

<!-- Flatpickr CSS: Local with CDN fallback -->
<link rel="stylesheet" href="/vendor/flatpickr/flatpickr.min.css" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';">

<style>
    .bb-filter-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
        position: relative;
        z-index: 1020;
    }
    .bb-date-picker-wrap,
    .bb-bill-picker-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
    }
    .bb-date-trigger-btn,
    .bb-bill-trigger-btn {
        background-color: #ffffff;
        color: #2b3445;
        border: 1px solid #d8e2ef;
        border-radius: 8px;
        padding: 0.55rem 1rem;
        font-size: 0.925rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        user-select: none;
    }
    .bb-date-trigger-btn:hover,
    .bb-bill-trigger-btn:hover {
        border-color: #4f46e5;
        background-color: #fbfbfe;
        color: #1e293b;
        box-shadow: 0 3px 8px rgba(79,70,229,0.12);
    }
    .bb-date-trigger-btn.active,
    .bb-bill-trigger-btn.active {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .bb-bill-trigger-btn.has-filter {
        border-color: #059669;
        background-color: #f0fdf4;
        color: #065f46;
        box-shadow: 0 1px 3px rgba(5,150,105,0.1);
    }
    .bb-bill-trigger-btn.has-filter:hover {
        border-color: #047857;
        background-color: #ecfdf5;
        box-shadow: 0 3px 8px rgba(5,150,105,0.18);
    }
    .bb-bill-trigger-btn.has-filter.active {
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
    }
    .bb-bill-currency-badge {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
        font-size: 0.725rem;
        font-weight: 700;
        padding: 0.18rem 0.45rem;
        border-radius: 4px;
        letter-spacing: 0.02em;
    }
    .bb-bill-clear-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: #bbf7d0;
        color: #166534;
        font-size: 0.75rem;
        font-weight: bold;
        line-height: 1;
        transition: all 0.15s ease;
        margin-left: -0.15rem;
    }
    .bb-bill-clear-btn:hover {
        background-color: #ef4444;
        color: #ffffff;
    }
    .bb-date-panel {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.14), 0 5px 15px rgba(0, 0, 0, 0.06);
        padding: 1.15rem;
        z-index: 99999;
        min-width: 820px;
        max-width: 95vw;
        box-sizing: border-box;
    }
    .bb-date-panel.show,
    .bb-bill-panel.show {
        display: block;
        animation: bbFadeIn 0.18s ease-out forwards;
    }
    @keyframes bbFadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .bb-picker-body {
        display: flex;
        gap: 1.25rem;
    }
    .bb-presets-col {
        width: 170px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        border-right: 1px solid #edf2f7;
        padding-right: 1rem;
    }
    .bb-presets-header {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
        margin-bottom: 0.35rem;
        padding-left: 0.5rem;
    }
    .bb-preset-btn {
        background: transparent;
        border: none;
        border-radius: 6px;
        padding: 0.45rem 0.65rem;
        text-align: left;
        font-size: 0.85rem;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .bb-preset-btn:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .bb-preset-btn.active {
        background-color: #eef2ff;
        color: #4f46e5;
        font-weight: 600;
    }
    .bb-calendar-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        direction: ltr !important;
        text-align: left !important;
        overflow: visible;
    }
    .bb-footer-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid #edf2f7;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .bb-date-inputs {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #64748b;
    }
    .bb-date-input {
        width: 115px;
        padding: 0.35rem 0.6rem;
        font-size: 0.85rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background-color: #f8fafc;
        text-align: center;
        color: #1e293b;
        font-family: monospace;
    }
    .bb-date-input:focus {
        border-color: #4f46e5;
        outline: none;
        background-color: #fff;
    }
    .bb-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .bb-btn-reset {
        padding: 0.45rem 0.9rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: #64748b;
        background: transparent;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .bb-btn-reset:hover {
        background-color: #f1f5f9;
        color: #334155;
    }
    .bb-btn-apply {
        padding: 0.45rem 1.15rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
        background-color: #4f46e5;
        border: 1px solid #4f46e5;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
        box-shadow: 0 1px 2px rgba(79, 70, 229, 0.2);
    }
    .bb-btn-apply:hover {
        background-color: #4338ca;
        border-color: #4338ca;
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
    }

    /* Bill panel styling */
    .bb-bill-panel {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.14), 0 5px 15px rgba(0, 0, 0, 0.06);
        padding: 0.85rem;
        z-index: 99999;
        min-width: 320px;
        max-width: 380px;
        box-sizing: border-box;
    }
    .bb-bill-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.65rem;
        padding: 0 0.25rem;
    }
    .bb-bill-panel-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
    }
    .bb-bill-panel-count {
        font-size: 0.725rem;
        color: #94a3b8;
        font-weight: 500;
    }
    .bb-bill-search-box {
        position: relative;
        margin-bottom: 0.65rem;
    }
    .bb-bill-search-input {
        width: 100%;
        padding: 0.45rem 0.75rem 0.45rem 2.1rem;
        font-size: 0.85rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #f8fafc;
        outline: none;
        box-sizing: border-box;
        transition: all 0.15s ease;
    }
    .bb-bill-search-input:focus {
        border-color: #4f46e5;
        background: #ffffff;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.12);
    }
    .bb-bill-search-icon {
        position: absolute;
        left: 0.7rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .bb-bill-list {
        max-height: 290px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        padding-right: 0.15rem;
    }
    .bb-bill-divider {
        height: 1px;
        background-color: #f1f5f9;
        margin: 0.35rem 0;
    }
    .bb-bill-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.65rem;
        border-radius: 7px;
        cursor: pointer;
        text-decoration: none;
        color: #334155;
        transition: all 0.15s ease;
        user-select: none;
    }
    .bb-bill-item:hover {
        background-color: #f8fafc;
        color: #0f172a;
    }
    .bb-bill-item.active {
        background-color: #eef2ff;
        color: #4f46e5;
        font-weight: 600;
    }
    .bb-bill-item-left {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }
    .bb-bill-item-icon {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .bb-bill-item-info {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
        min-width: 0;
    }
    .bb-bill-item-name {
        font-size: 0.865rem;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bb-bill-item.active .bb-bill-item-name {
        color: #4f46e5;
        font-weight: 600;
    }
    .bb-bill-item-sub {
        font-size: 0.725rem;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bb-bill-item-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        flex-shrink: 0;
    }
    .badge-curr-uah {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .badge-curr-usd {
        background-color: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .badge-curr-eur {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }
    .bb-bill-check {
        color: #4f46e5;
        flex-shrink: 0;
    }
    .bb-btn-reset-all {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.825rem;
        font-weight: 500;
        color: #64748b;
        background-color: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 0.55rem 0.85rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .bb-btn-reset-all:hover {
        background-color: #f8fafc;
        border-color: #ef4444;
        color: #ef4444;
    }

    /* Flatpickr core resets & styling inside calendar column */
    .bb-calendar-col .flatpickr-calendar {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        font-family: inherit !important;
        width: auto !important;
        display: inline-block !important;
        direction: ltr !important;
        position: relative !important;
        top: 0 !important;
        opacity: 1 !important;
        visibility: visible !important;
        margin: 0 !important;
    }
    .bb-calendar-col .flatpickr-months {
        display: flex !important;
        align-items: center !important;
        position: relative !important;
        height: 38px !important;
    }
    .bb-calendar-col .flatpickr-month {
        flex: 1 !important;
        color: #1e293b !important;
        fill: #1e293b !important;
        height: 38px !important;
        overflow: visible !important;
        position: relative !important;
    }
    .bb-calendar-col .flatpickr-current-month {
        font-size: 1rem !important;
        font-weight: 600 !important;
        padding-top: 4px !important;
        color: #1e293b !important;
        position: relative !important;
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 4px !important;
        left: 0 !important;
        right: 0 !important;
        transform: none !important;
    }
    .bb-calendar-col .flatpickr-current-month .flatpickr-monthDropdown-months,
    .bb-calendar-col .flatpickr-current-month input.cur-year {
        font-weight: 600 !important;
        color: #1e293b !important;
    }
    .bb-calendar-col .flatpickr-prev-month,
    .bb-calendar-col .flatpickr-next-month {
        position: absolute !important;
        top: 2px !important;
        height: 32px !important;
        width: 32px !important;
        padding: 8px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        color: #475569 !important;
        fill: #475569 !important;
        z-index: 10 !important;
    }
    .bb-calendar-col .flatpickr-prev-month:hover,
    .bb-calendar-col .flatpickr-next-month:hover {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
    }
    .bb-calendar-col .flatpickr-innerContainer {
        display: flex !important;
        overflow: visible !important;
        direction: ltr !important;
    }
    .bb-calendar-col .flatpickr-rContainer {
        display: inline-block !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    .bb-calendar-col .flatpickr-weekdays {
        display: flex !important;
        align-items: center !important;
        height: 28px !important;
    }
    .bb-calendar-col span.flatpickr-weekday {
        color: #94a3b8 !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
    }
    .bb-calendar-col .flatpickr-days {
        width: auto !important;
        display: flex !important;
        overflow: visible !important;
    }
    .bb-calendar-col .dayContainer {
        width: 300px !important;
        min-width: 300px !important;
        max-width: 300px !important;
        padding: 0 4px !important;
        display: inline-grid !important;
        grid-template-columns: repeat(7, 1fr) !important;
        justify-items: center !important;
        box-sizing: border-box !important;
    }
    .bb-calendar-col .flatpickr-day {
        border-radius: 6px !important;
        color: #334155 !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        height: 36px !important;
        line-height: 36px !important;
        margin: 1px 0 !important;
        width: 36px !important;
        max-width: 36px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
    }
    .bb-calendar-col .flatpickr-day:hover {
        background: #f1f5f9 !important;
        border-color: #f1f5f9 !important;
    }
    .bb-calendar-col .flatpickr-day.selected,
    .bb-calendar-col .flatpickr-day.startRange,
    .bb-calendar-col .flatpickr-day.endRange {
        background: #4f46e5 !important;
        border-color: #4f46e5 !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    .bb-calendar-col .flatpickr-day.inRange {
        background: #eef2ff !important;
        border-color: #eef2ff !important;
        box-shadow: -5px 0 0 #eef2ff, 5px 0 0 #eef2ff !important;
        color: #3730a3 !important;
        border-radius: 0 !important;
    }
    .bb-calendar-col .flatpickr-day.today {
        border-color: #4f46e5 !important;
    }

    @media (max-width: 860px) {
        .bb-date-panel {
            min-width: 320px;
            width: 95vw;
            padding: 0.75rem;
        }
        .bb-picker-body {
            flex-direction: column;
        }
        .bb-presets-col {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid #edf2f7;
            padding-right: 0;
            padding-bottom: 0.75rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }
        .bb-presets-header {
            grid-column: span 2;
        }
        .bb-calendar-col .dayContainer {
            min-width: 280px !important;
            max-width: 280px !important;
            width: 280px !important;
        }
    }
</style>

<div class="bb-filter-toolbar">
    <!-- 1. Flatpickr Date Range Picker -->
    <div class="bb-date-picker-wrap" id="bb-date-picker-component">
        <button type="button" class="bb-date-trigger-btn {{ $hasFilter ? 'has-filter' : '' }}" id="bb-trigger-btn" title="Натисніть для вибору періоду">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="color: #4f46e5;">
                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
            </svg>
            <span id="bb-btn-text">{{ $formattedRange }}</span>
            @if($presetBadge)
                <span class="badge" style="background-color: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px;">{{ $presetBadge }}</span>
            @endif
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" style="color: #94a3b8;" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
            </svg>
        </button>

        @if($hasFilter)
            <button type="button" class="btn btn-sm btn-link text-decoration-none ms-1 text-muted" id="bb-date-clear-link" style="font-size: 0.85rem; padding: 0.25rem 0.45rem;" title="Скинути фільтр дат">
                ✕ Скинути
            </button>
        @endif

        <!-- Flatpickr Dropdown Panel -->
        <div class="bb-date-panel" id="bb-date-panel">
            <div class="bb-picker-body">
                <!-- Left: Quick presets column -->
                <div class="bb-presets-col">
                    <div class="bb-presets-header">Швидкий період</div>
                    <button type="button" class="bb-preset-btn" data-preset="today">Сьогодні</button>
                    <button type="button" class="bb-preset-btn" data-preset="yesterday">Вчора</button>
                    <button type="button" class="bb-preset-btn" data-preset="last-7-days">Останні 7 днів</button>
                    <button type="button" class="bb-preset-btn" data-preset="last-30-days">Останні 30 днів</button>
                    <button type="button" class="bb-preset-btn" data-preset="this-week">Цей тиждень</button>
                    <button type="button" class="bb-preset-btn" data-preset="last-week">Минулий тиждень</button>
                    <button type="button" class="bb-preset-btn" data-preset="this-month">Цей місяць</button>
                    <button type="button" class="bb-preset-btn" data-preset="last-month">Минулий місяць</button>
                    <button type="button" class="bb-preset-btn" data-preset="this-quarter">Цей квартал</button>
                    <button type="button" class="bb-preset-btn" data-preset="this-year">Цей рік</button>
                    <button type="button" class="bb-preset-btn" data-preset="last-year">Минулий рік</button>
                    <button type="button" class="bb-preset-btn" data-preset="all-time" style="color: #64748b; border-top: 1px solid #f1f5f9; margin-top: 0.25rem;">Весь час</button>
                </div>

                <!-- Right: Flatpickr Calendar -->
                <div class="bb-calendar-col">
                    <div id="bb-flatpickr-inline-calendar"></div>
                </div>
            </div>

            <!-- Footer: Inputs & Action buttons -->
            <div class="bb-footer-bar">
                <div class="bb-date-inputs">
                    <span>Період:</span>
                    <input type="text" id="bb-input-start" class="bb-date-input" placeholder="РРРР-ММ-ДД" value="{{ $startVal }}" readonly>
                    <span>—</span>
                    <input type="text" id="bb-input-end" class="bb-date-input" placeholder="РРРР-ММ-ДД" value="{{ $endVal }}" readonly>
                </div>
                <div class="bb-actions">
                    <button type="button" class="bb-btn-reset" id="bb-btn-clear">Скинути</button>
                    <button type="button" class="bb-btn-apply" id="bb-btn-apply">Застосувати</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Account / Bank Filter beside Date Picker -->
    <div class="bb-bill-picker-wrap" id="bb-bill-picker-component">
        @php
            $activeIconInfo = $activeBill ? bbGetBillIconInfo($activeBill->name) : null;
        @endphp
        <button type="button" class="bb-bill-trigger-btn {{ $activeBill ? 'has-filter active-bill' : '' }}" id="bb-bill-trigger-btn" title="Фільтр за рахунком або банком">
            <span class="bb-bill-trigger-icon">
                @if($activeBill)
                    {{ $activeIconInfo['icon'] }}
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="color: #059669;">
                        <path d="m8 0 6.61 3h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.382l.5 2a.498.498 0 0 1-.485.618H.5a.5.5 0 0 1-.485-.618l.5-2A.5.5 0 0 1 1 13V6H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 3h.89L8 0ZM3.777 3h8.447L8 1.056 3.777 3ZM2 6v7h1V6H2Zm2 0v7h2.5V6H4Zm3.5 0v7h1V6h-1Zm2 0v7H12V6H9.5ZM13 6v7h1V6h-1Zm2-1V4H1v1h14Zm-.39 9H1.39l-.25 1h13.72l-.25-1Z"/>
                    </svg>
                @endif
            </span>
            <span class="bb-bill-btn-text">
                {{ $activeBill ? $activeBill->name : 'Всі рахунки та банки' }}
            </span>
            @if($activeBill)
                <span class="bb-bill-currency-badge">{{ $activeBill->currency?->code ?? 'UAH' }}</span>
                <span class="bb-bill-clear-btn" id="bb-bill-clear-quick" title="Очистити фільтр рахунку">✕</span>
            @endif
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" style="color: #94a3b8;" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
            </svg>
        </button>

        <!-- Dropdown menu panel -->
        <div class="bb-bill-panel" id="bb-bill-panel">
            <div class="bb-bill-panel-header">
                <span class="bb-bill-panel-title">Оберіть рахунок або банк</span>
                <span class="bb-bill-panel-count">{{ $billsList->count() }} рахунків</span>
            </div>

            <div class="bb-bill-search-box">
                <svg class="bb-bill-search-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <input type="text" id="bb-bill-search-input" class="bb-bill-search-input" placeholder="Швидкий пошук рахунку..." autocomplete="off">
            </div>

            <div class="bb-bill-list" id="bb-bill-list">
                <!-- All accounts option -->
                <div class="bb-bill-item {{ !$activeBill ? 'active' : '' }}" data-bill-id="" data-bill-name="Всі рахунки та банки" data-bill-bank="">
                    <div class="bb-bill-item-left">
                        <div class="bb-bill-item-icon" style="background-color: #ecfdf5; color: #059669;">
                            🏛️
                        </div>
                        <div class="bb-bill-item-info">
                            <span class="bb-bill-item-name">Усі рахунки та банки</span>
                            <span class="bb-bill-item-sub">Загальна статистика за всіма рахунками</span>
                        </div>
                    </div>
                    @if(!$activeBill)
                        <svg class="bb-bill-check" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                        </svg>
                    @endif
                </div>

                <div class="bb-bill-divider"></div>

                <!-- List of individual bills -->
                @foreach($billsList as $bill)
                    @php
                        $iconInfo = bbGetBillIconInfo($bill->name);
                        $isCurrent = ($activeBill && $activeBill->id === $bill->id);
                        $currCode = $bill->currency?->code ?? 'UAH';
                    @endphp
                    <div class="bb-bill-item {{ $isCurrent ? 'active' : '' }}" 
                         data-bill-id="{{ $bill->id }}" 
                         data-bill-name="{{ $bill->name }}" 
                         data-bill-bank="{{ $bill->bank_name ?? '' }}">
                        <div class="bb-bill-item-left">
                            <div class="bb-bill-item-icon" style="background-color: {{ $iconInfo['bg'] }}; color: {{ $iconInfo['color'] }};">
                                {{ $iconInfo['icon'] }}
                            </div>
                            <div class="bb-bill-item-info">
                                <span class="bb-bill-item-name">{{ $bill->name }}</span>
                                <span class="bb-bill-item-sub">{{ $bill->bank_name ?: ($iconInfo['label'] . ' (' . $currCode . ')') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="bb-bill-item-badge badge-curr-{{ strtolower($currCode) }}">
                                {{ $currCode }}
                            </span>
                            @if($isCurrent)
                                <svg class="bb-bill-check" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 3. Global Reset Button if both or either filter is set -->
    @if($hasFilter || $activeBill)
        <a href="{{ $baseUrl }}" class="bb-btn-reset-all" title="Скинути всі встановлені фільтри">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
            </svg>
            <span>Скинути все</span>
        </a>
    @endif
</div>

<!-- Load Flatpickr library (local with CDN fallback) -->
<script>
    (function() {
        function loadScript(src, fallback, callback) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = callback;
            s.onerror = function() {
                if (fallback) {
                    var fb = document.createElement('script');
                    fb.src = fallback;
                    fb.onload = callback;
                    document.head.appendChild(fb);
                }
            };
            document.head.appendChild(s);
        }

        function initFlatpickrLibrary() {
            if (typeof flatpickr === 'undefined') {
                loadScript('/vendor/flatpickr/flatpickr.min.js', 'https://cdn.jsdelivr.net/npm/flatpickr', function() {
                    loadScript('/vendor/flatpickr/uk.js', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/uk.js', function() {
                        setupBBFilters();
                    });
                });
            } else {
                setupBBFilters();
            }
        }

        function setupBBFilters() {
            var dateComponent = document.getElementById('bb-date-picker-component');
            var billComponent = document.getElementById('bb-bill-picker-component');

            if (!dateComponent || dateComponent.dataset.initialized === 'true') return;
            dateComponent.dataset.initialized = 'true';

            var triggerBtn = document.getElementById('bb-trigger-btn');
            var panel = document.getElementById('bb-date-panel');
            var calendarEl = document.getElementById('bb-flatpickr-inline-calendar');
            var inputStart = document.getElementById('bb-input-start');
            var inputEnd = document.getElementById('bb-input-end');
            var applyBtn = document.getElementById('bb-btn-apply');
            var clearBtn = document.getElementById('bb-btn-clear');
            var presetBtns = dateComponent.querySelectorAll('.bb-preset-btn');
            var dateClearLink = document.getElementById('bb-date-clear-link');

            var initialStart = "{{ $startVal }}";
            var initialEnd   = "{{ $endVal }}";
            var fp = null;

            function initFp() {
                if (fp) return;
                var fpLocale = (typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns.uk) ? flatpickr.l10ns.uk : 'default';
                var showCount = window.innerWidth > 860 ? 2 : 1;

                fp = flatpickr(calendarEl, {
                    mode: 'range',
                    inline: true,
                    showMonths: showCount,
                    locale: fpLocale,
                    dateFormat: 'Y-m-d',
                    defaultDate: (initialStart && initialEnd) ? [initialStart, initialEnd] : [],
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 1) {
                            inputStart.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                            inputEnd.value   = instance.formatDate(selectedDates[0], 'Y-m-d');
                        } else if (selectedDates.length >= 2) {
                            inputStart.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                            inputEnd.value   = instance.formatDate(selectedDates[1], 'Y-m-d');
                        }
                        clearActivePresetClass();
                    }
                });
            }

            // Toggle date panel
            triggerBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var isOpen = panel.classList.contains('show');
                if (isOpen) {
                    closeDatePanel();
                } else {
                    openDatePanel();
                }
            });

            function openDatePanel() {
                closeBillPanel();
                panel.classList.add('show');
                triggerBtn.classList.add('active');
                if (!fp) {
                    initFp();
                } else {
                    fp.redraw();
                }
            }

            function closeDatePanel() {
                panel.classList.remove('show');
                triggerBtn.classList.remove('active');
            }

            // Close on click outside
            document.addEventListener('click', function(e) {
                if (!dateComponent.contains(e.target) && !e.target.closest('.flatpickr-calendar')) {
                    closeDatePanel();
                }
            });

            panel.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            function clearActivePresetClass() {
                presetBtns.forEach(function(b) { b.classList.remove('active'); });
            }

            function formatDateISO(d) {
                var month = '' + (d.getMonth() + 1);
                var day = '' + d.getDate();
                var year = d.getFullYear();
                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;
                return [year, month, day].join('-');
            }

            // Presets click handling
            presetBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    clearActivePresetClass();
                    btn.classList.add('active');

                    if (!fp) initFp();

                    var preset = btn.getAttribute('data-preset');
                    var now = new Date();
                    var s = new Date();
                    var e = new Date();

                    if (preset === 'today') {
                        // s and e are today
                    } else if (preset === 'yesterday') {
                        s.setDate(now.getDate() - 1);
                        e.setDate(now.getDate() - 1);
                    } else if (preset === 'last-7-days') {
                        s.setDate(now.getDate() - 6);
                    } else if (preset === 'last-30-days') {
                        s.setDate(now.getDate() - 29);
                    } else if (preset === 'this-week') {
                        var day = now.getDay() || 7; // 1 = Monday
                        s.setDate(now.getDate() - day + 1);
                        e.setDate(s.getDate() + 6);
                    } else if (preset === 'last-week') {
                        var day = now.getDay() || 7;
                        s.setDate(now.getDate() - day - 6);
                        e.setDate(s.getDate() + 6);
                    } else if (preset === 'this-month') {
                        s = new Date(now.getFullYear(), now.getMonth(), 1);
                        e = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    } else if (preset === 'last-month') {
                        s = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        e = new Date(now.getFullYear(), now.getMonth(), 0);
                    } else if (preset === 'this-quarter') {
                        var quarter = Math.floor(now.getMonth() / 3);
                        s = new Date(now.getFullYear(), quarter * 3, 1);
                        e = new Date(now.getFullYear(), quarter * 3 + 3, 0);
                    } else if (preset === 'this-year') {
                        s = new Date(now.getFullYear(), 0, 1);
                        e = new Date(now.getFullYear(), 11, 31);
                    } else if (preset === 'last-year') {
                        s = new Date(now.getFullYear() - 1, 0, 1);
                        e = new Date(now.getFullYear() - 1, 11, 31);
                    } else if (preset === 'all-time') {
                        applyFilter('', '');
                        return;
                    }

                    var sStr = formatDateISO(s);
                    var eStr = formatDateISO(e);

                    inputStart.value = sStr;
                    inputEnd.value   = eStr;

                    if (fp) fp.setDate([sStr, eStr], true);
                });
            });

            // Apply date filter (preserves bill_id)
            function applyFilter(start, end) {
                var url = new URL(window.location.href);
                if (start && end) {
                    url.searchParams.set('created_at[start]', start);
                    url.searchParams.set('created_at[end]', end);
                } else {
                    url.searchParams.delete('created_at[start]');
                    url.searchParams.delete('created_at[end]');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }

            applyBtn.addEventListener('click', function() {
                var s = inputStart.value.trim();
                var e = inputEnd.value.trim();
                if (!s || !e) {
                    alert('Будь ласка, оберіть діапазон дат у календарі');
                    return;
                }
                applyFilter(s, e);
            });

            clearBtn.addEventListener('click', function() {
                applyFilter('', '');
            });

            if (dateClearLink) {
                dateClearLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyFilter('', '');
                });
            }

            // ----------------------------------------------------
            // Account / Bank Filter interactions
            // ----------------------------------------------------
            var billTriggerBtn = document.getElementById('bb-bill-trigger-btn');
            var billPanel = document.getElementById('bb-bill-panel');
            var billSearchInput = document.getElementById('bb-bill-search-input');
            var billItems = document.querySelectorAll('.bb-bill-item');
            var billClearQuick = document.getElementById('bb-bill-clear-quick');

            function selectBill(billId) {
                var url = new URL(window.location.href);
                if (billId) {
                    url.searchParams.set('bill_id', billId);
                } else {
                    url.searchParams.delete('bill_id');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }

            function openBillPanel() {
                closeDatePanel();
                if (billPanel && billTriggerBtn) {
                    billPanel.classList.add('show');
                    billTriggerBtn.classList.add('active');
                    if (billSearchInput) {
                        setTimeout(function() { billSearchInput.focus(); }, 50);
                    }
                }
            }

            function closeBillPanel() {
                if (billPanel && billTriggerBtn) {
                    billPanel.classList.remove('show');
                    billTriggerBtn.classList.remove('active');
                }
            }

            if (billTriggerBtn && billPanel) {
                billTriggerBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (billPanel.classList.contains('show')) {
                        closeBillPanel();
                    } else {
                        openBillPanel();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (billComponent && !billComponent.contains(e.target)) {
                        closeBillPanel();
                    }
                });

                billPanel.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            if (billClearQuick) {
                billClearQuick.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectBill(null);
                });
            }

            billItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    var bId = item.getAttribute('data-bill-id');
                    selectBill(bId);
                });
            });

            if (billSearchInput) {
                billSearchInput.addEventListener('input', function(e) {
                    var q = (e.target.value || '').toLowerCase().trim();
                    billItems.forEach(function(item) {
                        var name = (item.getAttribute('data-bill-name') || '').toLowerCase();
                        var bank = (item.getAttribute('data-bill-bank') || '').toLowerCase();
                        if (!q || name.indexOf(q) !== -1 || bank.indexOf(q) !== -1) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        }

        // Initialize on DOM ready and Turbo/Turbolinks navigation
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFlatpickrLibrary);
        } else {
            initFlatpickrLibrary();
        }
        document.addEventListener('turbo:load', initFlatpickrLibrary);
        document.addEventListener('turbolinks:load', initFlatpickrLibrary);
    })();
</script>
