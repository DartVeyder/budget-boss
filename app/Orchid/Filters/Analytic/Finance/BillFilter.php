<?php

namespace App\Orchid\Filters\Analytic\Finance;

use App\Models\FinanceBill;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class BillFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Рахунок';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return ['bill_id'];
    }

    /**
     * Apply to a given Eloquent query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        $billId = $this->request->get('bill_id');
        if (!empty($billId)) {
            return $builder->where('finance_bill_id', $billId);
        }

        return $builder;
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        $sharedIds = auth()->user()?->getSharedUserIds() ?? [auth()->id()];
        $bills = FinanceBill::whereIn('user_id', $sharedIds)->with('owner')->orderBy('name')->get();

        $options = [];
        $isSharing = count($sharedIds) > 1;
        foreach ($bills as $bill) {
            $label = $bill->name;
            if ($isSharing && $bill->owner) {
                $ownerSuffix = $bill->user_id === auth()->id() ? ' (Я)' : " ({$bill->owner->name})";
                $label .= $ownerSuffix;
            }
            $options[$bill->id] = $label;
        }

        return [
            Select::make('bill_id')
                ->options($options)
                ->empty('Всі рахунки')
                ->value($this->request->get('bill_id'))
                ->title('Рахунок'),
        ];
    }
}
