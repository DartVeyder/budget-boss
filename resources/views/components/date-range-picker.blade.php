<div class="mb-4 position-relative">
    <!-- Trigger Button -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
        <div class="position-relative">
            <button type="button" id="date-picker-trigger" class="btn btn-white bg-white border rounded shadow-sm px-4 py-2 fw-semibold text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem; cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar3 text-primary" viewBox="0 0 16 16">
                    <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                    <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                </svg>
                <span id="date-picker-display-text">{{ __('Loading date range...') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-chevron-down text-muted ms-1" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Dropdown Panel (Pixel Perfect recreation with premium modern UI/UX) -->
    <div id="date-picker-panel" class="bg-white rounded-4 border shadow-lg d-none position-absolute" style="z-index: 1050; width: 680px; max-width: 95vw; font-family: system-ui, -apple-system, sans-serif; overflow: hidden; animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Header / Tabs & Inputs -->
        <div class="px-4 pt-3 pb-2 border-bottom">
            <!-- Upper Tabs Row -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-1 bg-light p-1 rounded-3" style="font-size: 0.85rem;">
                    <button type="button" class="btn btn-sm fw-bold px-3 py-1 text-primary bg-white shadow-sm rounded-2 border-0" style="font-size: 0.8rem;">{{ __('Date Range') }}</button>
                    <button type="button" id="compare-dates-tab" class="btn btn-sm fw-medium px-2 py-1 text-muted rounded-2 border-0 bg-transparent transition-all" style="cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/>
                        </svg>
                    </button>
                </div>
                <div id="compare-text-indicator" class="text-muted small fw-bold text-uppercase d-flex align-items-center gap-1 transition-all" style="font-size: 0.72rem; letter-spacing: 0.5px; opacity: 0.5;">
                    <span class="status-dot bg-secondary rounded-circle" style="width: 6px; height: 6px; display: inline-block;"></span>
                    {{ __('Compare Dates') }}
                </div>
            </div>

            <!-- Inputs & Exclusion Row -->
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                <!-- Dropdown unit -->
                <select id="date-picker-unit" class="form-select form-select-sm border rounded-3 fw-semibold text-dark select-premium-hover" style="width: 85px; font-size: 0.8rem; height: 36px; cursor: pointer;">
                    <option value="days">{{ __('Days') }}</option>
                    <option value="weeks">{{ __('Weeks') }}</option>
                    <option value="months">{{ __('Months') }}</option>
                </select>

                <!-- Inputs start and end -->
                <div class="d-flex align-items-center gap-1 border rounded-3 px-3 py-1 bg-white flex-grow-1 transition-all" style="height: 36px;" id="input-range-container">
                    <input type="text" id="start-date-input" readonly class="border-0 p-0 text-center fw-bold text-dark bg-transparent" style="width: 95px; font-size: 0.8rem; outline: none; cursor: pointer;">
                    
                    <!-- Animating Start Time -->
                    <div id="start-time-container" class="time-container-anim animate-collapse">
                        <span class="text-muted text-separator">@</span>
                        <input type="text" id="start-time-input" class="border-0 p-0 text-primary bg-transparent time-input-field fw-semibold" style="width: 45px; font-size: 0.78rem; outline: none; text-align: center;" placeholder="00:00">
                    </div>
                    
                    <span class="text-muted px-1 fw-medium" style="font-size: 0.8rem;">to</span>
                    
                    <input type="text" id="end-date-input" readonly class="border-0 p-0 text-center fw-bold text-dark bg-transparent" style="width: 95px; font-size: 0.8rem; outline: none; cursor: pointer;">
                    
                    <!-- Animating End Time -->
                    <div id="end-time-container" class="time-container-anim animate-collapse">
                        <span class="text-muted text-separator">@</span>
                        <input type="text" id="end-time-input" class="border-0 p-0 text-primary bg-transparent time-input-field fw-semibold" style="width: 45px; font-size: 0.78rem; outline: none; text-align: center;" placeholder="23:59">
                    </div>
                </div>

                <!-- Exclusion Button (Now fully interactive) -->
                <button type="button" id="exclusion-toggle-btn" class="btn btn-sm btn-white border rounded-3 d-flex align-items-center gap-1 text-muted fw-bold transition-all" style="height: 36px; font-size: 0.8rem; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-exclude" viewBox="0 0 16 16">
                        <path d="M0 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/>
                    </svg>
                    <span>{{ __('Exclusion') }}</span>
                    <span id="exclusion-status-badge" class="badge bg-danger rounded-circle p-1 d-none" style="width: 6px; height: 6px;"></span>
                </button>
            </div>
        </div>

        <!-- Main Body: Quick Select (Left) & Calendar (Right) -->
        <div class="d-flex flex-row-responsive" style="height: 330px;" id="picker-body-wrapper">
            <!-- Left Panel: Quick Selection -->
            <div id="quick-select-panel" class="border-end py-2 px-1 bg-light-subtle d-flex flex-column gap-1 scroll-horizontal-mobile" style="width: 175px; overflow-y: auto;">
                <button type="button" class="quick-select-btn" data-range="this-week">{{ __('This week') }}</button>
                <button type="button" class="quick-select-btn" data-range="next-week">{{ __('Next week') }}</button>
                <button type="button" class="quick-select-btn" data-range="last-week">{{ __('Last week') }}</button>
                <button type="button" class="quick-select-btn" data-range="today">{{ __('Today') }}</button>
                <button type="button" class="quick-select-btn" data-range="last-7-days">{{ __('Last 7 days') }}</button>
                <button type="button" class="quick-select-btn" data-range="last-15-days">{{ __('Last 15 days') }}</button>
                <button type="button" class="quick-select-btn active-indigo" data-range="last-30-days">{{ __('Last 30 days') }}</button>
                <button type="button" class="quick-select-btn" data-range="this-month">{{ __('This month') }}</button>
                <button type="button" class="quick-select-btn" data-range="this-year">{{ __('This year') }}</button>
                <button type="button" class="quick-select-btn" data-range="custom">{{ __('Custom') }}</button>
            </div>

            <!-- Right Panel: Calendar Container -->
            <div class="flex-grow-1 p-3 d-flex flex-column" style="min-width: 0;">
                <!-- Month & Year Selector -->
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <button type="button" id="prev-month-btn" class="btn btn-sm btn-light border-0 rounded-circle d-flex align-items-center justify-content-center transition-all" style="width: 28px; height: 28px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l-6-6a.5.5 0 0 1 .708 0z"/>
                        </svg>
                    </button>
                    
                    <div class="d-flex align-items-center gap-1">
                        <select id="calendar-month-select" class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark p-0 pe-4 select-premium-hover" style="font-size: 0.9rem; width: auto; cursor: pointer;">
                            <option value="0">{{ __('January') }}</option>
                            <option value="1">{{ __('February') }}</option>
                            <option value="2">{{ __('March') }}</option>
                            <option value="3">{{ __('April') }}</option>
                            <option value="4">{{ __('May') }}</option>
                            <option value="5">{{ __('June') }}</option>
                            <option value="6">{{ __('July') }}</option>
                            <option value="7">{{ __('August') }}</option>
                            <option value="8">{{ __('September') }}</option>
                            <option value="9">{{ __('October') }}</option>
                            <option value="10">{{ __('November') }}</option>
                            <option value="11">{{ __('December') }}</option>
                        </select>
                        <select id="calendar-year-select" class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark p-0 pe-4 select-premium-hover" style="font-size: 0.9rem; width: auto; cursor: pointer;">
                            <!-- Will be populated dynamically by JS -->
                        </select>
                    </div>

                    <button type="button" id="next-month-btn" class="btn btn-sm btn-light border-0 rounded-circle d-flex align-items-center justify-content-center transition-all" style="width: 28px; height: 28px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                </div>

                <!-- Calendar Weekdays -->
                <div class="calendar-grid weekdays mb-1">
                    <div>SUN</div><div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div>
                </div>

                <!-- Calendar Days Grid -->
                <div id="calendar-days-grid" class="calendar-grid days flex-grow-1" style="min-height: 200px;">
                    <!-- Populated dynamically by JS -->
                </div>
            </div>
        </div>

        <!-- Footer / Enable Time Switch & Actions -->
        <div class="px-4 py-3 border-top bg-light d-flex justify-content-between align-items-center">
            <!-- Set Time Toggle (iOS style) -->
            <div class="form-check form-switch d-flex align-items-center gap-2 p-0 m-0">
                <input class="form-check-input ms-0 m-0 ios-switch-style" type="checkbox" id="set-time-toggle" style="cursor: pointer; width: 38px; height: 21px;">
                <label class="form-check-label fw-bold text-muted transition-all" id="set-time-label" for="set-time-toggle" style="font-size: 0.8rem; cursor: pointer; user-select: none; padding-left: 5px;">{{ __('Set Time') }}</label>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <button type="button" id="date-picker-cancel-btn" class="btn btn-sm btn-white border px-3 py-1.5 fw-bold text-muted rounded-3 transition-all" style="font-size: 0.8rem;">{{ __('Cancel') }}</button>
                <button type="button" id="date-picker-generate-btn" class="btn btn-sm btn-primary px-3 py-1.5 fw-bold text-white rounded-3 bg-indigo border-0 transition-all btn-generate-premium" style="font-size: 0.8rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);">{{ __('Generate') }}</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Premium visual styling and transitions */
    #date-picker-trigger:hover {
        border-color: #4f46e5 !important;
        background-color: #f8fafc !important;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.08) !important;
    }
    
    #date-picker-panel {
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.15) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        backdrop-filter: blur(10px);
        margin-top: 6px;
    }

    #input-range-container:focus-within {
        border-color: #4f46e5 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .quick-select-btn {
        width: 100%;
        text-align: left;
        padding: 8px 14px;
        background: transparent;
        border: 0;
        font-size: 0.78rem;
        font-weight: 650;
        color: #64748b;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .quick-select-btn:hover {
        background-color: #f1f5f9;
        color: #0f172a;
        transform: translateX(2px);
    }
    .quick-select-btn.active-indigo {
        background-color: #eef2ff !important;
        color: #4f46e5 !important;
        box-shadow: inset 2px 0 0 #4f46e5;
    }

    /* Select elements styling */
    .select-premium-hover {
        transition: all 0.2s ease;
    }
    .select-premium-hover:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }
    
    /* Calendar grid and day buttons */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        align-items: center;
    }
    .calendar-grid.weekdays {
        font-size: 0.65rem;
        font-weight: 800;
        color: #94a3b8;
        letter-spacing: 0.8px;
        height: 24px;
    }
    .calendar-grid.days {
        grid-gap: 3px 0px;
    }
    
    .calendar-day {
        font-size: 0.8rem;
        font-weight: 650;
        color: #334155;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        user-select: none;
        position: relative;
        transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        border-radius: 6px;
    }
    .calendar-day:hover:not(.empty-day) {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .calendar-day.empty-day {
        cursor: default;
    }
    
    /* Range Selection Highlight concepts matching SaaS premium */
    .calendar-day.selected-start {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
        border-radius: 0px !important;
        border-top-left-radius: 50% !important;
        border-bottom-left-radius: 50% !important;
        font-weight: 800;
    }
    .calendar-day.selected-end {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
        border-radius: 0px !important;
        border-top-right-radius: 50% !important;
        border-bottom-right-radius: 50% !important;
        font-weight: 800;
    }
    .calendar-day.selected-start.selected-end {
        border-radius: 50% !important;
    }
    .calendar-day.in-range {
        background-color: #eef2ff !important;
        color: #4f46e5 !important;
        border-radius: 0px !important;
    }

    /* REALTIME HOVER PREVIEW RANGE STYLES */
    .calendar-day.in-range-hover {
        background-color: #f0f3ff !important;
        color: #6366f1 !important;
        border-radius: 0px !important;
    }
    .calendar-day.selected-end-hover {
        background-color: #818cf8 !important;
        color: #ffffff !important;
        border-radius: 0px !important;
        border-top-right-radius: 50% !important;
        border-bottom-right-radius: 50% !important;
        font-weight: 800;
    }
    
    /* Switch Style (iOS Concept) */
    .ios-switch-style:checked {
        background-color: #4f46e5 !important;
        border-color: #4f46e5 !important;
    }
    
    /* Smooth collapse animation for Time fields */
    .time-container-anim {
        display: flex;
        align-items: center;
        opacity: 1;
        max-width: 120px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
        white-space: nowrap;
    }
    .time-container-anim.animate-collapse {
        opacity: 0;
        max-width: 0px;
        pointer-events: none;
    }
    .text-separator {
        font-size: 0.75rem;
        margin: 0 2px;
    }

    /* Cancel & Generate premium hovers */
    #date-picker-cancel-btn:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a !important;
    }
    .btn-generate-premium:hover {
        background-color: #4338ca !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35) !important;
    }
    .btn-generate-premium:active {
        transform: translateY(0);
    }

    /* Compare and Exclusion fully interactive hover and click states */
    #compare-dates-tab.active-compare {
        background-color: #eef2ff !important;
        color: #4f46e5 !important;
        border: 1px solid rgba(99, 102, 241, 0.3) !important;
    }
    #exclusion-toggle-btn.active-exclusion {
        background-color: #fef2f2 !important;
        border-color: #fca5a5 !important;
        color: #dc2626 !important;
    }

    /* Dynamic Responsive Media Query Adapter (Tablet & Mobile UX) */
    @media (max-width: 680px) {
        #date-picker-panel {
            width: 95vw !important;
            left: 2.5vw !important;
        }
        .flex-row-responsive {
            flex-direction: column !important;
            height: auto !important;
        }
        #quick-select-panel {
            width: 100% !important;
            flex-direction: row !important;
            border-end: 0 !important;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 12px !important;
            height: 48px !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            white-space: nowrap !important;
            -webkit-overflow-scrolling: touch;
        }
        .quick-select-btn {
            width: auto !important;
            display: inline-block !important;
            padding: 4px 12px !important;
            font-size: 0.75rem !important;
        }
        .quick-select-btn.active-indigo {
            box-shadow: none !important;
            border: 1px solid #4f46e5 !important;
        }
        .quick-select-btn:hover {
            transform: none !important;
        }
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById('date-picker-trigger');
    const panel = document.getElementById('date-picker-panel');
    const displayText = document.getElementById('date-picker-display-text');
    
    // Inputs & selectors
    const startDateInput = document.getElementById('start-date-input');
    const startTimeInput = document.getElementById('start-time-input');
    const endDateInput = document.getElementById('end-date-input');
    const endTimeInput = document.getElementById('end-time-input');
    const setTimeToggle = document.getElementById('set-time-toggle');
    const setTimeLabel = document.getElementById('set-time-label');
    const unitSelect = document.getElementById('date-picker-unit');
    
    const startTimeContainer = document.getElementById('start-time-container');
    const endTimeContainer = document.getElementById('end-time-container');
    
    const monthSelect = document.getElementById('calendar-month-select');
    const yearSelect = document.getElementById('calendar-year-select');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');
    const daysGrid = document.getElementById('calendar-days-grid');
    
    const cancelBtn = document.getElementById('date-picker-cancel-btn');
    const generateBtn = document.getElementById('date-picker-generate-btn');
    const quickSelectButtons = document.querySelectorAll('.quick-select-btn');

    // Compare and Exclusion elements
    const compareTabBtn = document.getElementById('compare-dates-tab');
    const compareIndicator = document.getElementById('compare-text-indicator');
    const exclusionBtn = document.getElementById('exclusion-toggle-btn');
    const exclusionBadge = document.getElementById('exclusion-status-badge');

    // Parse URL params for pre-selecting dates
    const urlParams = new URLSearchParams(window.location.search);
    let startVal = urlParams.get('created_at[start]');
    let endVal = urlParams.get('created_at[end]');
    
    let activeStart = startVal ? new Date(startVal) : new Date(new Date().setDate(new Date().getDate() - 30));
    let activeEnd = endVal ? new Date(endVal) : new Date();

    // Check if time is enabled in URL parameters
    const hasTime = (startVal && startVal.includes(' ')) || (endVal && endVal.includes(':'));
    setTimeToggle.checked = hasTime;
    
    // Initial State of month display
    let displayMonth = activeStart.getMonth();
    let displayYear = activeStart.getFullYear();

    // Setup Year Selector Dropdown
    const currentYear = new Date().getFullYear();
    for (let y = currentYear - 6; y <= currentYear + 2; y++) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearSelect.appendChild(opt);
    }

    // Toggle panel view
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        const rect = trigger.getBoundingClientRect();
        panel.style.top = (rect.bottom + window.scrollY) + 'px';
        panel.style.left = (rect.left + window.scrollX) + 'px';
        panel.classList.toggle('d-none');
    });

    // Close panel when clicking outside
    document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && e.target !== trigger && !trigger.contains(e.target)) {
            panel.classList.add('d-none');
        }
    });

    cancelBtn.addEventListener('click', () => panel.classList.add('d-none'));

    // Format dates to display beautifully inside triggers & inputs
    function formatDisplayDate(date) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('uk-UA', options);
    }
    
    function formatInputDate(date) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('uk-UA', options);
    }

    function formatTime(date) {
        const h = String(date.getHours()).padStart(2, '0');
        const m = String(date.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function syncInputs() {
        startDateInput.value = formatInputDate(activeStart);
        if (activeEnd) {
            endDateInput.value = formatInputDate(activeEnd);
        } else {
            endDateInput.value = '—';
        }
        startTimeInput.value = formatTime(activeStart);
        endTimeInput.value = activeEnd ? formatTime(activeEnd) : '23:59';
        
        // Handle collapse animations for Time Containers
        if (setTimeToggle.checked) {
            startTimeContainer.classList.remove('animate-collapse');
            endTimeContainer.classList.remove('animate-collapse');
            setTimeLabel.classList.add('text-primary');
            setTimeLabel.classList.remove('text-muted');
        } else {
            startTimeContainer.classList.add('animate-collapse');
            endTimeContainer.classList.add('animate-collapse');
            setTimeLabel.classList.remove('text-primary');
            setTimeLabel.classList.add('text-muted');
        }

        // Update triggering button text
        let displayStr = `${formatDisplayDate(activeStart)}`;
        if (setTimeToggle.checked) displayStr += `, ${formatTime(activeStart)}`;
        
        if (activeEnd) {
            displayStr += ` — ${formatDisplayDate(activeEnd)}`;
            if (setTimeToggle.checked) displayStr += `, ${formatTime(activeEnd)}`;
        }
        displayText.textContent = displayStr;
    }

    // Interactive Hover States variables
    let hoveredDate = null;

    // Render Calendar days
    function renderCalendar() {
        monthSelect.value = displayMonth;
        yearSelect.value = displayYear;
        daysGrid.innerHTML = '';

        const firstDayIndex = new Date(displayYear, displayMonth, 1).getDay();
        const totalDays = new Date(displayYear, displayMonth + 1, 0).getDate();

        // Empty cells padding
        for (let i = 0; i < firstDayIndex; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.classList.add('calendar-day', 'empty-day');
            daysGrid.appendChild(emptyCell);
        }

        // Add Month days
        for (let day = 1; day <= totalDays; day++) {
            const dayCell = document.createElement('div');
            dayCell.classList.add('calendar-day');
            dayCell.textContent = day;

            const thisDate = new Date(displayYear, displayMonth, day);
            dayCell.dataset.dateStr = thisDate.toDateString();
            
            // Check selection states
            const isStart = activeStart && thisDate.toDateString() === activeStart.toDateString();
            const isEnd = activeEnd && thisDate.toDateString() === activeEnd.toDateString();
            const inRange = activeStart && activeEnd && thisDate > activeStart && thisDate < activeEnd;

            if (isStart) dayCell.classList.add('selected-start');
            if (isEnd) dayCell.classList.add('selected-end');
            if (inRange) dayCell.classList.add('in-range');

            // --- UX ENHANCEMENT: REAL-TIME DYNAMIC HOVER PREVIEW ---
            dayCell.addEventListener('mouseenter', function() {
                if (activeStart && !activeEnd) {
                    hoveredDate = thisDate;
                    updateHoverHighlight();
                }
            });

            // Handle date cell clicks
            dayCell.addEventListener('click', function() {
                if (activeStart && activeEnd && activeStart.toDateString() !== activeEnd.toDateString()) {
                    activeStart = new Date(displayYear, displayMonth, day, 0, 0, 0);
                    activeEnd = null;
                } else if (activeStart && !activeEnd) {
                    const chosen = new Date(displayYear, displayMonth, day, 23, 59, 59);
                    if (chosen < activeStart) {
                        activeEnd = activeStart;
                        activeStart = new Date(displayYear, displayMonth, day, 0, 0, 0);
                    } else {
                        activeEnd = chosen;
                    }
                } else {
                    activeStart = new Date(displayYear, displayMonth, day, 0, 0, 0);
                }

                syncInputs();
                renderCalendar();
                setActiveQuickSelect('custom');
            });

            daysGrid.appendChild(dayCell);
        }
    }

    // Helper to draw live hover selections
    function updateHoverHighlight() {
        if (!activeStart || activeEnd || !hoveredDate) return;

        const allDayCells = daysGrid.querySelectorAll('.calendar-day:not(.empty-day)');
        allDayCells.forEach(cell => {
            const cellDate = new Date(cell.dataset.dateStr);
            cell.classList.remove('in-range-hover', 'selected-end-hover');

            if (hoveredDate > activeStart) {
                if (cellDate > activeStart && cellDate < hoveredDate) {
                    cell.classList.add('in-range-hover');
                } else if (cellDate.toDateString() === hoveredDate.toDateString()) {
                    cell.classList.add('selected-end-hover');
                }
            } else {
                if (cellDate < activeStart && cellDate > hoveredDate) {
                    cell.classList.add('in-range-hover');
                } else if (cellDate.toDateString() === hoveredDate.toDateString()) {
                    cell.classList.add('selected-start'); // Draw start visual on hover-start
                }
            }
        });
    }

    // Clear hover previews when leaving grid
    daysGrid.addEventListener('mouseleave', function() {
        hoveredDate = null;
        const allDayCells = daysGrid.querySelectorAll('.calendar-day');
        allDayCells.forEach(cell => {
            cell.classList.remove('in-range-hover', 'selected-end-hover');
        });
    });

    // Quick range selector implementation
    function selectRange(rangeType) {
        const today = new Date();
        
        switch (rangeType) {
            case 'today':
                activeStart = new Date(today.setHours(0,0,0,0));
                activeEnd = new Date(today.setHours(23,59,59,999));
                break;
            case 'this-week':
                const first = today.getDate() - today.getDay();
                activeStart = new Date(new Date(today).setDate(first));
                activeStart.setHours(0,0,0,0);
                activeEnd = new Date(new Date(today).setDate(first + 6));
                activeEnd.setHours(23,59,59,999);
                break;
            case 'next-week':
                const nextFirst = today.getDate() - today.getDay() + 7;
                activeStart = new Date(new Date(today).setDate(nextFirst));
                activeStart.setHours(0,0,0,0);
                activeEnd = new Date(new Date(today).setDate(nextFirst + 6));
                activeEnd.setHours(23,59,59,999);
                break;
            case 'last-week':
                const lastFirst = today.getDate() - today.getDay() - 7;
                activeStart = new Date(new Date(today).setDate(lastFirst));
                activeStart.setHours(0,0,0,0);
                activeEnd = new Date(new Date(today).setDate(lastFirst + 6));
                activeEnd.setHours(23,59,59,999);
                break;
            case 'last-7-days':
                activeStart = new Date(new Date().setDate(today.getDate() - 7));
                activeStart.setHours(0,0,0,0);
                activeEnd = new Date();
                activeEnd.setHours(23,59,59,999);
                break;
            case 'last-15-days':
                activeStart = new Date(new Date().setDate(today.getDate() - 15));
                activeStart.setHours(0,0,0,0);
                activeEnd = new Date();
                activeEnd.setHours(23,59,59,999);
                break;
            case 'last-30-days':
                activeStart = new Date(new Date().setDate(today.getDate() - 30));
                activeStart.setHours(0,0,0,0);
                activeEnd = new Date();
                activeEnd.setHours(23,59,59,999);
                break;
            case 'this-month':
                activeStart = new Date(today.getFullYear(), today.getMonth(), 1, 0,0,0);
                activeEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0, 23,59,59);
                break;
            case 'this-year':
                activeStart = new Date(today.getFullYear(), 0, 1, 0,0,0);
                activeEnd = new Date(today.getFullYear(), 11, 31, 23,59,59);
                break;
        }

        displayMonth = activeStart.getMonth();
        displayYear = activeStart.getFullYear();
        
        syncInputs();
        renderCalendar();
        setActiveQuickSelect(rangeType);
    }

    function setActiveQuickSelect(range) {
        quickSelectButtons.forEach(btn => {
            if (btn.getAttribute('data-range') === range) {
                btn.classList.add('active-indigo');
            } else {
                btn.classList.remove('active-indigo');
            }
        });
    }

    // Attach navigation events
    prevMonthBtn.addEventListener('click', () => {
        if (displayMonth === 0) {
            displayMonth = 11;
            displayYear--;
        } else {
            displayMonth--;
        }
        renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
        if (displayMonth === 11) {
            displayMonth = 0;
            displayYear++;
        } else {
            displayMonth++;
        }
        renderCalendar();
    });

    monthSelect.addEventListener('change', (e) => {
        displayMonth = parseInt(e.target.value);
        renderCalendar();
    });

    yearSelect.addEventListener('change', (e) => {
        displayYear = parseInt(e.target.value);
        renderCalendar();
    });

    // Time Toggle handler
    setTimeToggle.addEventListener('change', syncInputs);

    // Bind Quick Select Click Listeners
    quickSelectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            selectRange(btn.getAttribute('data-range'));
        });
    });

    // Compare Dates Fully Interactive click logic
    compareTabBtn.addEventListener('click', function() {
        const isActive = compareTabBtn.classList.toggle('active-compare');
        if (isActive) {
            compareIndicator.style.opacity = '1';
            compareIndicator.classList.remove('text-muted');
            compareIndicator.classList.add('text-primary');
            compareIndicator.querySelector('.status-dot').classList.replace('bg-secondary', 'bg-primary');
        } else {
            compareIndicator.style.opacity = '0.5';
            compareIndicator.classList.add('text-muted');
            compareIndicator.classList.remove('text-primary');
            compareIndicator.querySelector('.status-dot').classList.replace('bg-primary', 'bg-secondary');
        }
    });

    // Exclusion Button Fully Interactive click logic
    exclusionBtn.addEventListener('click', function() {
        const isActive = exclusionBtn.classList.toggle('active-exclusion');
        if (isActive) {
            exclusionBadge.classList.remove('d-none');
            exclusionBtn.querySelector('span').textContent = '{{ __('Excluded') }}';
        } else {
            exclusionBadge.classList.add('d-none');
            exclusionBtn.querySelector('span').textContent = '{{ __('Exclusion') }}';
        }
    });

    // Generate Button Click Redirects with proper URL parameters
    generateBtn.addEventListener('click', function() {
        if (!activeStart) return;
        const end = activeEnd || activeStart;
        
        // Parse time inputs if toggled
        if (setTimeToggle.checked) {
            const startT = startTimeInput.value.split(':');
            const endT = endTimeInput.value.split(':');
            
            if(startT.length === 2) activeStart.setHours(parseInt(startT[0]), parseInt(startT[1]));
            if(endT.length === 2) end.setHours(parseInt(endT[0]), parseInt(endT[1]));
        }

        // Format to YYYY-MM-DD or YYYY-MM-DD HH:mm:ss
        function pad(num) { return String(num).padStart(2, '0'); }
        function formatDb(date, includeTime) {
            const y = date.getFullYear();
            const m = pad(date.getMonth() + 1);
            const d = pad(date.getDate());
            if (!includeTime) return `${y}-${m}-${d}`;
            
            const hh = pad(date.getHours());
            const mm = pad(date.getMinutes());
            const ss = pad(date.getSeconds());
            return `${y}-${m}-${d} ${hh}:${mm}:${ss}`;
        }

        const startStr = formatDb(activeStart, setTimeToggle.checked);
        const endStr = formatDb(end, setTimeToggle.checked);

        // Redirect browser with filter params
        const newUrl = `${window.location.pathname}?created_at[start]=${encodeURIComponent(startStr)}&created_at[end]=${encodeURIComponent(endStr)}`;
        window.location.href = newUrl;
    });

    // Initial load runs
    syncInputs();
    renderCalendar();
    
    // Set initial quick select state based on active dates
    const diffTime = Math.abs(activeEnd - activeStart);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    if (diffDays >= 28 && diffDays <= 32) setActiveQuickSelect('last-30-days');
    else setActiveQuickSelect('custom');
});
</script>
