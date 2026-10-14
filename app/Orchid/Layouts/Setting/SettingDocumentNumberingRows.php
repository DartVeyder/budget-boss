<?php

namespace App\Orchid\Layouts\Setting;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class SettingDocumentNumberingRows extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Input::make('document_numbering.act_template')
                ->title('Шаблон номера Акту')
                ->placeholder('АКТ-{YYYY}/{MM}-{NUM}')
                ->help('Формат для автогенерації номерів актів. Доступні змінні: <code>{PREFIX}</code> (АКТ), <code>{YYYY}</code> (2026), <code>{YY}</code> (26), <code>{MM}</code> (10), <code>{DD}</code> (день), <code>{NUM}</code> (01, 02...), <code>{NUM:3}</code> (001...). Наприклад: <code>АКТ-{YYYY}/{MM}-{NUM}</code> або <code>{YYYY}{MM}{DD}-{NUM}</code>'),

            Input::make('document_numbering.invoice_template')
                ->title('Шаблон номера Рахунку')
                ->placeholder('РАХ-{YYYY}/{MM}-{NUM}')
                ->help('Формат для автогенерації номерів рахунків-фактур. Доступні змінні: <code>{PREFIX}</code> (РАХ), <code>{YYYY}</code>, <code>{YY}</code>, <code>{MM}</code>, <code>{DD}</code>, <code>{NUM}</code>, <code>{NUM:3}</code>. Наприклад: <code>РАХ-{YYYY}/{MM}-{NUM}</code> або <code>INV-{YYYY}-{NUM:3}</code>'),
        ];
    }
}
