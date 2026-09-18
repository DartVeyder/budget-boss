@if(isset($limitProgress))
<div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%); border-left: 5px solid {{ $limitProgress['percent'] >= 90 ? '#dc3545' : ($limitProgress['percent'] >= 70 ? '#ffc107' : '#0d6efd') }} !important;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Моніторинг ФОП &bull; {{ $limitProgress['year'] }} рік</span>
                <h4 class="fw-bold text-dark mb-0 mt-1 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-shield-check text-primary" viewBox="0 0 16 16">
                        <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                        <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                    </svg>
                    Річний ліміт доходу ФОП
                </h4>
            </div>
            <div>
                <span class="badge rounded-pill px-3 py-2 fw-semibold 
                    {{ $limitProgress['status'] === 'danger' ? 'bg-danger text-white' : ($limitProgress['status'] === 'warning' ? 'bg-warning text-dark' : 'bg-success-subtle text-success border border-success-subtle') }}" style="font-size: 0.85rem;">
                    {{ $limitProgress['message'] }}
                </span>
            </div>
        </div>

        <div class="row g-3 my-2">
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-muted d-block small mb-1">Отриманий дохід ({{ $limitProgress['year'] }})</span>
                    <h3 class="fw-bold text-success mb-0">
                        {{ number_format($limitProgress['income'], 2, '.', ' ') }} ₴
                    </h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-muted d-block small mb-1">Річний ліміт групи</span>
                    <h3 class="fw-bold text-dark mb-0">
                        {{ number_format($limitProgress['limit'], 2, '.', ' ') }} ₴
                    </h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white rounded-3 border">
                    <span class="text-muted d-block small mb-1">Залишок до досягнення ліміту</span>
                    <h3 class="fw-bold {{ $limitProgress['remaining'] < 500000 ? 'text-danger' : 'text-primary' }} mb-0">
                        {{ number_format($limitProgress['remaining'], 2, '.', ' ') }} ₴
                    </h3>
                </div>
            </div>
        </div>

        <!-- Visual Progress Bar -->
        <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-1 small">
                <span class="fw-semibold text-muted">Використано: <strong class="text-dark">{{ $limitProgress['percent'] }}%</strong></span>
                <span class="fw-semibold text-muted">Залишилося: <strong class="text-dark">{{ round(100 - $limitProgress['percent'], 2) }}%</strong></span>
            </div>
            <div class="progress rounded-pill shadow-inner" style="height: 14px; background-color: #e9ecef;">
                <div class="progress-bar progress-bar-striped progress-bar-animated 
                    {{ $limitProgress['percent'] >= 90 ? 'bg-danger' : ($limitProgress['percent'] >= 70 ? 'bg-warning' : 'bg-primary') }}" 
                    role="progressbar" 
                    style="width: {{ min(100, $limitProgress['percent']) }}%;" 
                    aria-valuenow="{{ $limitProgress['percent'] }}" 
                    aria-valuemin="0" 
                    aria-valuemax="100">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
            <a href="{{ route('platform.fop.tax') }}" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 rounded-pill px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-calculator" viewBox="0 0 16 16">
                    <path d="M12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h8zM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4z"/>
                    <path d="M4 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-4z"/>
                </svg>
                Податки та Календар
            </a>
            <a href="{{ route('platform.fop.ledger') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 rounded-pill px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                    <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                </svg>
                Книга доходів
            </a>
        </div>
    </div>
</div>
@endif
