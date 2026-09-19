<?php

namespace App\Orchid\Screens\Finance\Act;

use App\Models\Act;
use App\Models\FinanceTransaction;
use App\Orchid\Layouts\Finance\Act\ActListLayout;
use App\Services\Finance\Act\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
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
            'acts' => Act::with(['customer', 'counterparty', 'fop', 'attachment', 'transaction.attachment'])
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
        $unimportedCount = FinanceTransaction::where('user_id', Auth::id())
            ->where('type', 'income')
            ->whereDoesntHave('act')
            ->has('attachment')
            ->count();

        return [
            Button::make("Імпортувати акти з транзакцій ({$unimportedCount})")
                ->icon('bs.box-arrow-in-down')
                ->type(Color::SUCCESS)
                ->method('importFromTransactions')
                ->confirm("Створити Акти та Рахунки для {$unimportedCount} доходів з прикріпленими файлами?")
                ->canSee($unimportedCount > 0),

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

            Layout::modal('asyncUploadActModal', Layout::rows([
                Input::make('act.id')->type('hidden'),

                Upload::make('act.attachment')
                    ->title('Підписаний акт (скан / PDF)')
                    ->acceptedFiles('.pdf,.docx,.doc,.jpg,.jpeg,.png')
                    ->help('Завантажте підписану скан-копію або PDF документ'),

                Select::make('act.status')
                    ->title('Статус акту')
                    ->options([
                        'draft' => 'Чернетка',
                        'sent' => 'Надіслано клієнту',
                        'signed' => 'Підписано обома сторонами',
                        'cancelled' => 'Скасовано',
                    ])
                    ->help('При завантаженні скану статус можна встановити як "Підписано"'),
            ]))
            ->async('asyncGetAct')
            ->applyButton('Зберегти')
            ->title('Підписаний акт'),
        ];
    }

    /**
     * Batch import Acts and Invoices from transactions that have attachments.
     */
    public function importFromTransactions(DocumentGenerationService $docService)
    {
        $result = $docService->importFromTransactions(Auth::id());
        $count = $result['imported'] ?? 0;

        Toast::success("Успішно створено {$count} актів та рахунків з файлами із транзакцій!");

        return redirect()->route('platform.acts');
    }

    /**
     * Async get act data for upload modal.
     */
    public function asyncGetAct(Request $request): iterable
    {
        $actId = $request->input('act');
        $act = Act::where('user_id', Auth::id())->with(['attachment', 'transaction.attachment'])->findOrFail($actId);

        if ($act->attachment->isEmpty() && $act->transaction && $act->transaction->attachment->isNotEmpty()) {
            $txActFiles = $act->transaction->attachment->filter(function ($att) {
                $n = mb_strtolower($att->original_name ?? '');
                return str_contains($n, 'акт') || str_contains($n, 'akt');
            });
            $act->setRelation('attachment', $txActFiles->isNotEmpty() ? $txActFiles : $act->transaction->attachment);
        }

        return [
            'act' => $act,
        ];
    }

    /**
     * Save signed act attachment and status.
     */
    public function saveSignedAct(Request $request)
    {
        $actId = $request->input('act.id');
        $act = Act::where('user_id', Auth::id())->findOrFail($actId);

        $attachments = $request->input('act.attachment', []);
        $act->attachment()->sync($attachments);

        $newStatus = $request->input('act.status');
        if (!empty($attachments) && ($newStatus === 'draft' || empty($newStatus))) {
            $newStatus = 'signed';
        }
        if ($newStatus) {
            $act->status = $newStatus;
        }
        $act->save();

        Toast::info('Підписаний акт успішно збережено.');

        return redirect()->route('platform.acts');
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

