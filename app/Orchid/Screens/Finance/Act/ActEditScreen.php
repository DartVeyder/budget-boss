<?php

namespace App\Orchid\Screens\Finance\Act;

use App\Models\Act;
use App\Models\ActItem;
use App\Models\Customer;
use App\Models\CustomerCounterparty;
use App\Models\FinanceInvoice;
use App\Models\FinanceTransaction;
use App\Models\Fop;
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
            $act->loadMissing(['items', 'fop', 'customer', 'counterparty']);
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
                $transaction = FinanceTransaction::where('user_id', Auth::id())->find($request->get('transaction_id'));
                if ($transaction) {
                    $prepared = $documentService->prepareFromTransaction($transaction);
                    $act->fill($prepared);
                    $items = $prepared['items'] ?? [];
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
            Layout::block(
                Layout::rows([
                    Relation::make('act.fop_id')
                        ->title('Виконавець (мій ФОП)')
                        ->fromModel(Fop::class, 'name')
                        ->applyScope('user')
                        ->required()
                        ->help('Реквізити (ПІБ, ІПН, адреса, IBAN банку) автоматично перенесуться в документ'),

                    Group::make([
                        Relation::make('act.customer_id')
                            ->title('Замовник (Клієнт)')
                            ->fromModel(Customer::class, 'name')
                            ->applyScope('user')
                            ->required(),

                        Relation::make('act.counterparty_id')
                            ->title('ФОП контрагент (платник)')
                            ->fromModel(CustomerCounterparty::class, 'name')
                            ->applyScope('user')
                            ->help('Оберіть, якщо кошти або документ оформлюються на конкретного ФОПа-платника замовника'),
                    ]),
                ])
            )
            ->title('Сторони документа')
            ->description('Вибір вашого ФОПа та замовника для автоматичного заповнення реквізитів'),

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

                    Group::make([
                        Input::make('act.contract_number')
                            ->title('Номер договору')
                            ->placeholder('№ 12/2026 (необов\'язково)'),

                        DateTimer::make('act.contract_date')
                            ->title('Дата договору')
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
     * Save or update Act (and optionally Invoice).
     */
    public function save(Act $act, Request $request, DocumentGenerationService $documentService)
    {
        if ($act->exists && $act->user_id !== Auth::id()) {
            abort(403);
        }

        $actData = $request->input('act', []);
        $itemsData = $request->input('items', []);

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
