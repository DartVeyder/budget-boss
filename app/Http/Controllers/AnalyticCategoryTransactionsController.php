<?php

namespace App\Http\Controllers;

use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticCategoryTransactionsController extends Controller
{
    /**
     * Get transactions for a specific category within the selected date range and optional bill.
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $categoryId = $request->get('category_id');
        if (!$categoryId) {
            return response()->json(['error' => 'Category ID required'], 400);
        }

        $category = FinanceTransactionCategory::find($categoryId);
        $categoryName = $category ? $category->name : ('Категорія #' . $categoryId);

        $start = $request->get('start');
        $end = $request->get('end');
        $billId = $request->get('bill_id');
        $type = $request->get('type');

        // Handle date range
        if (empty($start) || empty($end)) {
            $createdAt = $request->get('created_at', []);
            $start = $createdAt['start'] ?? null;
            $end = $createdAt['end'] ?? null;
        }

        if (empty($start) || empty($end)) {
            $start = Carbon::now()->subYear()->startOfDay()->format('Y-m-d H:i:s');
            $end = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $start = Carbon::parse($start)->startOfDay()->format('Y-m-d H:i:s');
            $end = Carbon::parse($end)->endOfDay()->format('Y-m-d H:i:s');
        }

        $query = FinanceTransaction::where('user_id', $userId)
            ->where('is_balance', 1)
            ->where('transaction_category_id', $categoryId)
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($billId) && is_numeric($billId)) {
            $query->where('finance_bill_id', (int)$billId);
        }

        if (!empty($type) && in_array($type, ['income', 'expenses'])) {
            $query->where('type', $type);
        }

        $totalSum = (float) $query->sum('currency_amount');
        $count = $query->count();

        $transactions = $query->with(['bill.currency', 'customer', 'counterparty'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($t) {
                $isIncome = ($t->type === 'income');
                $amount = (float) $t->amount;
                $formattedAmount = ($isIncome ? '+' : '') . number_format($amount, 2, '.', ' ') . ' ' . ($t->currency_code ?? '₴');

                $description = $t->comment;
                if (empty($description)) {
                    $description = $t->customer?->name ?: ($t->counterparty?->name ?: ($t->source_name ?: 'Без опису'));
                }

                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => $amount,
                    'formatted_amount' => $formattedAmount,
                    'date' => Carbon::parse($t->created_at)->format('d.m.Y H:i'),
                    'bill_name' => $t->bill?->name ?? '—',
                    'currency' => $t->currency_code ?? '₴',
                    'description' => $description,
                    'edit_url' => route('platform.transactions.edit', $t->id),
                ];
            });

        return response()->json([
            'category' => [
                'id' => (int)$categoryId,
                'name' => $categoryName,
                'type' => $type,
            ],
            'period' => [
                'start' => Carbon::parse($start)->format('d.m.Y'),
                'end' => Carbon::parse($end)->format('d.m.Y'),
            ],
            'count' => $count,
            'total_amount' => $totalSum,
            'total_formatted' => number_format(abs($totalSum), 2, '.', ' ') . ' ₴',
            'transactions' => $transactions,
            'transactions_screen_url' => route('platform.transactions', [
                'filter' => ['transaction_category_id' => $categoryId],
            ]),
        ]);
    }

    /**
     * Get monthly expenses breakdown for a specific expense source (Monobank style).
     */
    public function getSourceMonthly(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sourceId = $request->get('source_id');
        if (!$sourceId) {
            return response()->json(['error' => 'Source ID required'], 400);
        }

        $start = $request->get('start');
        $end = $request->get('end');
        $billId = $request->get('bill_id');
        if (!empty($billId) && is_numeric($billId)) {
            $billId = (int)$billId;
        } else {
            $billId = null;
        }

        $service = new \App\Services\Finance\Transaction\TransactionsService();
        $monthlyData = $service->getMonthlyExpensesBySource((int)$sourceId, $start, $end, $billId);

        // Fetch recent transactions for this source
        $q = FinanceTransaction::where('user_id', $userId)
            ->where('is_balance', 1)
            ->where('type', 'expenses')
            ->where('transaction_source_id', $sourceId);

        if ($start && $end) {
            $q->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay()->format('Y-m-d H:i:s'),
                Carbon::parse($end)->endOfDay()->format('Y-m-d H:i:s')
            ]);
        }

        if ($billId) {
            $q->where('finance_bill_id', $billId);
        }

        $transactions = $q->with(['bill.currency', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'amount' => (float)$t->amount,
                    'formatted_amount' => '-' . number_format(abs((float)$t->amount), 2, '.', ' ') . ' ' . ($t->currency_code ?? '₴'),
                    'date' => Carbon::parse($t->created_at)->format('d.m.Y H:i'),
                    'bill_name' => $t->bill?->name ?? '—',
                    'category_name' => $t->category?->name ?? 'Витрати',
                    'description' => $t->comment ?: ($t->category?->name ?? 'Витрата'),
                    'edit_url' => route('platform.transactions.edit', $t->id),
                ];
            });

        $monthlyData['transactions'] = $transactions;

        return response()->json($monthlyData);
    }
}
