<?php

namespace App\Orchid\Screens\Fop;

use App\Models\Fop;
use App\Services\Finance\Fop\FopTaxService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class FopLedgerScreen extends Screen
{
    /**
     * @var Fop|null
     */
    public $fop;

    /**
     * @var int
     */
    public $year;

    /**
     * @var int|null
     */
    public $quarter;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $taxService = new FopTaxService();
        $fop = $taxService->getFop();

        if (!$fop) {
            return [
                'fop' => null,
                'records' => collect(),
            ];
        }

        $this->fop = $fop;
        $this->year = (int)request()->get('year', Carbon::now()->year);
        $this->quarter = request()->has('quarter') && request()->get('quarter') !== ''
            ? (int)request()->get('quarter')
            : null;

        $records = $taxService->getIncomeLedger($fop, $this->year, $this->quarter);
        $totalIncome = $records->sum('total_income');

        return [
            'fop' => $fop,
            'year' => $this->year,
            'quarter' => $this->quarter,
            'records' => $records,
            'metrics' => [
                'count' => $records->count(),
                'total_income' => number_format($totalIncome, 2, '.', ' ') . ' ₴',
                'period' => ($this->quarter ? "{$this->quarter} квартал " : 'Весь ') . "{$this->year} рік",
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Книга обліку доходів ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Офіційна типова форма обліку доходів платника єдиного податку.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $year = request()->get('year', Carbon::now()->year);
        $quarter = request()->get('quarter', '');

        return [
            Link::make('Експорт в Excel (CSV)')
                ->icon('bs.file-earmark-spreadsheet')
                ->route('platform.fop.ledger.export', ['year' => $year, 'quarter' => $quarter]),

            Link::make('Друкована версія / PDF')
                ->icon('bs.printer')
                ->route('platform.fop.ledger.print', ['year' => $year, 'quarter' => $quarter])
                ->target('_blank'),

            Link::make('Податки та Календар')
                ->icon('bs.calendar-check')
                ->route('platform.fop.tax'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        if (!$this->fop) {
            return [
                Layout::view('platform::partials.welcome', [
                    'title' => 'ФОП ще не створено',
                    'text' => 'Будь ласка, спочатку заповніть інформацію про свій ФОП у розділі "Мій ФОП".'
                ])
            ];
        }

        return [
            Layout::metrics([
                'Звітний період' => 'metrics.period',
                'Кількість записів' => 'metrics.count',
                'Загальна сума доходу' => 'metrics.total_income',
            ]),

            Layout::view('fop.ledger-filters'),

            Layout::table('records', [
                TD::make('index', '№ з/п')
                    ->width('60px')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn ($row) => $row instanceof \Orchid\Screen\Repository ? $row->get('index') : ($row['index'] ?? '')),

                TD::make('date', 'Дата запису')
                    ->width('110px')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn ($row) => "<strong>" . e($row instanceof \Orchid\Screen\Repository ? $row->get('date') : ($row['date'] ?? '')) . "</strong>"),

                TD::make('description', 'Зміст операції / Контрагент')
                    ->render(function ($row) {
                        $desc = $row instanceof \Orchid\Screen\Repository ? $row->get('description') : ($row['description'] ?? '');
                        $edrpou = $row instanceof \Orchid\Screen\Repository ? $row->get('payer_edrpou') : ($row['payer_edrpou'] ?? '-');
                        return "<div><strong>" . e($desc) . "</strong><br><small class='text-muted'>ЄДРПОУ/ІПН: " . e($edrpou) . "</small></div>";
                    }),

                TD::make('cashless_amount', 'Безготівковий дохід (грн)')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($row) => number_format((float)($row instanceof \Orchid\Screen\Repository ? $row->get('cashless_amount') : ($row['cashless_amount'] ?? 0)), 2, '.', ' ') . ' ₴'),

                TD::make('total_income', 'Всього дохід (грн)')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($row) => "<strong class='text-success'>" . number_format((float)($row instanceof \Orchid\Screen\Repository ? $row->get('total_income') : ($row['total_income'] ?? 0)), 2, '.', ' ') . " ₴</strong>"),

                TD::make('adjusted_income', 'Скоригований дохід (грн)')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($row) => "<strong class='text-dark'>" . number_format((float)($row instanceof \Orchid\Screen\Repository ? $row->get('adjusted_income') : ($row['adjusted_income'] ?? 0)), 2, '.', ' ') . " ₴</strong>"),
            ]),
        ];
    }
}
