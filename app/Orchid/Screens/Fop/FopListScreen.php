<?php

namespace App\Orchid\Screens\Fop;

use App\Models\Fop;
use App\Orchid\Layouts\Fop\FopListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class FopListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'fops' => Fop::filters()->defaultSort('id', 'desc')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Керування ФОП';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Керування інформацією про ФОП (Фізична особа-підприємець).';
    }


    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Додати')
                ->icon('bs.plus-circle')
                ->route('platform.fops.create'),
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
            FopListLayout::class,
        ];
    }
}
