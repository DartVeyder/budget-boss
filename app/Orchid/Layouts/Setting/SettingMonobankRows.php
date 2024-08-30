<?php

namespace App\Orchid\Layouts\Setting;

use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class SettingMonobankRows extends Rows
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
            Input::make('monobank.api_key')
                ->title('Api key'),
            Select::make('monobank.active')
                ->options([
                    1  => 'Включити',
                    2  => 'Виключити',
                ])
                ->title('Status'),

            Input::make('monobank.user_id')
                ->value(Auth::user()->id)
                ->hidden(),
            ];

    }
}
