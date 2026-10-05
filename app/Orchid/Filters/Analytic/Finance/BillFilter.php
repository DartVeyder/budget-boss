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
        return [
            Select::make('bill_id')
                ->fromModel(FinanceBill::where('user_id', auth()->id()), 'name')
                ->empty('Всі рахунки')
                ->title('Рахунок'),
        ];
    }
}
