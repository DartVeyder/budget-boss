<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рахунок на оплату {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, 'Helvetica Neue', Helvetica, 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        .no-print {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .btn-outline {
            background-color: transparent;
            color: #495057;
            border-color: #ced4da;
        }

        /* Банківський блок реквізитів згідно шаблону */
        .bank-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border: 1px solid #000;
        }
        .bank-box td {
            padding: 5px 8px;
            vertical-align: top;
        }
        .bb-left-top {
            width: 52%;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .bb-right-top {
            width: 48%;
            border-bottom: 1px solid #000;
        }
        .bb-left-bottom {
            width: 52%;
            border-right: 1px solid #000;
        }
        .bb-right-bottom {
            width: 48%;
            vertical-align: top;
        }
        .bb-label {
            font-size: 8pt;
            color: #222;
            margin-bottom: 2px;
        }
        .bb-val {
            font-size: 9pt;
            color: #000;
        }
        .bb-code-row {
            margin-top: 6px;
        }
        .bb-code-row .bb-label {
            display: inline-block;
            margin-right: 12px;
        }

        /* Заголовок рахунку */
        .invoice-title {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 6px;
        }
        .doc-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 6px 0 15px 0;
        }

        /* Сторони (Постачальник, Покупець, Договір) */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
            line-height: 1.4;
        }
        .parties-table td {
            vertical-align: top;
            padding: 3px 0;
        }
        .party-lbl {
            width: 165px;
            font-weight: normal;
            color: #000;
        }

        /* Таблиця товарів / робіт / послуг */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 6px;
            font-size: 9pt;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }
        .items-table th {
            text-align: center;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .total-row {
            display: flex;
            justify-content: flex-end;
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 6px;
            margin-bottom: 12px;
            padding-right: 4px;
        }

        .summary-count {
            font-size: 8.5pt;
            margin-bottom: 4px;
            color: #000;
        }
        .summary-words {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 12px;
            color: #000;
        }

        .bottom-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 12px 0 20px 0;
        }

        /* Блок підпису "Виписав(ла)" згідно шаблону docx */
        .signature-line-block {
            margin-top: 25px;
            display: flex;
            justify-content: flex-end;
            padding-right: 10px;
        }
        .signature-inner {
            display: flex;
            align-items: baseline;
            font-size: 9pt;
        }
        .sign-label {
            font-weight: bold;
            font-size: 9pt;
        }
        .sign-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 150px;
            margin: 0 10px;
        }
        .sign-name {
            font-size: 8.5pt;
            font-weight: normal;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="no-print">
        <div>
            <a href="{{ route('platform.invoices') }}" class="btn btn-outline">← До списку рахунків</a>
            @if($invoice->act)
                <a href="{{ route('platform.acts.print', $invoice->act) }}" class="btn btn-outline" style="margin-left: 8px;">Переглянути Акт</a>
            @else
                <a href="{{ route('platform.acts.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-outline" style="margin-left: 8px;">+ Сформувати Акт</a>
            @endif
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Друк / Зберегти як PDF</button>
        </div>
    </div>

    <!-- Банківський зразок платіжного доручення / реквізити отримувача -->
    <table class="bank-box">
        <tr>
            <td class="bb-left-top">
                <div class="bb-label">Отримувач</div>
                <div class="bb-val"><strong>{{ $fopDetails['name_with_fop'] }}</strong></div>
                <div class="bb-code-row">
                    <span class="bb-label">Код</span>
                    <span class="bb-val"><strong>{{ $fopDetails['ipn'] }}</strong></span>
                </div>
            </td>
            <td class="bb-right-top">
                &nbsp;
            </td>
        </tr>
        <tr>
            <td class="bb-left-bottom">
                <div class="bb-label">Банк отримувача</div>
                <div class="bb-val"><strong>{{ $fopDetails['bank_name'] ?: 'АТ «УНІВЕРСАЛ БАНК»' }}</strong></div>
            </td>
            <td class="bb-right-bottom">
                <div class="bb-label">КРЕДИТ рах. №</div>
                <div class="bb-val" style="font-size: 9.5pt; font-weight: bold; letter-spacing: 0.5px;">{{ $fopDetails['iban'] }}</div>
            </td>
        </tr>
    </table>

    <!-- Назва та номер рахунку -->
    <div class="invoice-title">
        Рахунок на оплату № {{ $invoice->invoice_number }} від {{ \App\Services\Finance\Act\UkrainianNumberToWords::formatUkrainianDate($invoice->invoice_date ?? $invoice->created_at) }}
    </div>
    <hr class="doc-divider">

    <!-- Сторони договору -->
    <table class="parties-table">
        <tr>
            <td class="party-lbl">Постачальник:</td>
            <td>
                <strong>{{ $fopDetails['name_with_fop'] }}</strong><br>
                {{ $fopDetails['iban'] }}  у банку {{ $fopDetails['bank_name'] ?: 'АТ «УНІВЕРСАЛ БАНК»' }}<br>
                @if($fopDetails['address']){{ $fopDetails['address'] }},<br>@endif
                Платник єдиного податку, {{ $fopDetails['tax_group'] }},<br>
                Не платник ПДВ, тел.: {{ $fopDetails['phone'] ?: '—' }},<br>
                код за ЄДРПОУ {{ $fopDetails['ipn'] }}, ІПН {{ $fopDetails['ipn'] }}
            </td>
        </tr>
        <tr>
            <td class="party-lbl" style="padding-top: 12px;">Покупець:</td>
            <td style="padding-top: 12px;">
                <strong>{{ $customerDetails['name_with_fop'] }}</strong>
            </td>
        </tr>
        @if ($invoice->contract_number || $invoice->contract_date)
        <tr>
            <td class="party-lbl" style="padding-top: 12px;">Договір надання послуг:</td>
            <td style="padding-top: 12px;">
                {{ $invoice->contract_number }} @if($invoice->contract_date) від {{ \App\Services\Finance\Act\UkrainianNumberToWords::formatUkrainianDate($invoice->contract_date) }} @endif
            </td>
        </tr>
        @endif
    </table>

    @php
        $items = !empty($invoice->items_data) && is_array($invoice->items_data) ? $invoice->items_data : [
            [
                'name' => $invoice->comment ?: "Оплата згідно рахунку {$invoice->invoice_number}",
                'unit' => 'послуга',
                'quantity' => 1,
                'price' => (float)$invoice->total,
                'amount' => (float)$invoice->total,
            ]
        ];
    @endphp

    <!-- Таблиця товарів та послуг -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 32px;">№</th>
                <th>Товари (роботи, послуги)</th>
                <th colspan="2" style="width: 120px;">Кількість</th>
                <th style="width: 105px;">Ціна, грн</th>
                <th style="width: 110px;">Сума, грн</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                @php
                    $qty = (float)($item['quantity'] ?? 1);
                    $price = (float)($item['price'] ?? 0);
                    $amt = (float)($item['amount'] ?? ($qty * $price));
                    $unit = $item['unit'] ?? 'послуга';
                    $qtyDisplay = ($qty == (int)$qty ? (int)$qty : number_format($qty, 2, ',', ' '));
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item['name'] ?? '' }}</td>
                    <td class="text-right" style="width: 50px;">{{ $qtyDisplay }}</td>
                    <td class="text-center" style="width: 70px;">{{ $unit }}</td>
                    <td class="text-right">{{ number_format($price, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($amt, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Разом -->
    <div class="total-row">
        Разом:&nbsp;&nbsp;{{ number_format($invoice->total, 2, ',', ' ') }}
    </div>

    <!-- Всього найменувань та сума прописом -->
    <div class="summary-count">
        Всього найменувань {{ count($items) }}, на суму {{ number_format($invoice->total, 2, ',', ' ') }} ГРН.
    </div>
    <div class="summary-words">
        {{ \App\Services\Finance\Act\UkrainianNumberToWords::convert($invoice->total, true) }}.
    </div>

    <hr class="bottom-divider">

    <!-- Підпис -->
    <div class="signature-line-block">
        <div class="signature-inner">
            <span class="sign-label">Виписав(ла):</span>
            <span class="sign-line"></span>
            <span class="sign-name">{{ $fopDetails['clean_name'] }}</span>
        </div>
    </div>

</div>
</body>
</html>
