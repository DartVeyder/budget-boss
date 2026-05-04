<?php

namespace App\Orchid\Screens\Setting;

use App\Models\UserSetting;
use App\Orchid\Layouts\Setting\SettingMonobankRows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SettingScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $setting = Auth::user()->setting;
        return [
            'monobank' => [
                'api_key' => $setting->monobank_api_key ?? '',
                'active'  => $setting->monobank_active ? 1 : 2,
            ]
        ];
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
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
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
            Layout::accordion(
                [
                'Monobank' => [
                    SettingMonobankRows::class
                ]
            ]),

        ];
    }

    /**
     * @param Request $request
     */
    public function save(Request $request): void
    {
        $data = $request->get('monobank');

        $setting = UserSetting::firstOrCreate(
            ['user_id' => Auth::user()->id]
        );

        $setting->monobank_api_key = $data['api_key'] ?? null;
        $setting->monobank_active = isset($data['active']) && $data['active'] == 1;
        $setting->save();

        Toast::info(__('Settings saved successfully.'));
    }
}
