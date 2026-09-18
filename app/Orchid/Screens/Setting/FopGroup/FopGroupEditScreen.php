<?php

namespace App\Orchid\Screens\Setting\FopGroup;

use App\Models\FopGroup;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class FopGroupEditScreen extends Screen
{
    /**
     * @var FopGroup
     */
    public $fopGroup;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FopGroup $fopGroup): iterable
    {
        $fopGroup->load('taxRates');

        return [
            'fopGroup' => $fopGroup,
            'taxRates' => $fopGroup->taxRates->pluck('id')->toArray(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->fopGroup->exists ? 'Редагувати групу ФОП' : 'Створити нову групу ФОП';
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
                ->canSee($this->fopGroup->exists),
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
                Input::make('fopGroup.name')
                    ->title('Назва групи')
                    ->placeholder('Наприклад: 3 група (5%)')
                    ->required(),

                Relation::make('taxRates')
                    ->title('Податки')
                    ->placeholder('Виберіть податки для цієї групи')
                    ->fromModel(TaxRate::class, 'name')
                    ->multiple(),

                Input::make('fopGroup.annual_limit')
                    ->type('number')
                    ->step('0.01')
                    ->title('Річний ліміт доходу (грн)')
                    ->placeholder('Наприклад: 8285700')
                    ->help('Законодавчий ліміт обсягу доходу для цієї групи за календарний рік.'),

                Input::make('fopGroup.monthly_esv')
                    ->type('number')
                    ->step('0.01')
                    ->title('Базовий щомісячний ЄСВ (грн)')
                    ->placeholder('Наприклад: 1760.00')
                    ->help('Мінімальний страховий внесок на місяць (22% від мінімальної заробітної плати).'),
            ])
        ];
    }

    /**
     * @param FopGroup $fopGroup
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(FopGroup $fopGroup, Request $request)
    {
        $fopGroup->fill($request->get('fopGroup'))->save();

        $fopGroup->taxRates()->sync($request->input('taxRates', []));

        Toast::info('Групу ФОП успішно збережено.');

        return redirect()->route('platform.setting.fop-groups');
    }

    /**
     * @param FopGroup $fopGroup
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(FopGroup $fopGroup)
    {
        $fopGroup->delete();

        Toast::info('Групу ФОП успішно видалено.');

        return redirect()->route('platform.setting.fop-groups');
    }
}
