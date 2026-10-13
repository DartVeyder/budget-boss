<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
    <div class="card-body p-4 text-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center shadow" 
                     style="width: 56px; height: 56px; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4);">
                    <x-orchid-icon path="bs.people-fill" class="text-white" width="26" height="26"/>
                </div>
                <div>
                    <h4 class="fw-bold mb-1 text-white">Спільний сімейний бюджет</h4>
                    <p class="text-white-50 mb-0 small">
                        Об'єднуйте фінанси з партнером, членами сім'ї або колегами в єдиний робочий простір.
                    </p>
                </div>
            </div>
            <div>
                @if($is_sharing)
                    <span class="badge bg-success bg-opacity-25 text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-check-circle-fill me-1"></i> Спільний доступ активний
                    </span>
                @else
                    <span class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-person me-1"></i> Індивідуальний режим
                    </span>
                @endif
            </div>
        </div>

        <div class="row g-3 mt-3 pt-3 border-top border-white border-opacity-10 text-white-50 small">
            <div class="col-md-4 d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-info fs-5"></i>
                <span><strong>Спільні рахунки:</strong> залишки обох учасників враховуються у загальний капітал</span>
            </div>
            <div class="col-md-4 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-right text-warning fs-5"></i>
                <span><strong>Єдині транзакції:</strong> бачте всі доходи та витрати із зазначенням автора</span>
            </div>
            <div class="col-md-4 d-flex align-items-center gap-2">
                <i class="bi bi-pie-chart text-success fs-5"></i>
                <span><strong>Спільна аналітика:</strong> графіки та звіти будуються за всіма спільними даними</span>
            </div>
        </div>
    </div>
</div>
