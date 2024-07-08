<?php

namespace App\Orchid\Layouts\Customer;

use App\Models\Customer;
use App\Models\FinanceCurrency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\In;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class CustomerSaveRows extends Rows
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
            Input::make('name')
                ->title('Name')
                ->required()
        ];
    }
}
