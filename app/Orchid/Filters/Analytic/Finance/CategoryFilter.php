<?php

namespace App\Orchid\Filters\Analytic\Finance;

use App\Models\FinanceTransactionCategory;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class CategoryFilter extends Filter
{
    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Категорія';
    }

    /**
     * The array of matched parameters.
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return ['category_id', 'transaction_category_id'];
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
        $categoryId = $this->request->get('category_id') ?? $this->request->get('transaction_category_id');
        if (!empty($categoryId)) {
            return $builder->where('transaction_category_id', (int)$categoryId);
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
            Select::make('category_id')
                ->fromModel(FinanceTransactionCategory::query(), 'name')
                ->empty('Всі категорії')
                ->value($this->request->get('category_id') ?? $this->request->get('transaction_category_id'))
                ->title('Категорія')
        ];
    }
}
