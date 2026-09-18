<?php

namespace App\Orchid\Layouts\Customer;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class CustomerCounterpartyEditLayout extends Rows
{
    /**
     * The array of fields to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Input::make('counterparty.id')
                ->type('hidden'),

            Input::make('counterparty.name')
                ->title('ПІБ ФОПа або назва компанії-платника')
                ->placeholder('ФОП Іваненко Іван Іванович')
                ->help('Офіційна назва платника, від якого надходитимуть кошти')
                ->required(),

            Group::make([
                Input::make('counterparty.ipn')
                    ->title('ІПН / РНОКПП')
                    ->placeholder('10 цифр для ФОП або 8 для юрособи')
                    ->help('Використовується для податкової звітності та книги доходів'),

                Input::make('counterparty.bank_name')
                    ->title('Назва банку')
                    ->placeholder('АТ КБ "ПриватБанк", monobank тощо'),
            ]),

            Input::make('counterparty.iban')
                ->title('IBAN платника')
                ->placeholder('UA000000000000000000000000000')
                ->help('Розрахунковий рахунок у форматі IBAN (необов\'язково)'),

            TextArea::make('counterparty.notes')
                ->title('Примітки')
                ->placeholder('Будь-які примітки або коментарі')
                ->rows(3),

            CheckBox::make('counterparty.is_active')
                ->title('Активний')
                ->placeholder('Доступний для вибору в транзакціях')
                ->sendTrueOrFalse()
                ->value(true),
        ];
    }
}
