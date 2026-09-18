<?php

namespace App\Orchid\Screens\Finance\Act;

use App\Models\Act;
use App\Orchid\Layouts\Finance\Act\ActListLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class ActListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'acts' => Act::with(['customer', 'counterparty', 'fop'])
                ->filters()
                ->where('user_id', Auth::id())
                ->defaultSort('act_date', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Акти наданих послуг';
    }

    /**
     * The screen's description.
     */
    public function description(): ?string
    {
        return 'Облік та друк первинних документів (актів виконаних робіт/послуг) для ФОП';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Створити Акт / Рахунок')
                ->icon('bs.plus-circle')
                ->route('platform.acts.create'),
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
            ActListLayout::class,
        ];
    }

    /**
     * Remove Act.
     */
    public function remove(Request $request)
    {
        $act = Act::where('user_id', Auth::id())->findOrFail($request->get('id'));
        $act->delete();

        Toast::info('Акт успішно видалено.');

        return redirect()->route('platform.acts');
    }
}
