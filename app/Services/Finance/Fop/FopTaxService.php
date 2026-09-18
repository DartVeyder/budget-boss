<?php

namespace App\Services\Finance\Fop;

use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionType;
use App\Models\Fop;
use App\Models\FopQuarterDocument;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Orchid\Attachment\File as OrchidFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FopTaxService
{
    /**
     * Get the active FOP for the current user or given user ID.
     */
    public function getFop(?int $userId = null): ?Fop
    {
        $userId = $userId ?? Auth::id();
        return Fop::where('user_id', $userId)->where('is_active', true)->first()
            ?? Fop::where('user_id', $userId)->first();
    }

    /**
     * Calculate annual limit progress for the FOP.
     */
    public function getLimitProgress(Fop $fop, ?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $limit = $fop->effective_annual_limit;

        $incomeQuery = FinanceTransaction::query()
            ->where('is_balance', 1)
            ->where('type', 'income')
            ->whereYear('created_at', $year)
            ->where(function ($q) use ($fop) {
                $q->where('fop_id', $fop->id);
                if ($fop->finance_bill_id) {
                    $q->orWhere('finance_bill_id', $fop->finance_bill_id);
                }
            });

        $income = (float)$incomeQuery->sum('currency_amount');
        $remaining = max(0.0, $limit - $income);
        $percent = $limit > 0 ? round(($income / $limit) * 100, 2) : 0.0;

        if ($percent >= 100) {
            $status = 'danger';
            $message = 'Ліміт річного доходу вичерпано! Перевищення: ' . number_format($income - $limit, 2, '.', ' ') . ' ₴';
        } elseif ($percent >= 85) {
            $status = 'warning';
            $message = 'Увага! Використано ' . $percent . '% річного ліміту.';
        } elseif ($percent >= 65) {
            $status = 'info';
            $message = 'Використано понад половину ліміту (' . $percent . '%).';
        } else {
            $status = 'success';
            $message = 'Дохід у межах норми (' . $percent . '% ліміту).';
        }

        return [
            'year' => $year,
            'limit' => $limit,
            'income' => $income,
            'remaining' => $remaining,
            'percent' => $percent,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Get quarterly report with taxes and deadlines.
     */
    public function getQuarterlyReport(Fop $fop, ?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $monthlyEsv = $fop->effective_monthly_esv;
        $quarterlyEsv = $monthlyEsv * 3;

        // Fetch tax rates attached to FOP group
        $fop->loadMissing('fopGroup.taxRates');
        $rates = $fop->fopGroup?->taxRates ?? collect();

        // Correctly detect Single Tax rate (5% or name containing 'єдин')
        $singleTaxRate = $rates->first(function ($r) {
            $name = mb_strtolower($r->name);
            return str_contains($name, 'єдин') || str_contains($name, 'single') || (float)$r->value === 5.0;
        });
        $singleTaxPercent = (float)($singleTaxRate?->value ?? 5.0);

        // Correctly detect Military Tax rate (1% or name containing 'військов')
        $militaryRate = $rates->first(function ($r) {
            $name = mb_strtolower($r->name);
            return str_contains($name, 'військов') || str_contains($name, 'military') || (float)$r->value === 1.0;
        });
        $militaryTaxPercent = (float)($militaryRate?->value ?? 1.0);

        $now = Carbon::now();
        $quarters = [];

        $docCounts = FopQuarterDocument::where('fop_id', $fop->id)
            ->where('year', $year)
            ->selectRaw('quarter, count(*) as count')
            ->groupBy('quarter')
            ->pluck('count', 'quarter')
            ->toArray();

        for ($q = 1; $q <= 4; $q++) {
            $startMonth = ($q - 1) * 3 + 1;
            $endMonth = $q * 3;

            $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay();

            // Income transactions in this quarter
            $query = FinanceTransaction::query()
                ->where('is_balance', 1)
                ->where('type', 'income')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where(function ($sq) use ($fop) {
                    $sq->where('fop_id', $fop->id);
                    if ($fop->finance_bill_id) {
                        $sq->orWhere('finance_bill_id', $fop->finance_bill_id);
                    }
                });

            $income = (float)$query->sum('currency_amount');

            // Single Tax (5%) calculated on the total quarterly income
            $singleTax = round($income * ($singleTaxPercent / 100), 2);

            // Military tax (1%) calculated on the total quarterly income
            $militaryTax = round($income * ($militaryTaxPercent / 100), 2);

            $totalTax = $singleTax + $militaryTax + $quarterlyEsv;

            // Deadlines
            // ESV deadline: 19th of month following quarter
            $nextMonthOfQuarter = $endMonth + 1;
            $esvYear = $nextMonthOfQuarter > 12 ? $year + 1 : $year;
            $esvMonth = $nextMonthOfQuarter > 12 ? 1 : $nextMonthOfQuarter;
            $esvDeadline = Carbon::createFromDate($esvYear, $esvMonth, 19)->endOfDay();

            // Declaration deadline: 40 days after quarter end
            $declarationDeadline = $endDate->copy()->addDays(40)->endOfDay();

            // Single tax deadline: 10 days after declaration deadline
            $singleTaxDeadline = $declarationDeadline->copy()->addDays(10)->endOfDay();

            // Days left to nearest deadline
            $isPast = $endDate->isPast();
            $isCurrent = $now->between($startDate, $endDate);

            $deadlinesList = [
                'esv' => [
                    'title' => 'Сплата ЄСВ',
                    'date' => $esvDeadline,
                    'amount' => $quarterlyEsv,
                    'days_left' => $now->diffInDays($esvDeadline, false),
                ],
                'declaration' => [
                    'title' => 'Подання декларації ЄП',
                    'date' => $declarationDeadline,
                    'days_left' => $now->diffInDays($declarationDeadline, false),
                ],
                'single_tax' => [
                    'title' => 'Сплата Єдиного Податку',
                    'date' => $singleTaxDeadline,
                    'amount' => $singleTax,
                    'days_left' => $now->diffInDays($singleTaxDeadline, false),
                ],
            ];

            // Cumulative income for declaration up to this quarter
            $cumulativeIncome = (float)FinanceTransaction::query()
                ->where('is_balance', 1)
                ->where('type', 'income')
                ->whereBetween('created_at', [Carbon::createFromDate($year, 1, 1)->startOfDay(), $endDate])
                ->where(function ($sq) use ($fop) {
                    $sq->where('fop_id', $fop->id);
                    if ($fop->finance_bill_id) {
                        $sq->orWhere('finance_bill_id', $fop->finance_bill_id);
                    }
                })->sum('currency_amount');

            $cumulativeSingleTax = round($cumulativeIncome * ($singleTaxPercent / 100), 2);

            $quarters[$q] = [
                'quarter' => $q,
                'name' => $q . ' квартал ' . $year,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'income' => $income,
                'single_tax_percent' => $singleTaxPercent,
                'single_tax' => $singleTax,
                'military_tax' => $militaryTax,
                'esv' => $quarterlyEsv,
                'total_tax' => $totalTax,
                'deadlines' => $deadlinesList,
                'is_current' => $isCurrent,
                'is_past' => $isPast,
                'cumulative_income' => $cumulativeIncome,
                'cumulative_single_tax' => $cumulativeSingleTax,
                'documents_count' => (int)($docCounts[$q] ?? 0),
            ];
        }

        return [
            'year' => $year,
            'fop' => $fop,
            'is_esv_exempt' => (bool)$fop->is_esv_exempt,
            'monthly_esv' => $monthlyEsv,
            'single_tax_percent' => $singleTaxPercent,
            'military_tax_percent' => $militaryTaxPercent,
            'quarters' => $quarters,
            'total_year_income' => array_sum(array_column($quarters, 'income')),
            'total_year_taxes' => array_sum(array_column($quarters, 'total_tax')),
            'total_year_single_tax' => array_sum(array_column($quarters, 'single_tax')),
            'total_year_military_tax' => array_sum(array_column($quarters, 'military_tax')),
            'total_year_esv' => array_sum(array_column($quarters, 'esv')),
            'total_year_documents' => array_sum($docCounts),
        ];
    }

    /**
     * Get records for the official Income Ledger book.
     */
    public function getIncomeLedger(Fop $fop, int $year, ?int $quarter = null): Collection
    {
        $query = FinanceTransaction::query()
            ->with(['customer', 'counterparty', 'bill'])
            ->where('is_balance', 1)
            ->where('type', 'income')
            ->whereYear('created_at', $year)
            ->where(function ($sq) use ($fop) {
                $sq->where('fop_id', $fop->id);
                if ($fop->finance_bill_id) {
                    $sq->orWhere('finance_bill_id', $fop->finance_bill_id);
                }
            })
            ->orderBy('created_at', 'asc');

        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->get()->map(function (FinanceTransaction $tx, $index) {
            $amount = (float)$tx->currency_amount;
            
            $payerName = $tx->counterparty?->name ?: ($tx->customer?->name ?: 'Клієнт');
            $payerEdrpou = $tx->counterparty?->ipn ?: ($tx->customer?->ipn ?: ($tx->customer?->edrpou ?: '-'));

            $description = $tx->customer?->name ?: ($tx->comment ?: 'Надходження за послуги / товари');
            if ($tx->counterparty && $tx->customer && $tx->counterparty->name !== $tx->customer->name) {
                $description = "{$tx->customer->name} (через {$tx->counterparty->name})";
            }

            return new \Orchid\Screen\Repository([
                'index' => $index + 1,
                'id' => $tx->id,
                'date' => $tx->created_at ? $tx->created_at->format('d.m.Y') : '',
                'created_at' => $tx->created_at,
                'description' => $description,
                'payer' => $payerName,
                'payer_edrpou' => $payerEdrpou,
                'cashless_amount' => $amount,
                'cash_amount' => 0.00,
                'total_income' => $amount,
                'returns_amount' => 0.00,
                'adjusted_income' => $amount,
            ]);
        });
    }

    /**
     * Get summary figures for the Single Tax Declaration (F0103308).
     */
    public function getDeclarationSummary(Fop $fop, int $year, int $quarter): array
    {
        $endMonth = $quarter * 3;
        $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay();
        $yearStart = Carbon::createFromDate($year, 1, 1)->startOfDay();

        // Load rates
        $fop->loadMissing('fopGroup.taxRates');
        $rates = $fop->fopGroup?->taxRates ?? collect();
        $singleTaxRate = $rates->first(function ($r) {
            $name = mb_strtolower($r->name);
            return str_contains($name, 'єдин') || str_contains($name, 'single') || (float)$r->value === 5.0;
        });
        $rate = (float)($singleTaxRate?->value ?? 5.0);

        // Cumulative income for the reporting period (Jan 1 to Quarter End)
        $cumulativeIncome = (float)FinanceTransaction::query()
            ->where('is_balance', 1)
            ->where('type', 'income')
            ->whereBetween('created_at', [$yearStart, $endDate])
            ->where(function ($sq) use ($fop) {
                $sq->where('fop_id', $fop->id);
                if ($fop->finance_bill_id) {
                    $sq->orWhere('finance_bill_id', $fop->finance_bill_id);
                }
            })->sum('currency_amount');

        // Total single tax accrued cumulatively
        $cumulativeTax = round($cumulativeIncome * ($rate / 100), 2);

        // Tax accrued in previous periods of this year
        $previousTax = 0.0;
        if ($quarter > 1) {
            $prevEndMonth = ($quarter - 1) * 3;
            $prevEndDate = Carbon::createFromDate($year, $prevEndMonth, 1)->endOfMonth()->endOfDay();
            $prevIncome = (float)FinanceTransaction::query()
                ->where('is_balance', 1)
                ->where('type', 'income')
                ->whereBetween('created_at', [$yearStart, $prevEndDate])
                ->where(function ($sq) use ($fop) {
                    $sq->where('fop_id', $fop->id);
                    if ($fop->finance_bill_id) {
                        $sq->orWhere('finance_bill_id', $fop->finance_bill_id);
                    }
                })->sum('currency_amount');
            $previousTax = round($prevIncome * ($rate / 100), 2);
        }

        // Tax payable for this quarter
        $payableTax = max(0.0, $cumulativeTax - $previousTax);

        return [
            'fop_name' => $fop->name,
            'ipn' => $fop->ipn,
            'year' => $year,
            'quarter' => $quarter,
            'quarter_title' => match ($quarter) {
                1 => 'I квартал',
                2 => 'Півріччя (I - II кв.)',
                3 => '9 місяців (I - III кв.)',
                4 => 'Рік (I - IV кв.)',
            },
            'rate_percent' => $rate,
            'line_income' => $cumulativeIncome,        // Рядок 06 (для 3 групи)
            'line_accrued_tax' => $cumulativeTax,      // Рядок 11
            'line_previous_tax' => $previousTax,       // Рядок 12
            'line_payable_tax' => $payableTax,         // Рядок 14 (до сплати)
            'military_tax' => round($cumulativeIncome * 0.01, 2),
        ];
    }

    /**
     * Export Income Ledger as Excel-compatible CSV (with UTF-8 BOM).
     */
    public function exportLedgerCsv(Fop $fop, int $year, ?int $quarter = null): StreamedResponse
    {
        $records = $this->getIncomeLedger($fop, $year, $quarter);
        $periodTitle = $quarter ? "{$year}_Q{$quarter}" : "{$year}_full";
        $fileName = "Kniga_dohodiv_{$fop->ipn}_{$periodTitle}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records, $fop, $year, $quarter) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Title block
            fputcsv($handle, ['КНИГА ОБЛІКУ ДОХОДІВ (для платників єдиного податку)'], ';');
            fputcsv($handle, ['Платник:', $fop->name, 'РНОКПП / ІПН:', $fop->ipn], ';');
            fputcsv($handle, ['Період:', ($quarter ? "{$quarter} квартал " : '') . "{$year} року"], ';');
            fputcsv($handle, [], ';');

            // Table Header
            fputcsv($handle, [
                '№ з/п',
                'Дата запису',
                'Зміст операції / Контрагент',
                'Сума безготівкового доходу (грн)',
                'Сума готівкового доходу (грн)',
                'Всього отримано доходу (грн)',
                'Сума повернутих коштів (грн)',
                'Скоригована сума доходу (грн)',
            ], ';');

            $totalIncome = 0.0;

            foreach ($records as $row) {
                $totalIncome += $row['total_income'];
                fputcsv($handle, [
                    $row['index'],
                    $row['date'],
                    $row['description'],
                    number_format($row['cashless_amount'], 2, ',', ''),
                    number_format($row['cash_amount'], 2, ',', ''),
                    number_format($row['total_income'], 2, ',', ''),
                    number_format($row['returns_amount'], 2, ',', ''),
                    number_format($row['adjusted_income'], 2, ',', ''),
                ], ';');
            }

            // Total row
            fputcsv($handle, [], ';');
            fputcsv($handle, [
                'РАЗОМ',
                '',
                '',
                number_format($totalIncome, 2, ',', ''),
                '0,00',
                number_format($totalIncome, 2, ',', ''),
                '0,00',
                number_format($totalIncome, 2, ',', ''),
            ], ';');

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Create tax payment expense transaction.
     */
    public function createTaxPayment(Fop $fop, string $taxTitle, float $amount, int $quarter, int $year): FinanceTransaction
    {
        $billId = $fop->finance_bill_id;
        if (!$billId) {
            $bill = FinanceBill::where('user_id', $fop->user_id)->first();
            $billId = $bill?->id;
        }

        // Find or create 'Податки' expense category
        $category = FinanceTransactionCategory::firstOrCreate(
            ['name' => 'Податки', 'type' => 'expenses', 'user_id' => $fop->user_id],
            ['active' => 1]
        );

        // Find or create 'Tax payment' type
        $type = FinanceTransactionType::firstOrCreate(
            ['name' => 'expenses', 'type' => 'expenses']
        );

        $comment = "{$taxTitle} за {$quarter} кв. {$year} р. ({$fop->name})";

        $transaction = new FinanceTransaction();
        $transaction->amount = -$amount;
        $transaction->currency_amount = $amount;
        $transaction->absolute_currency_amount = $amount;
        $transaction->currency_value = 1.0;
        $transaction->type = 'expenses';
        $transaction->transaction_type_id = $type->id;
        $transaction->transaction_category_id = $category->id;
        $transaction->finance_bill_id = $billId;
        $transaction->fop_id = $fop->id;
        $transaction->user_id = $fop->user_id;
        $transaction->comment = $comment;
        $transaction->is_balance = 1;
        $transaction->created_at = Carbon::now();
        $transaction->save();

        return $transaction;
    }

    /**
     * Get quarter documents for the given year and optional quarter.
     */
    public function getQuarterDocuments(Fop $fop, int $year, ?int $quarter = null): Collection
    {
        $query = FopQuarterDocument::with(['attachment', 'user'])
            ->where('fop_id', $fop->id)
            ->where('year', $year)
            ->orderByDesc('quarter')
            ->orderByDesc('id');

        if ($quarter !== null && $quarter >= 1 && $quarter <= 4) {
            $query->where('quarter', $quarter);
        }

        return $query->get();
    }

    /**
     * Store an uploaded document for a specific quarter.
     */
    public function storeQuarterDocument(
        Fop $fop,
        int $year,
        int $quarter,
        string $documentType,
        ?string $title,
        ?string $notes,
        UploadedFile $file,
        ?int $userId = null
    ): FopQuarterDocument {
        $orchidFile = new OrchidFile($file, 'public', 'fop_documents');
        /** @var \Orchid\Attachment\Models\Attachment $attachment */
        $attachment = $orchidFile->load();

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize() ?: 0;
        $filePath = $attachment->path . $attachment->name . '.' . $attachment->extension;

        if (empty($title)) {
            $typeLabel = FopQuarterDocument::TYPES[$documentType]['label'] ?? 'Документ';
            $title = $typeLabel . ' (' . $quarter . ' кв. ' . $year . ' р.)';
        }

        return FopQuarterDocument::create([
            'fop_id'        => $fop->id,
            'user_id'       => $userId ?? Auth::id() ?? $fop->user_id,
            'year'          => $year,
            'quarter'       => $quarter,
            'document_type' => $documentType,
            'title'         => $title,
            'notes'         => $notes,
            'attachment_id' => $attachment->id,
            'file_path'     => $filePath,
            'original_name' => $originalName,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
        ]);
    }

    /**
     * Delete a quarter document and its underlying file.
     */
    public function deleteQuarterDocument(Fop $fop, int $documentId): bool
    {
        $doc = FopQuarterDocument::where('fop_id', $fop->id)->findOrFail($documentId);

        if ($doc->attachment) {
            $doc->attachment->delete();
        } elseif ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        return (bool)$doc->delete();
    }
}
