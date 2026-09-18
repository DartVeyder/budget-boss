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
            'military_tax' => 'Сплата Військового збору',
            'esv' => 'Сплата ЄСВ',
            default => 'Сплата податку',
        };

        $taxService->createTaxPayment($fop, $taxTitle, $amount, $quarter, $year);

        Alert::success("Створено транзакцію витрат на суму " . number_format($amount, 2, '.', ' ') . " ₴ ({$taxTitle}).");

        return redirect()->route('platform.fop.tax', ['year' => $year]);
    }

    /**
     * Upload a document for a quarter.
     */
    public function uploadQuarterDocument(Request $request, FopTaxService $taxService)
    {
        $fop = $taxService->getFop();
        if (!$fop) {
            Toast::error('ФОП не знайдено.');
            return redirect()->back();
        }

        $request->validate([
            'quarter'       => 'required|integer|min:1|max:4',
            'year'          => 'required|integer|min:2020|max:2050',
            'document_type' => 'required|string|max:50',
            'title'         => 'nullable|string|max:255',
            'notes'         => 'nullable|string|max:1000',
            'file'          => 'required|file|max:25600',
        ], [
            'file.required' => 'Будь ласка, оберіть файл для завантаження.',
            'file.max'      => 'Розмір файлу не може перевищувати 25 МБ.',
        ]);

        $quarter = (int)$request->input('quarter');
        $year = (int)$request->input('year');
        $documentType = (string)$request->input('document_type');
        $title = $request->input('title');
        $notes = $request->input('notes');

        $doc = $taxService->storeQuarterDocument(
            $fop,
            $year,
            $quarter,
            $documentType,
            $title,
            $notes,
            $request->file('file')
        );

        Toast::info("Документ «{$doc->title}» успішно додано до {$quarter} кварталу {$year} р.");

        return redirect()->route('platform.fop.tax', [
            'year' => $year,
            'doc_quarter' => $quarter,
        ]);
    }

    /**
     * Delete a quarter document.
     */
    public function deleteQuarterDocument(Request $request, $id, FopTaxService $taxService)
    {
        $fop = $taxService->getFop();
        if (!$fop) {
            Toast::error('ФОП не знайдено.');
            return redirect()->back();
        }

        $deleted = $taxService->deleteQuarterDocument($fop, (int)$id);

        if ($deleted) {
            Toast::info('Документ успішно видалено.');
        } else {
            Toast::error('Не вдалося видалити документ.');
        }

        return redirect()->back();
    }

    /**
     * Download a quarter document securely.
     */
    public function downloadQuarterDocument($id, FopTaxService $taxService)
    {
        $fop = $taxService->getFop();
        if (!$fop) {
            abort(404, 'ФОП не знайдено.');
        }

        $doc = \App\Models\FopQuarterDocument::where('fop_id', $fop->id)->findOrFail($id);

        if ($doc->attachment) {
            $filePath = storage_path('app/public/' . $doc->attachment->path . $doc->attachment->name . '.' . $doc->attachment->extension);
            if (file_exists($filePath)) {
                return response()->download($filePath, $doc->original_name);
            }
        }

        if ($doc->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)) {
            return response()->download(
                storage_path('app/public/' . $doc->file_path),
                $doc->original_name
            );
        }

        abort(404, 'Файл документа не знайдено у сховищі.');
    }
}
