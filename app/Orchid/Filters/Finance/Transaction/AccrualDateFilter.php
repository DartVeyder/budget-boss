<?php

namespace App\Orchid\Filters\Finance\Transaction;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateRange;

class AccrualDateFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Дата';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return ['accrual_date'];
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
       return $builder->whereBetween('accrual_date', [$this->request->get('accrual_date')['start'], $this->request->get('accrual_date')['end']]);
    }

    /**
     * Get the display fields.
     *
     * @return Field[]
     */
    public function display(): iterable
    {
        return [
            DateRange::make('accrual_date')
            ->title('Date'),
        ];
    }
}
