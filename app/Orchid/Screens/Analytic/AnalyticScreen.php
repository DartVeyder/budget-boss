<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Analytic;

use Illuminate\Http\Request;
use Orchid\Screen\Screen;

class AnalyticScreen extends Screen
{
    /**
     * Redirect to the full Finance analytics & recommendations screen.
     */
    public function handle(Request $request, ...$arguments)
    {
        return redirect()->route('platform.analytic.finance');
    }

    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Аналітика';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [];
    }
}

