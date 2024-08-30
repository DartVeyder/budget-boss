<?php

namespace App\Orchid\Screens\Setting;

use App\Orchid\Layouts\Setting\SettingMonobankRows;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class SettingScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Setting';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::accordion(
                [
                'Monobank' => [
                    SettingMonobankRows::class
                ]
            ]),

        ];
    }
}
