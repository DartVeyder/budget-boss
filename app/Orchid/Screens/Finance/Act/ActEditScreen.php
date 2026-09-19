<?php

namespace App\Orchid\Screens\Finance\Act;

use App\Models\Act;
use App\Models\ActItem;
use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Models\Fop;
use App\Orchid\Layouts\Finance\Act\ActPartiesListener;
use App\Services\Finance\Act\DocumentGenerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ActEditScreen extends Screen
{
    /**
     * @var Act
     */
    public $act;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Act $act, Request $request, DocumentGenerationService $documentService): iterable
    {
        if ($act->exists && $act->user_id !== Auth::id()) {
            abort(403);
        }

        $this->act = $act;

        $items = [];

        if ($act->exists) {
            $act->loadMissing(['items', 'fop', 'customer', 'counterparty', 'attachment', 'transaction.attachment']);
            if ($act->attachment->isEmpty() && $act->transaction && $act->transaction->attachment->isNotEmpty()) {
                $txActFiles = $act->transaction->attachment->filter(function ($att) {
                    $n = mb_strtolower($att->original_name ?? '');
                    return str_contains($n, 'акт') || str_contains($n, 'akt');
                });
                $suggested = $txActFiles->isNotEmpty() ? $txActFiles : $act->transaction->attachment;
                $act->setRelation('attachment', $suggested);
            }

            $items = $act->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => (float)$item->quantity,
                    'price' => (float)$item->price,
                ];
            })->toArray();
        } else {
            // New Act: check if prefilling from Transaction or Invoice
            if ($request->has('transaction_id')) {
                $transaction = FinanceTransaction::where('user_id', Auth::id())->with('attachment')->find($request->get('transaction_id'));
                if ($transaction) {
                    $prepared = $documentService->prepareFromTransaction($transaction);
                    $act->fill($prepared);
                    $items = $prepared['items'] ?? [];
                    if (!empty($prepared['attachments'])) {
                        $act->setRelation('attachment', $transaction->attachment->whereIn('id', $prepared['attachments']));
                    }
                }
            } elseif ($request->has('invoice_id')) {
                $invoice = FinanceInvoice::where('user_id', Auth::id())->find($request->get('invoice_id'));
                if ($invoice) {
                    $prepared = $documentService->prepareFromInvoice($invoice);
                    $act->fill($prepared);
                    $items = $prepared['items'] ?? [];
                }
            } else {
                // Default new act
                $defaultFop = Fop::where('user_id', Auth::id())->where('is_active', true)->first();
                $act->fop_id = $defaultFop?->id;
                $act->act_number = $documentService->generateActNumber();
                $act->act_date = Carbon::now()->toDateString();
                $act->status = 'draft';

                if ($defaultFop?->contract_number) {
                    $act->contract_number = $defaultFop->contract_number;
                }
                if ($defaultFop?->contract_date) {
                    $act->contract_date = $defaultFop->contract_date->toDateString();
                }

                $items = [
                    [
                        'name' => 'Послуги згідно домовленості',
                        'unit' => 'послуга',
                        'quantity' => 1,
                        'price' => 0.00,
                    ]
                ];
            }
        }

        // Auto-select counterparty if customer has 1 counterparty and counterparty is not set
        if ($act->customer_id && empty($act->counterparty_id)) {
            $cust = Customer::with(['counterparties' => fn ($q) => $q->active()])->find($act->customer_id);
            if ($cust && $cust->counterparties->count() === 1) {
                $act->counterparty_id = $cust->counterparties->first()->id;
            }
        }

        $cp = $act->counterparty ?: ($act->counterparty_id ? CustomerCounterparty::find($act->counterparty_id) : null);
        $cust = $act->customer ?: ($act->customer_id ? Customer::find($act->customer_id) : null);

        $activeFop = $act->fop ?: ($act->fop_id ? Fop::find($act->fop_id) : Fop::where('user_id', Auth::id())->where('is_active', true)->first());
        if ($activeFop) {
            if (empty($act->fop_id)) {
                $act->fop_id = $activeFop->id;
            }
        }

        $targetContractNumber = $cp?->contract_number ?: $activeFop?->contract_number;
        $targetContractDate = $cp?->contract_date ? $cp->contract_date->toDateString() : ($activeFop?->contract_date?->toDateString());

        if (empty($act->contract_number) && !empty($targetContractNumber)) {
            $act->contract_number = $targetContractNumber;
        }
        if (empty($act->contract_date) && !empty($targetContractDate)) {
            $act->contract_date = $targetContractDate;
        }

        if (empty($act->customer_address)) {
            $act->customer_address = $cp?->address ?: ($cust?->address ?: '');
        }
        if (empty($act->customer_phone)) {
            $act->customer_phone = $cp?->phone ?: ($cust?->phone ?: '');
        }
        if (empty($act->customer_tax_group)) {
            $act->customer_tax_group = $cp?->tax_group ?: ($cust?->tax_group ?: '');
        }
        if (!isset($act->customer_is_single_tax)) {
            $act->customer_is_single_tax = $cp ? (bool)$cp->is_single_tax : ($cust ? (bool)$cust->is_single_tax : true);
        }
        if (!isset($act->customer_is_vat_payer)) {
            $act->customer_is_vat_payer = $cp ? (bool)$cp->is_vat_payer : ($cust ? (bool)$cust->is_vat_payer : false);
        }

        return [
            'act' => $act,
            'items' => $items,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->act->exists
            ? "Редагування Акту: {$this->act->act_number}"
            : 'Створення Акту та Рахунку';
    }

    /**
     * The screen's description.
     */
    public function description(): ?string
    {
        return 'Оформлення первинних документів з автоматичним підтягуванням реквізитів з обраного ФОПа';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $actions = [];

        if ($this->act && $this->act->exists) {
            $actions[] = Link::make('Друк / Перегляд (A4)')
                ->icon('bs.printer')
                ->route('platform.acts.print', $this->act)
                ->target('_blank');
        }

        $actions[] = Button::make('Зберегти Акт')
            ->icon('bs.check-circle')
            ->type(Color::PRIMARY)
            ->method('save');

        if ($this->act && $this->act->exists) {
            $actions[] = Button::make('Видалити')
                ->icon('bs.trash3')
                ->confirm('Видалити цей акт?')
                ->method('remove');
        }

        return $actions;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            ActPartiesListener::class,

            Layout::block(
                Layout::rows([
                    Group::make([
                        Input::make('act.act_number')
                            ->title('Номер акту')
                            ->required()
                            ->placeholder('АКТ-2026/09-01'),

                        DateTimer::make('act.act_date')
                            ->title('Дата складання акту')
                            ->required()
                            ->format('Y-m-d')
                            ->allowEmpty(),
                    ]),

                    Select::make('act.status')
                        ->title('Статус акту')
                        ->options([
                            'draft' => 'Чернетка',
                            'sent' => 'Надіслано клієнту',
                            'signed' => 'Підписано обома сторонами',
                            'cancelled' => 'Скасовано',
                        ])
                        ->required(),

                    CheckBox::make('create_invoice')
                        ->title('Створити також Рахунок-фактуру на оплату?')
                        ->sendTrueOrFalse()
                        ->value(!(bool)($this->act?->exists))
                        ->help('Одночасно сформує офіційний Рахунок на оплату з цими ж позиціями та реквізитами ФОП'),

                    Upload::make('act.attachment')
                        ->title('Підписаний акт (скан / PDF)')
                        ->acceptedFiles('.pdf,.docx,.doc,.jpg,.jpeg,.png')
                        ->help('Завантажте підписану скан-копію або файл з ЕЦП/КЕП (PDF, JPG тощо)'),

                    TextArea::make('act.notes')
                        ->title('Додаткові примітки')
                        ->rows(2)
                        ->placeholder('Внутрішній коментар або примітка'),
                ])
            )
            ->title('Параметри акту')
            ->description('Номер, дата та статус документа'),

            Layout::block(
                Layout::rows([
                    Matrix::make('items')
                        ->title('Позиції робіт / послуг')
                        ->columns([
                            'Найменування робіт (послуг)' => 'name',
                            'Од. виміру' => 'unit',
                            'Кількість' => 'quantity',
                            'Ціна без ПДВ (грн)' => 'price',
                        ])
                        ->fields([
                            'name' => Input::make()->required()->placeholder('Послуги з розробки програмного забезпечення'),
                            'unit' => Input::make()->value('послуга')->placeholder('послуга'),
                            'quantity' => Input::make()->type('number')->step(0.01)->value(1),
                            'price' => Input::make()->type('number')->step(0.01)->placeholder('0.00'),
                        ])
                        ->help('Вкажіть перелік наданих послуг чи виконаних робіт. Сума розраховується автоматично.'),
                ])
            )
            ->title('Таблична частина послуг')
            ->description('Перелік послуг або робіт, які фіксуються в акті')
            ->commands([
                Button::make('Зберегти Акт')
                    ->type(Color::PRIMARY)
                    ->icon('bs.check-circle')
                    ->method('save'),
            ]),
        ];
    }

    /**
     * Asynchronous listener method triggered when customer is selected.
     */
    public function asyncGetCustomerParties($actData = null)
    {
        $customerId = is_array($actData) ? ($actData['customer_id'] ?? null) : $actData;
        $counterpartyId = is_array($actData) ? ($actData['counterparty_id'] ?? null) : null;

        $customer = $customerId ? Customer::with(['fop', 'counterparties' => fn ($q) => $q->active()])->find($customerId) : null;

        $counterparty = null;
        if ($customer && $customer->counterparties->count() === 1) {
            $counterparty = $customer->counterparties->first();
            $counterpartyId = $counterparty->id;
        } elseif ($counterpartyId && $customer) {
            $counterparty = $customer->counterparties->firstWhere('id', $counterpartyId);
        } elseif ($counterpartyId) {
            $counterparty = CustomerCounterparty::find($counterpartyId);
        }

        $fopId = is_array($actData) ? ($actData['fop_id'] ?? null) : null;
        if (!$fopId && $customer?->fop_id) {
            $fopId = $customer->fop_id;
        }

        $fop = $fopId ? Fop::find($fopId) : null;
        $contractNumber = is_array($actData) ? ($actData['contract_number'] ?? null) : null;
        $contractDate = is_array($actData) ? ($actData['contract_date'] ?? null) : null;

        $targetContractNumber = $counterparty?->contract_number ?: $fop?->contract_number;
        $targetContractDate = $counterparty?->contract_date ? $counterparty->contract_date->toDateString() : ($fop?->contract_date?->toDateString());

        if (empty($contractNumber) && !empty($targetContractNumber)) {
            $contractNumber = $targetContractNumber;
        }
        if (empty($contractDate) && !empty($targetContractDate)) {
            $contractDate = $targetContractDate;
        }

        $taxGroup = $counterparty?->tax_group ?: ($customer?->tax_group ?: '');
        $isSingleTax = $counterparty ? (bool)$counterparty->is_single_tax : ($customer ? (bool)$customer->is_single_tax : true);
        $isVatPayer = $counterparty ? (bool)$counterparty->is_vat_payer : ($customer ? (bool)$customer->is_vat_payer : false);

        $actResult = [
            'customer_id' => $customerId,
            'counterparty_id' => $counterpartyId,
            'customer_address' => $counterparty?->address ?: ($customer?->address ?: ''),
            'customer_phone' => $counterparty?->phone ?: ($customer?->phone ?: ''),
            'customer_tax_group' => $taxGroup,
            'customer_is_single_tax' => $isSingleTax,
            'customer_is_vat_payer' => $isVatPayer,
            'contract_number' => $contractNumber,
            'contract_date' => $contractDate,
        ];
        if ($fopId) {
            $actResult['fop_id'] = $fopId;
        }

        return [
            'act' => $actResult,
        ];
    }

    /**
     * Save or update Act (and optionally Invoice).
     */
    public function save(Act $act, Request $request, DocumentGenerationService $documentService)
    {
        if ($act->exists && $act->user_id !== Auth::id()) {
            abort(403);
        }

        $actData = $request->input('act', []);
        $itemsData = $request->input('items', []);
        $attachments = $request->input('act.attachment', []);
        unset($actData['attachment']);

        if (empty($actData['counterparty_id'])) {
            $actData['counterparty_id'] = null;
        }
        $cp = !empty($actData['counterparty_id']) ? CustomerCounterparty::find($actData['counterparty_id']) : null;
        $fop = !empty($actData['fop_id']) ? Fop::find($actData['fop_id']) : null;

        if (empty($actData['contract_number'])) {
            $actData['contract_number'] = $cp?->contract_number ?: $fop?->contract_number;
        }
        if (empty($actData['contract_date'])) {
            $actData['contract_date'] = $cp?->contract_date ? $cp->contract_date->toDateString() : ($fop?->contract_date?->toDateString());
        }
        if (empty($actData['contract_date'])) {
            $actData['contract_date'] = null;
        }

        $request->validate([
            'act.fop_id' => 'required|exists:fops,id',
            'act.customer_id' => 'required|exists:customers,id',
            'act.act_number' => 'required|string|max:255',
            'act.act_date' => 'required|date',
        ]);

        $totalAmount = 0.0;
        $cleanItems = [];

        foreach ($itemsData as $item) {
            $name = trim($item['name'] ?? '');
            if (empty($name)) {
                continue;
            }

            $qty = max(0.01, (float)($item['quantity'] ?? 1));
            $price = max(0.0, (float)($item['price'] ?? 0));
            $amt = round($qty * $price, 2);
            $totalAmount += $amt;

            $cleanItems[] = [
                'name' => $name,
                'unit' => $item['unit'] ?: 'послуга',
                'quantity' => $qty,
                'price' => $price,
                'amount' => $amt,
            ];
        }

        $act->fill($actData);
        $act->user_id = Auth::id();
        $act->total_amount = $totalAmount;
        $act->save();

        $act->attachment()->sync($attachments);
        if (!empty($attachments) && $act->status === 'draft') {
            $act->status = 'signed';
            $act->save();
        }

        // Sync Act Items
        $act->items()->delete();
        foreach ($cleanItems as $cItem) {
            $act->items()->create($cItem);
        }

        // Check if we should also create or update Invoice
        if ($request->boolean('create_invoice')) {
            $invoice = $act->invoice ?: new FinanceInvoice();
            if (!$invoice->exists) {
                $invoice->user_id = Auth::id();
                $invoice->invoice_number = $documentService->generateInvoiceNumber();
            }

            $invoice->customer_id = $act->customer_id;
            $invoice->fop_id = $act->fop_id;
            $invoice->counterparty_id = $act->counterparty_id;
            $invoice->invoice_date = $act->act_date;
            $invoice->due_date = Carbon::parse($act->act_date)->addDays(3)->toDateString();
            $invoice->contract_number = $act->contract_number;
            $invoice->contract_date = $act->contract_date;
            $invoice->total = $totalAmount;
            $invoice->comment = $cleanItems[0]['name'] ?? 'Оплата послуг';
            $invoice->items_data = $cleanItems;
            $invoice->finance_currency_id = 1; // UAH
            $invoice->status = 'not paid';
            $invoice->save();

            if (!empty($attachments)) {
                $invoice->attachment()->syncWithoutDetaching($attachments);
            }

            $act->finance_invoice_id = $invoice->id;
            $act->save();
        }

        Toast::info('Акт успішно збережено!');

        return redirect()->route('platform.acts');
    }

    /**
     * Delete Act.
     */
    public function remove(Act $act)
    {
        if ($act->user_id !== Auth::id()) {
            abort(403);
        }

        $act->delete();

        Toast::info('Акт видалено.');

        return redirect()->route('platform.acts');
    }
}
