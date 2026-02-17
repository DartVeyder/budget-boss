<?php

namespace App\Orchid\Screens\Fop;

use App\Models\FinanceBill;
use App\Models\Fop;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Color;

class FopEditScreen extends Screen
{
    /**
     * @var Fop
     */
    public $fop;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @param Fop $fop
     *
     * @return array
     */
    public function query(Fop $fop): iterable
    {
        return [
            'fop' => $fop,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->fop->exists ? 'Редагування ФОП' : 'Створення ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Деталі, такі як ІПН, Рахунок та Адреса.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Створити')
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('createOrUpdate')
                ->canSee(!$this->fop->exists),

            Button::make('Оновити')
                ->icon('bs.check-circle')
                ->type(Color::DEFAULT)
                ->method('createOrUpdate')
                ->canSee($this->fop->exists),

            Button::make('Видалити')
                ->icon('bs.trash')
                ->type(Color::DANGER)
                ->method('remove')
                ->canSee($this->fop->exists),
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
//                Relation::make('fop.user_id')
//                    ->title('Користувач')
//                    ->fromModel(User::class, 'name')
//                    ->required(),

                Input::make('fop.name')
                    ->title('Назва')
                    ->placeholder('Введіть назву ФОП')
                    ->required(),

                Input::make('fop.ipn')
                    ->title('ІПН')
                    ->placeholder('Введіть ІПН')
                    ->required(),

                Input::make('fop.ewn')
                    ->title('ЄДРПОУ')
                    ->placeholder('Введіть ЄДРПОУ (необов\'язково)'),

                Input::make('fop.address')
                    ->title('Адреса')
                    ->placeholder('Введіть адресу'),

                Relation::make('fop.finance_bill_id')
                    ->title('Рахунок / Картка')
                    ->fromModel(FinanceBill::class, 'name')
                    ->required(),



                Input::make('fop.director')
                    ->title('Директор')
                    ->placeholder('Введіть ім\'я директора (необов\'язково)'),

                CheckBox::make('fop.is_active')
                    ->title('Активний')
                    ->sendTrueOrFalse(),
            ])
        ];
    }

    /**
     * @param Fop    $fop
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(Fop $fop, Request $request)
    {
        $data = $request->get('fop');
        if (!$fop->exists) {
            $data['user_id'] = auth()->id();
        }
        $fop->fill($data)->save();

        Alert::info('Ви успішно створили/оновили ФОП.');

        return redirect()->route('platform.fops');
    }

    /**
     * @param Fop $fop
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(Fop $fop)
    {
        $fop->delete();

        Alert::info('Ви успішно видалили ФОП.');

        return redirect()->route('platform.fops');
    }
}
