<?php

namespace App\Custom\Metrics;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Orchid\Metrics\GroupCollection;
use Orchid\Metrics\TimeCollection;

trait Chartable
{
    /**
     * Counts the values for model at the range and previous range.
     */
    public function scopeCountForGroup(Builder $builder, string $groupColumn): GroupCollection
    {
        $group = $builder->select("$groupColumn as label", DB::raw('count(*) as value'))
            ->groupBy($groupColumn)
            ->orderBy('value', 'desc')
            ->get()
            ->map(fn (Model $model) => $model->forceFill([
                'label' => (string) $model->label,
                'value' => (int) $model->value,
            ]));

        return new GroupCollection($group);
    }

    /**
     * @param mixed|null $startDate
     * @param mixed|null $stopDate
     * @param string     $dateColumn
     */
    private function groupByDays(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        $dateColumn = $dateColumn ?? $builder->getModel()->getCreatedAtColumn();

        $startDate = empty($startDate)
            ? Carbon::now()->subMonth()
            : Carbon::parse($startDate);

        $stopDate = empty($stopDate)
            ? Carbon::now()
            : Carbon::parse($stopDate);

        $query = $builder
            ->select(DB::raw("$value as value, DATE($dateColumn) as label"))
            ->whereBetween($dateColumn, [$startDate, $stopDate])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $days = $startDate->diffInDays($stopDate) + 1;

        return TimeCollection::times($days, function () use ($startDate, $query) {
            $found = $query->firstWhere(
                'label',
                $startDate->startOfDay()->toDateString()
            );

            $result = [
                'value'   => ($found ? $found->value : 0),
                'label'   => $startDate->toDateString(),
            ];

            $startDate->addDay();

            return $result;
        });
    }
    private function groupByMonth(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        $dateColumn = $dateColumn ?? $builder->getModel()->getCreatedAtColumn();

        $startDate = empty($startDate)
            ? Carbon::now()->subYear()
            : Carbon::parse($startDate)->startOfMonth();

        $stopDate = empty($stopDate)
            ? Carbon::now()
            : Carbon::parse($stopDate)->endOfMonth();

        $query = $builder
            ->select(DB::raw("ABS($value) as value, DATE_FORMAT($dateColumn, '%Y-%m') as label"))
            ->whereBetween($dateColumn, [$startDate, $stopDate])
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $months = $startDate->diffInMonths($stopDate) + 1;

        return TimeCollection::times($months, function () use ($startDate, $query) {
            $found = $query->firstWhere(
                'label',
                $startDate->format('Y-m')
            );

            $result = [
                'value'   => ($found ? $found->value : 0),
                'label'   => $startDate->format('Y-m'),
            ];

            $startDate->addMonth();

            return $result;
        });
    }


    /**
     * Get total models grouped by `created_at` day.
     *
     * @param string|DateTimeInterface|null $startDate
     * @param string|DateTimeInterface|null $stopDate
     * @param string                        $dateColumn
     */
    public function scopeCountByDays(Builder $builder, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        return $this->groupByDays($builder, 'count(*)', $startDate, $stopDate, $dateColumn);
    }

    /**
     * Get average models grouped by `created_at` day.
     *
     * @param string|DateTimeInterface|null $startDate
     * @param string|DateTimeInterface|null $stopDate
     */
    public function scopeAverageByDays(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        return $this->groupByDays($builder, "avg($value)", $startDate, $stopDate, $dateColumn);
    }

    /**
     * Get sum models grouped by `created_at` day.
     *
     * @param string|DateTimeInterface|null $startDate
     * @param string|DateTimeInterface|null $stopDate
     */
    public function scopeSumByDays(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        return $this->groupByDays($builder, "sum($value)", $startDate, $stopDate, $dateColumn);
    }

    public function scopeSumByMonths(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        return $this->groupByMonth($builder, "sum($value)", $startDate, $stopDate, $dateColumn);
    }

    /**
     * Get sum models grouped by `created_at` day.
     *
     * @param string|DateTimeInterface|null $startDate
     * @param string|DateTimeInterface|null $stopDate
     */
    public function scopeMaxByDays(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        return $this->groupByDays($builder, "max($value)", $startDate, $stopDate, $dateColumn);
    }

    /**
     * Get min models grouped by `created_at` day.
     *
     * @param string|DateTimeInterface|null $startDate
     * @param string|DateTimeInterface|null $stopDate
     */
    public function scopeMinByDays(Builder $builder, string $value, $startDate = null, $stopDate = null, ?string $dateColumn = null): TimeCollection
    {
        return $this->groupByDays($builder, "min($value)", $startDate, $stopDate, $dateColumn);
    }

    /**
     * @deprecated usage maxByDays or minByDays
     *
     * Get values models grouped by `created_at` day.
     *
     * @param string|DateTimeInterface|null $startDate
     * @param string|DateTimeInterface|null $stopDate
     */
    public function scopeValuesByDays(Builder $builder, string $value, $startDate = null, $stopDate = null, string $dateColumn = 'created_at'): TimeCollection
    {
        return $this->groupByDays($builder, $value, $startDate, $stopDate, $dateColumn);
    }
}
