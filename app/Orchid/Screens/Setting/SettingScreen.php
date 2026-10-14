<?php

namespace App\Orchid\Screens\Setting;

use App\Models\UserSetting;
use App\Orchid\Layouts\Setting\SettingDocumentNumberingRows;
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
        $setting = Auth::user()?->setting;
        return [
            'monobank' => [
                'api_key' => $setting?->monobank_api_key ?? '',
                'active'  => $setting?->monobank_active ? 1 : 2,
            ],
            'document_numbering' => [
                'act_template'     => $setting?->act_number_template ?: 'АКТ-{YYYY}/{MM}-{NUM}',
                'invoice_template' => $setting?->invoice_number_template ?: 'РАХ-{YYYY}/{MM}-{NUM}',
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Налаштування';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.setting',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            \Orchid\Screen\Actions\Link::make('Спільний бюджет')
                ->icon('bs.people-fill')
                ->route('platform.setting.budget-shares'),

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
            Layout::accordion([
                'Нумерація документів (Акти та Рахунки)' => [
                    SettingDocumentNumberingRows::class,
                ],
                'Monobank' => [
                    SettingMonobankRows::class,
                ],
            ]),
        ];
    }

    /**
     * @param Request $request
     */
    public function save(Request $request): void
    {
        $monoData = $request->get('monobank');
        $docData = $request->get('document_numbering');

        $setting = UserSetting::firstOrCreate(
            ['user_id' => Auth::user()->id]
        );

        if ($monoData) {
            $setting->monobank_api_key = $monoData['api_key'] ?? null;
            $setting->monobank_active = isset($monoData['active']) && $monoData['active'] == 1;
        }

        if ($docData) {
            $setting->act_number_template = !empty($docData['act_template']) ? trim($docData['act_template']) : 'АКТ-{YYYY}/{MM}-{NUM}';
            $setting->invoice_number_template = !empty($docData['invoice_template']) ? trim($docData['invoice_template']) : 'РАХ-{YYYY}/{MM}-{NUM}';
        }

        $setting->save();

        Toast::info(__('Settings saved successfully.'));
    }
}
