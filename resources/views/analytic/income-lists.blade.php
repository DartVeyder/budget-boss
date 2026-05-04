<div class="row">
    <!-- Income By Bills -->
    <div class="col-md-6 mb-4">
        <div class="bg-white rounded shadow-sm p-4 h-100 border-top border-3 border-success">
            <h5 class="text-muted mb-4 fw-bold text-uppercase" style="letter-spacing: 0.5px;">{{ __('По рахунках (Доходи)') }}</h5>
            <div class="d-flex flex-column gap-3">
                @php
                    $billItems = $charts['income']['bill'][0]['items'] ?? [];
                @endphp
                
                @forelse($billItems as $item)
                    <div class="category-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-dark">{{ $item['name'] }}</span>
                            <span class="fw-bold" style="color: {{ $item['color'] }};">{{ $item['amount'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 8px; background-color: #f1f5f9; border-radius: 10px;">
                                <div class="progress-bar rounded-pill" role="progressbar" 
                                     style="width: {{ $item['percent'] }}%; background-color: {{ $item['color'] }};" 
                                     aria-valuenow="{{ $item['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <span class="text-muted small fw-medium" style="min-width: 45px; text-align: right;">{{ $item['percent'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-inbox mb-2" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625A.5.5 0 0 1 16 8.5V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .11-.312l3.7-4.625zM3.81 5h8.38l-2.4-3H6.21L3.81 5zM1 8.5v4.5A1 1 0 0 0 2 14h12a1 1 0 0 0 1-1V8.5H11.5a.5.5 0 0 0-.5.5 2.5 2.5 0 0 1-5 0 .5.5 0 0 0-.5-.5H1z"/>
                        </svg>
                        <p class="mb-0">{{ __('No data') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Income By Customers -->
    <div class="col-md-6 mb-4">
        <div class="bg-white rounded shadow-sm p-4 h-100 border-top border-3 border-success">
            <h5 class="text-muted mb-4 fw-bold text-uppercase" style="letter-spacing: 0.5px;">{{ __('По замовниках (Доходи)') }}</h5>
            <div class="d-flex flex-column gap-3">
                @php
                    $customerItems = $charts['income']['customer'][0]['items'] ?? [];
                @endphp
                
                @forelse($customerItems as $item)
                    <div class="category-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-dark">{{ $item['name'] }}</span>
                            <span class="fw-bold" style="color: {{ $item['color'] }};">{{ $item['amount'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 8px; background-color: #f1f5f9; border-radius: 10px;">
                                <div class="progress-bar rounded-pill" role="progressbar" 
                                     style="width: {{ $item['percent'] }}%; background-color: {{ $item['color'] }};" 
                                     aria-valuenow="{{ $item['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <span class="text-muted small fw-medium" style="min-width: 45px; text-align: right;">{{ $item['percent'] }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-inbox mb-2" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625A.5.5 0 0 1 16 8.5V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .11-.312l3.7-4.625zM3.81 5h8.38l-2.4-3H6.21L3.81 5zM1 8.5v4.5A1 1 0 0 0 2 14h12a1 1 0 0 0 1-1V8.5H11.5a.5.5 0 0 0-.5.5 2.5 2.5 0 0 1-5 0 .5.5 0 0 0-.5-.5H1z"/>
                        </svg>
                        <p class="mb-0">{{ __('No data') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
