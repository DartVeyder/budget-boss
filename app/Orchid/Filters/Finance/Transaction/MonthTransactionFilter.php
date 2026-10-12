<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Finance\Transaction;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class MonthTransactionFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public function name(): string
    {
        return 'Місяць';
    }

    /**
     * The array of matched parameters.
     */
    public function parameters(): ?array
    {
        return [
            'transaction_month',
        ];
    }

    /**
     * Apply to a given Eloquent query builder.
     */
    public function run(Builder $builder): Builder
    {
        $month = $this->request->get('transaction_month');
        if (! $month) {
            return $builder;
        }

        try {
            $date = Carbon::createFromFormat('Y-m', $month);
            $start = $date->copy()->startOfMonth()->startOfDay();
            $end = $date->copy()->endOfMonth()->endOfDay();

            return $builder->where(function (Builder $query) use ($start, $end) {
                $query->whereBetween('accrual_date', [$start, $end])
                    ->orWhere(function (Builder $sub) use ($start, $end) {
                        $sub->whereNull('accrual_date')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            });
        } catch (\Throwable) {
            return $builder;
        }
    }

    /**
     * Get the display fields.
     */
    public function display(): iterable
    {
        $options = [];
        $cursor = Carbon::now()->addMonth()->startOfMonth();

        for ($i = 0; $i < 24; $i++) {
            $key = $cursor->format('Y-m');
            $label = mb_convert_case($cursor->locale('uk')->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8');
            $options[$key] = $label;
            $cursor->subMonth();
        }

        return [
            Select::make('transaction_month')
                ->options($options)
                ->empty('Всі місяці')
                ->value($this->request->get('transaction_month'))
                ->title('Швидкий вибір місяця'),
        ];
    }

    /**
     * Value to be displayed in the active filter badge.
     */
    public function value(): string
    {
        $month = $this->request->get('transaction_month');
        if (! $month) {
            return '';
        }

        try {
            $label = mb_convert_case(
                Carbon::createFromFormat('Y-m', $month)->locale('uk')->translatedFormat('F Y'),
                MB_CASE_TITLE,
                'UTF-8'
            );
            return $this->name() . ': ' . $label;
        } catch (\Throwable) {
            return $this->name() . ': ' . $month;
        }
    }
}
