<?php

namespace App\Orchid\Filters\Finance\Transaction;

use App\Models\FinanceTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class TypeTransactionFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return __('Type');
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return [
            'transaction_type_id'
        ];
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
        return $builder->where('transaction_type_id', $this->request->get('transaction_type_id'));;
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        return [
            Select::make('transaction_type_id')
                ->fromModel(FinanceTransactionType::where('active', '=', '1'), 'name')
                ->empty()
                ->value($this->request->get('transaction_type_id'))
                ->applyScope('active')
                ->title('Type'),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        return $this->name().': '.FinanceTransactionType::where('id', $this->request->get('transaction_type_id'))->first()->name;
    }
}
