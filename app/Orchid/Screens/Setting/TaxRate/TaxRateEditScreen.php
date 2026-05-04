<?php

namespace App\Orchid\Screens\Setting\TaxRate;

use App\Models\TaxRate;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TaxRateEditScreen extends Screen
{
    /**
     * @var TaxRate
     */
    public $taxRate;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(TaxRate $taxRate): iterable
    {
        return [
            'taxRate' => $taxRate
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->taxRate->exists ? 'Редагувати ставку' : 'Створити нову ставку';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Зберегти')
                ->icon('bs.check-circle')
                ->method('createOrUpdate'),

            Button::make('Видалити')
                ->icon('bs.trash')
                ->method('remove')
                ->canSee($this->taxRate->exists),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('taxRate.name')
                    ->title('Назва')
                    ->placeholder('Наприклад: ПДВ 20%')
                    ->required(),

                Input::make('taxRate.value')
                    ->title('Значення (%)')
                    ->type('number')
                    ->step(0.01)
                    ->placeholder('Наприклад: 20')
                    ->required(),
            ])
        ];
    }

    /**
     * @param TaxRate $taxRate
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(TaxRate $taxRate, Request $request)
    {
        $taxRate->fill($request->get('taxRate'))->save();

        Toast::info('Ставку успішно збережено.');

        return redirect()->route('platform.setting.tax-rates');
    }

    /**
     * @param TaxRate $taxRate
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(TaxRate $taxRate)
    {
        $taxRate->delete();

        Toast::info('Ставку успішно видалено.');

        return redirect()->route('platform.setting.tax-rates');
    }
}
