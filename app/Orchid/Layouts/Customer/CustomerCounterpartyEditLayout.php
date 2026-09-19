<?php

namespace App\Orchid\Layouts\Customer;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
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

            Group::make([
                Input::make('counterparty.phone')
                    ->title('Номер телефону')
                    ->placeholder('+38 (067) 123-45-67')
                    ->help('Контактний номер телефону для актів та рахунків'),

                TextArea::make('counterparty.address')
                    ->title('Юридична / фактична адреса')
                    ->placeholder('Україна, 80106, Львівська обл...')
                    ->rows(2)
                    ->help('Адреса для реквізитів у первинних документах'),
            ]),

            Group::make([
                Select::make('counterparty.tax_group')
                    ->title('Група платника єдиного податку')
                    ->options([
                        '' => 'Без групи',
                        '1 група' => '1 група',
                        '2 група' => '2 група',
                        '3 група' => '3 група',
                        '4 група' => '4 група',
                    ])
                    ->empty('Не вказано')
                    ->help('Наприклад: 2 група, 3 група'),

                CheckBox::make('counterparty.is_single_tax')
                    ->title('Платник єдиного податку')
                    ->placeholder('Платник єдиного податку')
                    ->sendTrueOrFalse()
                    ->value(true),

                CheckBox::make('counterparty.is_vat_payer')
                    ->title('Платник ПДВ')
                    ->placeholder('Платник ПДВ (якщо не обрано — Не платник ПДВ)')
                    ->sendTrueOrFalse()
                    ->value(false),
            ]),

            Group::make([
                Input::make('counterparty.contract_number')
                    ->title('Номер договору')
                    ->placeholder('наприклад: МД18092026-01 або № 12/2026')
                    ->help('Автоматично підтягується в Акти та Рахунки на оплату для цього контрагента'),

                DateTimer::make('counterparty.contract_date')
                    ->title('Дата договору')
                    ->format('Y-m-d')
                    ->allowEmpty()
                    ->help('Дата укладання договору'),
            ]),

            Upload::make('counterparty.attachment')
                ->title('Документ договору (файл)')
                ->maxFiles(1)
                ->acceptedFiles('.pdf,.docx,.doc,.jpg,.jpeg,.png')
                ->help('Завантажте файл договору (PDF, DOCX). Якщо номер або дата не вказані, вони автоматично підтягнуться з документа'),

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
