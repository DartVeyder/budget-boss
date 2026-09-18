<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Книга обліку доходів — {{ $fop->name }} ({{ $year }} рік)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 13pt;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            font-size: 9.5pt;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 2px 4px;
        }
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8.5pt;
        }
        table.ledger-table th, table.ledger-table td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: right;
        }
        table.ledger-table th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }
        table.ledger-table td.text-left {
            text-align: left;
        }
        table.ledger-table td.text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        .no-print {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 18px; font-weight: bold; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer;">
            🖨 Роздрукувати / Зберегти як PDF
        </button>
        <button onclick="window.close()" style="padding: 8px 14px; margin-left: 8px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Закрити
        </button>
    </div>

    <div class="header">
        <h2>КНИГА ОБЛІКУ ДОХОДІВ</h2>
        <p>(для платників єдиного податку першої, другої та третьої груп, які не є платниками ПДВ)</p>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 25%;"><strong>Платник податку (ФОП):</strong></td>
            <td style="width: 45%;">{{ $fop->name }}</td>
            <td style="width: 15%;"><strong>Звітний рік:</strong></td>
            <td style="width: 15%;">{{ $year }} {{ $quarter ? "({$quarter} квартал)" : '' }}</td>
        </tr>
        <tr>
            <td><strong>РНОКПП (ІПН):</strong></td>
            <td>{{ $fop->ipn }}</td>
            <td><strong>Група ФОП:</strong></td>
            <td>{{ $fop->fopGroup?->name ?? '3 група' }}</td>
        </tr>
        @if($fop->address)
        <tr>
            <td><strong>Податкова адреса:</strong></td>
            <td colspan="3">{{ $fop->address }}</td>
        </tr>
        @endif
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">№ з/п</th>
                <th rowspan="2" style="width: 75px;">Дата запису</th>
                <th rowspan="2">Зміст операції / Контрагент</th>
                <th colspan="3">Сума отриманого доходу, грн</th>
                <th rowspan="2" style="width: 85px;">Сума повернутих коштів, грн</th>
                <th rowspan="2" style="width: 95px;">Скоригована сума доходу, грн</th>
            </tr>
            <tr>
                <th style="width: 95px;">безготівкова</th>
                <th style="width: 85px;">готівкова</th>
                <th style="width: 95px;">всього</th>
            </tr>
            <tr style="background: #fafafa; font-size: 7.5pt;">
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @forelse($records as $row)
                @php $total += $row['total_income']; @endphp
                <tr>
                    <td class="text-center">{{ $row['index'] }}</td>
                    <td class="text-center">{{ $row['date'] }}</td>
                    <td class="text-left">{{ $row['description'] }}</td>
                    <td>{{ number_format($row['cashless_amount'], 2, ',', ' ') }}</td>
                    <td>0,00</td>
                    <td><strong>{{ number_format($row['total_income'], 2, ',', ' ') }}</strong></td>
                    <td>0,00</td>
                    <td><strong>{{ number_format($row['adjusted_income'], 2, ',', ' ') }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #777;">
                        За вказаний період записів про отримання доходу не знайдено.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; font-weight: bold;">РАЗОМ ЗА ПЕРІОД:</td>
                <td>{{ number_format($total, 2, ',', ' ') }}</td>
                <td>0,00</td>
                <td>{{ number_format($total, 2, ',', ' ') }}</td>
                <td>0,00</td>
                <td>{{ number_format($total, 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div>
            ФОП: _____________________ ( {{ $fop->name }} )
        </div>
        <div>
            Дата формування: {{ date('d.m.Y H:i') }}
        </div>
    </div>

</body>
</html>
