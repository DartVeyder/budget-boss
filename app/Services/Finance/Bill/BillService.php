<?php

namespace App\Services\Finance\Bill;

use App\Models\FinanceBill;
use App\Services\Currency\Currency;
use App\Services\Finance\Crypto\Binance\BinanceService;
use Illuminate\Support\Facades\Auth;

trait BillService
{
    private function generateMetricsToBill(?int $filterUserId = null): array{
        $data = [];
        $allSharedIds = Auth::user()?->getSharedUserIds() ?? [Auth::user()->id];
        $isShared = count($allSharedIds) > 1;

        $targetUserIds = ($filterUserId && in_array($filterUserId, $allSharedIds, true))
            ? [$filterUserId]
            : $allSharedIds;

        $bills = FinanceBill::with(['currency', 'owner'])->whereIn('user_id', $targetUserIds)->get();
        foreach ($bills as $bill){
            $balance = (float)$bill->transactions->sum('amount');
            $data[$bill->id] = [
                'id' => $bill->id,
                'user_id' => $bill->user_id,
                'name' => $bill->name,
                'iban' => $bill->iban,
                'bank_name' => $bill->bank_name,
                'user_name' => $isShared ? ($bill->owner?->name ?? null) : null,
                'is_mine' => $bill->user_id === Auth::id(),
                'raw_balance' => $balance,
                'currency_symbol' => $bill->currency?->symbol ?? '₴',
                'total' => ["value" => Currency::getFormatMoney($balance, $bill->currency?->symbol ?? '₴')]
            ];
        }

        if (!$filterUserId || $filterUserId === Auth::id()) {
            $data['binance'] = BinanceService::getBill();
        }

        return $data;
    }

    private function generateMetricsLayoutToBill($max = 0, ?int $filterUserId = null): array{
        $data = [];
        $allSharedIds = Auth::user()?->getSharedUserIds() ?? [Auth::user()->id];
        $targetUserIds = ($filterUserId && in_array($filterUserId, $allSharedIds, true))
            ? [$filterUserId]
            : $allSharedIds;

        $bills = FinanceBill::whereIn('user_id', $targetUserIds)->with('owner')->get();
        $isMultiUser = count($targetUserIds) > 1;

        foreach ($bills as $key => $bill){
            $label = $bill->name;
            if ($isMultiUser && $bill->owner) {
                $ownerLabel = $bill->user_id === Auth::id() ? ' (Ви)' : " ({$bill->owner->name})";
                $label .= $ownerLabel;
            }
            $data[$label] = "metrics.bills.". $bill->id.".total";
            if ($max == $key && $max != 0) {
                break;
            }
        }
        return $data;
    }

    /**
     * Get bills grouped by member for separated/combined views.
     */
    public function getGroupedBillsData(?int $filterUserId = null): array
    {
        $user = Auth::user();
        $sharedUsers = $user->getSharedUsers();
        $isShared = $sharedUsers->count() > 1;

        $bills = FinanceBill::with(['currency', 'owner'])
            ->whereIn('user_id', $user->getSharedUserIds())
            ->get();

        $groups = [];
        $grandTotal = 0;

        foreach ($sharedUsers as $member) {
            if ($filterUserId && $member->id !== $filterUserId) {
                continue;
            }

            $memberBills = $bills->where('user_id', $member->id);
            $memberTotal = 0;
            $memberBillItems = [];

            foreach ($memberBills as $b) {
                $balance = (float)$b->transactions->sum('amount');
                $memberTotal += $balance;
                $grandTotal += $balance;

                $memberBillItems[$b->id] = [
                    'id' => $b->id,
                    'user_id' => $b->user_id,
                    'name' => $b->name,
                    'iban' => $b->iban,
                    'bank_name' => $b->bank_name,
                    'user_name' => $member->name,
                    'is_mine' => $member->id === $user->id,
                    'raw_balance' => $balance,
                    'currency_symbol' => $b->currency?->symbol ?? '₴',
                    'total' => ['value' => Currency::getFormatMoney($balance, $b->currency?->symbol ?? '₴')],
                ];
            }

            $groups[$member->id] = [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'is_self' => $member->id === $user->id,
                'bills_count' => $memberBills->count(),
                'total_formatted' => Currency::getFormatMoney($memberTotal, '₴'),
                'bills' => $memberBillItems,
            ];
        }

        return [
            'is_shared' => $isShared,
            'filter_user_id' => $filterUserId,
            'shared_users' => $sharedUsers,
            'groups' => $groups,
            'grand_total_formatted' => Currency::getFormatMoney($grandTotal, '₴'),
        ];
    }


}
