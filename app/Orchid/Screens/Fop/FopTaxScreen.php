<?php

namespace App\Orchid\Screens\Fop;

use App\Models\Fop;
use App\Services\Finance\Fop\FopTaxService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class FopTaxScreen extends Screen
{
    /**
     * @var Fop|null
     */
    public $fop;

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
            ];
        }

        $this->fop = $fop;
        $year = (int)request()->get('year', Carbon::now()->year);
        $currentQuarter = (int)min(4, max(1, ceil(Carbon::now()->month / 3)));
        $declQuarter = (int)request()->get('declaration_quarter', $currentQuarter);

        $limitProgress = $taxService->getLimitProgress($fop, $year);
        $report = $taxService->getQuarterlyReport($fop, $year);
        $declaration = $taxService->getDeclarationSummary($fop, $year, $declQuarter);

        $docFilterQuarter = request()->has('doc_quarter') && request()->get('doc_quarter') !== ''
            ? (int)request()->get('doc_quarter')
            : null;

        $quarterDocuments = $taxService->getQuarterDocuments($fop, $year, $docFilterQuarter);
        $availableExpenses = $taxService->getRecentExpenseTransactions($fop, $year);

        return [
            'fop' => $fop,
            'limitProgress' => $limitProgress,
            'report' => $report,
            'declaration' => $declaration,
            'quarterDocuments' => $quarterDocuments,
            'docFilterQuarter' => $docFilterQuarter,
            'availableExpenses' => $availableExpenses,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Податки та Календар ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Розрахунок єдиного податку, ЄСВ, відстеження дедлайнів та дані для податкової декларації.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Профіль ФОП')
                ->icon('bs.person-gear')
                ->route('platform.fops'),

            Link::make('Книга обліку доходів')
                ->icon('bs.book')
                ->route('platform.fop.ledger'),
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
            Layout::view('fop.limit-progress-widget'),
            Layout::view('fop.quarterly-tax-card'),
        ];
    }

    /**
     * Record a tax payment expense transaction.
     */
    public function payTax(Request $request)
    {
        $taxService = new FopTaxService();
        $fop = $taxService->getFop();

        if (!$fop) {
            Toast::error('ФОП не знайдено.');
            return redirect()->back();
        }

        $quarter = (int)$request->input('quarter', 1);
        $year = (int)$request->input('year', Carbon::now()->year);
        $taxType = $request->input('tax_type', 'single_tax');
        $amount = (float)$request->input('amount', 0);

        if ($amount <= 0) {
            Toast::warning('Сума для сплати має бути більшою за 0.');
            return redirect()->back();
        }

        $taxTitle = match ($taxType) {
            'single_tax' => 'Сплата Єдиного Податку',
            'military_tax' => 'Сплата Військового збору',
            'esv' => 'Сплата ЄСВ',
            default => 'Сплата податку',
        };

        $transaction = $taxService->createTaxPayment($fop, $taxTitle, $amount, $quarter, $year, $taxType);

        Alert::success("Створено транзакцію витрат на суму " . number_format($amount, 2, '.', ' ') . " ₴ ({$taxTitle}).");

        return redirect()->route('platform.fop.tax', ['year' => $year]);
    }
}
