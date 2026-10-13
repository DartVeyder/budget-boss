<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Finance\Transaction;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class UserTransactionFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public function name(): string
    {
        return 'Користувач';
    }

    /**
     * The array of matched parameters.
     */
    public function parameters(): ?array
    {
        return [
            'transaction_user_id',
        ];
    }

    /**
     * Apply to a given Eloquent query builder.
     */
    public function run(Builder $builder): Builder
    {
        $userId = $this->request->get('transaction_user_id');
        if (!empty($userId)) {
            return $builder->where('finance_transactions.user_id', (int)$userId);
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
        $sharedIds = Auth::user()?->getSharedUserIds() ?? [Auth::id()];

        // Hide filter if only single user
        if (count($sharedIds) <= 1) {
            return [];
        }

        $options = [];
        $users = User::whereIn('id', $sharedIds)->get();
        foreach ($users as $u) {
            $label = $u->id === Auth::id() ? "{$u->name} (Я)" : $u->name;
            $options[$u->id] = $label;
        }

        return [
            Select::make('transaction_user_id')
                ->options($options)
                ->empty('Всі учасники бюджету')
                ->value($this->request->get('transaction_user_id'))
                ->title('Учасник бюджету'),
        ];
    }

    /**
     * Value to be displayed in the filter badge.
     */
    public function value(): string
    {
        $userId = $this->request->get('transaction_user_id');
        if (!$userId) {
            return '';
        }

        $user = User::find($userId);
        $name = $user ? $user->name : '#' . $userId;
        return $this->name() . ': ' . $name;
    }
}
