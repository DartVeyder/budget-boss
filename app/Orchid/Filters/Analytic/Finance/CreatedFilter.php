<?php

namespace App\Orchid\Filters\Analytic\Finance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateRange;

class CreatedFilter extends Filter
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
        return ['created_at'];
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
        $dates = $this->request->get('created_at');
        $start = $dates['start'] ?? null;
        $end   = $dates['end']   ?? null;

        if ($start && $end) {
            $startFormatted = strlen($start) === 10 ? Carbon::parse($start)->startOfDay()->toDateTimeString() : $start;
            $endFormatted   = strlen($end) === 10 ? Carbon::parse($end)->endOfDay()->toDateTimeString() : $end;
            return $builder->whereBetween('created_at', [$startFormatted, $endFormatted]);
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
            DateRange::make('created_at')
                ->title('Дата'),
        ];
    }
}
