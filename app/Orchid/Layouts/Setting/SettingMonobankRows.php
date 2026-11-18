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
                ->title('API-ключ')
                ->type('password')
                ->help('Отримати персональний токен можна на <a href="https://api.monobank.ua/" target="_blank" rel="noopener">api.monobank.ua</a> (скануйте QR-код у додатку Monobank). Підтримуються як особисті картки, так і рахунки ФОП.'),
            Select::make('monobank.active')
                ->options([
                    1  => 'Включити',
                    2  => 'Виключити',
                ])
                ->title('Статус синхронізації'),

            \Orchid\Screen\Fields\TextArea::make('monobank.accounts_summary')
                ->title('Підключені рахунки Monobank')
                ->disabled()
                ->rows(5)
                ->help('Автоматично визначені особисті та ФОП рахунки (оновлюється при збереженні).'),

            Input::make('monobank.user_id')
                ->value(Auth::user()->id)
                ->hidden(),
        ];
    }
}
