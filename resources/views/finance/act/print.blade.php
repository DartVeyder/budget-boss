<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Акт надання послуг {{ $act->act_number }}</title>
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

        /* Верхній блок "ЗАТВЕРДЖУЮ" */
        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .approval-table td {
            width: 50%;
            vertical-align: top;
        }
        .approval-table td:first-child {
            padding-right: 25px;
        }
        .approval-table td:last-child {
            padding-left: 25px;
        }
        .approval-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 4px;
        }
        .approval-name {
            font-size: 9pt;
            margin-bottom: 26px;
        }
        .approval-line {
            border-bottom: 1px solid #000;
            width: 100%;
            margin-bottom: 4px;
        }
        .approval-person {
            font-size: 9pt;
        }

        /* Заголовок документа */
        .act-heading {
            text-align: center;
            margin-bottom: 18px;
        }
        .act-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .act-number-date {
            font-size: 10.5pt;
            font-weight: bold;
        }

        /* Текст вступу */
        .act-preamble {
            text-align: justify;
            margin-bottom: 10px;
            font-size: 9.5pt;
            line-height: 1.4;
        }

        /* Таблиця послуг */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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

        .summary-text {
            font-size: 9.5pt;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        /* Блок підписів */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            margin-bottom: 25px;
        }
        .signatures-table td {
            width: 50%;
            vertical-align: top;
        }
        .signatures-table td:first-child {
            padding-right: 25px;
        }
        .signatures-table td:last-child {
            padding-left: 25px;
        }
        .sign-label {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 24px;
        }
        .sign-underline {
            border-bottom: 1px solid #000;
            width: 100%;
            margin-bottom: 4px;
        }
        .sign-note {
            font-size: 8pt;
            color: #333;
            margin-top: 3px;
            min-height: 24px;
        }
        .sign-date {
            font-size: 9.5pt;
            margin-top: 6px;
        }

        /* Реквізити */
        .requisites-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9pt;
            line-height: 1.45;
        }
        .requisites-table td {
            width: 50%;
            vertical-align: top;
        }
        .requisites-table td:first-child {
            padding-right: 25px;
        }
        .requisites-table td:last-child {
            padding-left: 25px;
        }
        .req-title {
            font-weight: bold;
            margin-bottom: 2px;
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
            <a href="{{ route('platform.acts') }}" class="btn btn-outline">← До списку актів</a>
            <a href="{{ route('platform.acts.edit', $act) }}" class="btn btn-outline" style="margin-left: 8px;">Редагувати</a>
        </div>
        <div>
            @if($act->invoice)
                <a href="{{ route('platform.invoices.print', $act->invoice) }}" class="btn btn-outline" style="margin-right: 8px;">Друк Рахунку</a>
            @endif
            <button onclick="window.print()" class="btn btn-primary">🖨️ Друк / Зберегти як PDF</button>
        </div>
    </div>

    <!-- Блок "ЗАТВЕРДЖУЮ" вгорі -->
    <table class="approval-table">
        <tr>
            <td>
                <div class="approval-title">ЗАТВЕРДЖУЮ</div>
                <div class="approval-name">{{ $fopDetails['name_with_fop'] }}</div>
                <div class="approval-line"></div>
                <div class="approval-person">{{ $fopDetails['clean_name'] }}</div>
            </td>
            <td>
                <div class="approval-title">ЗАТВЕРДЖУЮ</div>
                <div class="approval-name">{{ $customerDetails['name_with_fop'] }}</div>
                <div class="approval-line"></div>
                <div class="approval-person">{{ $customerDetails['director'] ?: $customerDetails['clean_name'] }}</div>
            </td>
        </tr>
    </table>

    <!-- Заголовок акту -->
    <div class="act-heading">
        <div class="act-title">АКТ надання послуг</div>
        <div class="act-number-date">
            № {{ $act->act_number }} від {{ \App\Services\Finance\Act\UkrainianNumberToWords::formatUkrainianDate($act->act_date) }}
        </div>
    </div>

    <!-- Вступна частина -->
    <div class="act-preamble">
        Ми, що нижче підписалися, представник Замовника {{ $customerDetails['fop_with_name'] }}, з одного боку, і представник Виконавця {{ $fopDetails['fop_with_name'] }}.
    </div>
    <div class="act-preamble">
        Виконавцем були виконані наступні роботи (надані такі послуги):
    </div>

    <!-- Таблиця робіт / послуг -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 32px;">№</th>
                <th>Найменування робіт, послуг</th>
                <th style="width: 60px;">Кіл-ть</th>
                <th style="width: 65px;">Од.</th>
                <th style="width: 105px;">Ціна, грн</th>
                <th style="width: 105px;">Сума, грн</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($act->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item->name }}</td>
                    <td class="text-center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format($item->quantity, 2, ',', ' ') }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->price, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-left">Послуги згідно домовленості</td>
                    <td class="text-center">1</td>
                    <td class="text-center">послуга</td>
                    <td class="text-right">{{ number_format($act->total_amount, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($act->total_amount, 2, ',', ' ') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Разом -->
    <div class="total-row">
        Разом:&nbsp;&nbsp;{{ number_format($act->total_amount, 2, ',', ' ') }}
    </div>

    <!-- Сума прописом та відсутність претензій -->
    <div class="summary-text">
        Загальна вартість робіт (послуг) склала {{ \App\Services\Finance\Act\UkrainianNumberToWords::convert($act->total_amount, false) }}
    </div>
    <div class="summary-text">
        Замовник претензій по об'єму, якості та строкам виконання робіт (надання послуг) не має.
    </div>

    <!-- Блок підписів -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sign-label">Від Виконавця*</div>
                <div class="sign-underline"></div>
                <div>{{ $fopDetails['clean_name'] }}</div>
                <div class="sign-note">* Відповідальний за здійснення господарської операції і правильність її оформлення</div>
                <div class="sign-date">{{ $act->act_date ? $act->act_date->format('d.m.Y') : now()->format('d.m.Y') }}</div>
            </td>
            <td>
                <div class="sign-label">Від Замовника</div>
                <div class="sign-underline"></div>
                <div>{{ $customerDetails['director'] ?: $customerDetails['clean_name'] }}</div>
                <div class="sign-note"></div>
                <div class="sign-date">{{ $act->act_date ? $act->act_date->format('d.m.Y') : now()->format('d.m.Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Повні реквізити обох сторін у футері -->
    <table class="requisites-table">
        <tr>
            <td>
                <div class="req-title">{{ $fopDetails['name_with_fop'] }}</div>
                @if($fopDetails['address'])
                    <div>{{ \Illuminate\Support\Str::startsWith($fopDetails['address'], ['Адреса:', 'адреса:']) ? $fopDetails['address'] : 'Адреса: ' . $fopDetails['address'] }}</div>
                @endif
                @if($fopDetails['iban'])<div>{{ $fopDetails['iban'] }},</div>@endif
                @if($fopDetails['ipn'])<div>ІПН {{ $fopDetails['ipn'] }},</div>@endif
                @if($fopDetails['phone'])<div>Тел.: {{ $fopDetails['phone'] }},</div>@endif
                <div>Платник єдиного податку, {{ $fopDetails['tax_group'] }},</div>
                <div>Не платник ПДВ</div>
            </td>
            <td>
                <div class="req-title">{{ $customerDetails['name_with_fop'] }}</div>
                @if($customerDetails['address'])
                    <div>{{ \Illuminate\Support\Str::startsWith($customerDetails['address'], ['Адреса:', 'адреса:']) ? $customerDetails['address'] : 'Адреса: ' . $customerDetails['address'] }}</div>
                @endif
                @if($customerDetails['iban'])<div>{{ $customerDetails['iban'] }}</div>@endif
                @if($customerDetails['code'])<div>ІПН: {{ $customerDetails['code'] }},</div>@endif
                @if($customerDetails['phone'])
                    <div>{{ \Illuminate\Support\Str::startsWith($customerDetails['phone'], ['Тел', 'тел']) ? $customerDetails['phone'] : 'Тел.: ' . $customerDetails['phone'] }},</div>
                @endif
                @php
                    $taxInfo = $customerDetails['tax_info'] ?? '';
                    if (preg_match('/^(.*),\s*(Не платник ПДВ|Платник ПДВ|без ПДВ|з ПДВ)$/ui', $taxInfo, $taxMatches)) {
                        $taxLine1 = trim($taxMatches[1]) . ',';
                        $taxLine2 = trim($taxMatches[2]);
                    } else {
                        $taxLine1 = $taxInfo;
                        $taxLine2 = null;
                    }
                @endphp
                @if($taxLine1)<div>{{ $taxLine1 }}</div>@endif
                @if($taxLine2)<div>{{ $taxLine2 }}</div>@endif
            </td>
        </tr>
    </table>

</div>
</body>
</html>
