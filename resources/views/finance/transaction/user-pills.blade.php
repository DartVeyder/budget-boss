@if(!empty($is_sharing_budget) && !empty($user_summary))
@php
    $queryParams = request()->except(['transaction_user_id', 'user_id', 'page']);
@endphp
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" 
     style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-left: 4px solid #10b981 !important;">
    <div class="card-body p-4">
        <!-- Top bar: Title & Quick switcher pills -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <i class="icon-people fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        {{ __('Спільний бюджет: Транзакції') }}
                    </h5>
                    <span class="text-muted small">
                        {{ __('Перегляд операцій разом або роздільно по кожному учаснику') }}
                    </span>
                </div>
            </div>

            <!-- Quick Filter Pills -->
            <div class="d-flex flex-wrap align-items-center gap-1 bg-light p-1 rounded-pill border">
                @php
                    $allUrl = request()->fullUrlWithQuery(array_merge($queryParams, ['transaction_user_id' => null, 'user_id' => null]));
                @endphp
                <a href="{{ $allUrl }}" 
                   class="btn btn-sm rounded-pill px-3 fw-semibold {{ empty($selected_user_id) ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none' }}">
                    <i class="icon-grid me-1"></i> {{ __('Всі транзакції') }}
                </a>
                @foreach($shared_users as $su)
                    @php
                        $userUrl = request()->fullUrlWithQuery(array_merge($queryParams, ['transaction_user_id' => $su->id]));
                        $isCurrentActive = ($selected_user_id == $su->id);
                    @endphp
                    <a href="{{ $userUrl }}" 
                       class="btn btn-sm rounded-pill px-3 fw-semibold {{ $isCurrentActive ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none' }}">
                        <i class="icon-user me-1"></i> {{ $su->name }} {{ $su->id === auth()->id() ? '('.__('Ви').')' : '' }}
                    </a>
                @endforeach
            </div>
        </div>

        @if(!empty($selected_user_id))
            @php
                $activeMember = $shared_users->firstWhere('id', $selected_user_id);
            @endphp
            <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 py-2 px-3 d-flex justify-content-between align-items-center mb-3">
                <div class="small text-primary-emphasis d-flex align-items-center gap-2">
                    <i class="icon-info"></i>
                    <span>
                        {{ __('Показано транзакції лише для:') }} 
                        <strong>{{ $activeMember ? $activeMember->name : '#' . $selected_user_id }}</strong>.
                    </span>
                </div>
                <a href="{{ $allUrl }}" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2 fw-semibold">
                    {{ __('Показати всі транзакції') }}
                </a>
            </div>
        @endif

        <!-- Separated Member Summary Row -->
        <div class="row g-3">
            @foreach($user_summary as $summary)
                @php
                    $userObj = $summary['user'];
                    $isMe = $summary['is_me'];
                    $isActive = $summary['is_active'];
                    $netPositive = $summary['net'] >= 0;
                    $singleUserUrl = request()->fullUrlWithQuery(array_merge($queryParams, ['transaction_user_id' => $userObj->id]));
                @endphp
                <div class="col-lg-6 col-12">
                    <div class="p-3 rounded-4 border h-100 transition-all {{ $isActive ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-white' }}"
                         style="border-width: {{ $isActive ? '2px' : '1px' }} !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 36px; height: 36px; background: {{ $isMe ? '#eef2ff' : '#fef3c7' }}; color: {{ $isMe ? '#4f46e5' : '#d97706' }};">
                                    {{ mb_strtoupper(mb_substr($userObj->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        {{ $userObj->name }}
                                        @if($isMe)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small ms-1">{{ __('Ви') }}</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill small ms-1">{{ __('Партнер') }}</span>
                                        @endif
                                    </h6>
                                    <span class="text-muted small">{{ $summary['count'] }} {{ __('операцій всього') }}</span>
                                </div>
                            </div>

                            @if(!$isActive)
                                <a href="{{ $singleUserUrl }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 small">
                                    <i class="icon-filter me-1"></i>{{ __('Фільтрувати') }}
                                </a>
                            @else
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1 small">
                                    <i class="icon-check me-1"></i>{{ __('Активно') }}
                                </span>
                            @endif
                        </div>

                        <!-- Income / Expenses / Balance metrics -->
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 rounded-3 bg-light border-0">
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Доходи') }}</div>
                                    <div class="fw-bold text-success" style="font-size: 0.9rem;">
                                        +{{ number_format($summary['income'], 0, '.', ' ') }} ₴
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded-3 bg-light border-0">
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Витрати') }}</div>
                                    <div class="fw-bold text-danger" style="font-size: 0.9rem;">
                                        -{{ number_format($summary['expenses'], 0, '.', ' ') }} ₴
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded-3 bg-light border-0">
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ __('Чистий підсумок') }}</div>
                                    <div class="fw-bold {{ $netPositive ? 'text-success' : 'text-danger' }}" style="font-size: 0.9rem;">
                                        {{ $netPositive ? '+' : '' }}{{ number_format($summary['net'], 0, '.', ' ') }} ₴
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
