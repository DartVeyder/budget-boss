<?php

namespace App\Orchid\Layouts\Finance\Transaction\Category;

use App\Models\FinanceTransactionMcc;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

class CategoryMccRows extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Input::make('mcc.name')
                ->title('Name'),
            Input::make('mcc.code')
                ->title('Code')
                ->required(),
        ];
    }
}
