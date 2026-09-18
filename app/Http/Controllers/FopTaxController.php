<?php

namespace App\Http\Controllers;

use App\Services\Finance\Fop\FopTaxService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;

class FopTaxController extends Controller
{
    /**
     * Export Income Ledger as CSV.
     */
    public function exportLedger(Request $request, FopTaxService $taxService)
    {
        $fop = $taxService->getFop();
        if (!$fop) {
            abort(404, 'ФОП не знайдено.');
        }

        $year = (int)$request->get('year', Carbon::now()->year);
        $quarter = $request->has('quarter') && $request->get('quarter') !== ''
            ? (int)$request->get('quarter')
            : null;

        return $taxService->exportLedgerCsv($fop, $year, $quarter);
    }

    /**
     * Display printable view of Income Ledger.
     */
    public function printLedger(Request $request, FopTaxService $taxService)
    {
        $fop = $taxService->getFop();
        if (!$fop) {
            abort(404, 'ФОП не знайдено.');
        }

        $year = (int)$request->get('year', Carbon::now()->year);
        $quarter = $request->has('quarter') && $request->get('quarter') !== ''
            ? (int)$request->get('quarter')
            : null;

        $records = $taxService->getIncomeLedger($fop, $year, $quarter);

        return view('fop.ledger-print', [
            'fop' => $fop,
            'year' => $year,
            'quarter' => $quarter,
            'records' => $records,
        ]);
    }

    /**
     * Record a tax payment expense transaction.
     */
    public function payTax(Request $request, FopTaxService $taxService)
    {
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
            'esv' => 'Сплата ЄСВ',
            default => 'Сплата податку',
        };

        $taxService->createTaxPayment($fop, $taxTitle, $amount, $quarter, $year);

        Alert::success("Створено транзакцію витрат на суму " . number_format($amount, 2, '.', ' ') . " ₴ ({$taxTitle}).");

        return redirect()->route('platform.fop.tax', ['year' => $year]);
    }
}
