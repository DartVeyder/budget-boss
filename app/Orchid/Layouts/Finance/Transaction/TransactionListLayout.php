<?php
namespace App\Orchid\Layouts\Finance\Transaction;


use App\Models\Customer;
use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Models\FinanceTransactionType;
use Illuminate\Support\Facades\Auth;
use Orchid\Platform\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use App\Orchid\Screens\Components\Cells\DateTime;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class TransactionListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'transactions';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))
                ->sort()
                ->width('95px')
                ->class('text-nowrap'),

            TD::make('transaction_type_id', __('Тип'))
                ->sort()
                ->width('120px')
                ->class('text-nowrap')
                ->filter(
                    TD::FILTER_SELECT,
                    FinanceTransactionType::where('active', 1)->pluck('name', 'id')
                )
                ->render(function (FinanceTransaction $transaction) {
                    $typeName = $transaction->transactionType?->name ?? match ($transaction->type) {
                        'income' => 'Дохід',
                        'expenses' => 'Витрати',
                        'transfer' => 'Переказ',
                        default => $transaction->type ?? '—',
                    };

                    return match ((int)$transaction->transaction_type_id) {
                        1 => "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1'><i class='bi bi-arrow-up-right me-1'></i> " . e($typeName) . "</span>",
                        2 => "<span class='badge bg-success-subtle text-success border border-success-subtle px-2 py-1'><i class='bi bi-arrow-down-left me-1'></i> " . e($typeName) . "</span>",
                        3 => "<span class='badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1'><i class='bi bi-arrow-left-right me-1'></i> " . e($typeName) . "</span>",
                        4 => "<span class='badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1'><i class='bi bi-clipboard-check me-1'></i> " . e($typeName) . "</span>",
                        default => match ($transaction->type) {
                            'income' => "<span class='badge bg-success-subtle text-success border border-success-subtle px-2 py-1'><i class='bi bi-arrow-down-left me-1'></i> " . e($typeName) . "</span>",
                            'expenses' => "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1'><i class='bi bi-arrow-up-right me-1'></i> " . e($typeName) . "</span>",
                            default => "<span class='badge bg-secondary-subtle text-secondary px-2 py-1'>" . e($typeName) . "</span>",
                        },
                    };
                }),

            TD::make('user_id', __('Користувач'))
                ->canSee(count(Auth::user()?->getSharedUserIds() ?? []) > 1)
                ->sort()
                ->class('text-nowrap')
                ->render(function (FinanceTransaction $transaction) {
                    $uName = $transaction->user?->name ?? '—';
                    $isMe = $transaction->user_id === Auth::id();
                    $badgeClass = $isMe ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-dark border';
                    $meSuffix = $isMe ? ' (Я)' : '';
                    return "<span class='badge {$badgeClass} px-2 py-1'><i class='bi bi-person me-1'></i>" . e($uName . $meSuffix) . "</span>";
                }),

            TD::make('transaction_category_id', __('Category'))
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    FinanceTransactionCategory::whereIn('user_id', Auth::user()?->getSharedUserIds() ?? [Auth::id()])
                        ->pluck('name', 'id'))
                ->render(
                     function(FinanceTransaction $transaction){
                        $str = ($transaction->category)? $transaction->category->name : '';
                        $str .=  ($transaction->source_name) ? "/</br> " . $transaction->source_name  :'';
                        return $str;
                    }
                ),
            TD::make('finance_bill_id', __('Bill'))
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    FinanceBill::whereIn('user_id', Auth::user()?->getSharedUserIds() ?? [Auth::id()])
                        ->pluck('name', 'id'))
                ->render(
                fn(FinanceTransaction $transaction) => $transaction->bill?->name ?? '—'
            ),
            TD::make('customer_id', __('Customer'))
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    Customer::whereIn('user_id', Auth::user()?->getSharedUserIds() ?? [Auth::id()])
                        ->pluck('name', 'id'))
                ->render(
                    function (FinanceTransaction $transaction) {
                        $name = $transaction->customer ? e($transaction->customer->name) : '';
                        if ($transaction->counterparty) {
                            $cpName = e($transaction->counterparty->name);
                            $cpIpn = $transaction->counterparty->ipn ? " (ІПН: {$transaction->counterparty->ipn})" : '';
                            $name .= "<br><span class='text-muted small'><i class='bi bi-person-badge'></i> {$cpName}{$cpIpn}</span>";
                        }
                        return $name;
                    }
                ),
            TD::make('finance_invoice_id', __('№ Invoice'))
                ->render(

                    fn(FinanceTransaction $transaction) => ($transaction->invoice)?$transaction->invoice->invoice_number : ''
                ),
            TD::make('amount', __('Amount'))
                ->sort()
                ->width('140px')
                ->class('text-nowrap')
                ->filter(
                    TD::FILTER_NUMBER_RANGE
                )
                ->render(function ($transaction){
                    return view('finance.transaction.partials.amount',
                        [
                            'type' => $transaction['type'],
                            'amount' => $transaction['amount'],
                            'symbol' =>  $transaction->currency?->symbol ?? ''
                        ]
                    );
                }),
            TD::make('mcc_code', 'MCC')->sort(),
            TD::make('tax_amount', __('Tax amount'))
                ->render(
                fn(FinanceTransaction $transaction) => $transaction->tax_amount . ' '. ($transaction->currency?->symbol ?? '')
            ),
            TD::make('comment', __('Comment'))
                ->render(function (FinanceTransaction $transaction) {
                    $html = e($transaction->comment ?? '');
                    if ($transaction->tax_type) {
                        $badgeClass = match ($transaction->tax_type) {
                            'single_tax' => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'military_tax' => 'bg-dark-subtle text-dark border border-secondary-subtle',
                            'esv' => 'bg-info-subtle text-info border border-info-subtle',
                            default => 'bg-secondary-subtle text-secondary',
                        };
                        $html .= "<div class='mt-1'><span class='badge {$badgeClass} rounded-pill small'>🏛️ {$transaction->tax_type_label} ({$transaction->tax_period_label})</span></div>";
                    }
                    return $html;
                }),
            TD::make('created_at', __('Created'))
                ->sort()
                ->filter(TD::FILTER_DATE_RANGE)
                ->render(
                    fn (FinanceTransaction $transaction) => $transaction->created_at
                )
                ->align(TD::ALIGN_RIGHT)
                ->sort(),
            TD::make('accrual_date', __('Date accrual'))
                ->sort()
                ->filter(TD::FILTER_DATE_RANGE)
                ->render(
                    fn (FinanceTransaction $transaction) => $transaction->accrual_date
                )
                ->align(TD::ALIGN_RIGHT)
                ->sort(),
            TD::make('attachments', __('Документи'))
                ->render(function (FinanceTransaction $transaction) {
                    $links = [];
                    foreach($transaction->attachment as $file) {
                        $url = '/storage/' . $file->path . $file->name . '.' . $file->extension;
                        $links[] = "<a href='{$url}' target='_blank' class='text-primary d-inline-block text-truncate' style='max-width: 150px;' title='{$file->original_name}'><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-paperclip\" viewBox=\"0 0 16 16\"><path d=\"M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z\"/></svg> {$file->original_name}</a>";
                    }
                    return implode('<br>', $links);
                }),
            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (FinanceTransaction $transaction) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->route('platform.transactions.edit', [$transaction->id])

                            ->icon('bs.pencil'),

                        Link::make('Сформувати Акт')
                            ->icon('bs.file-earmark-plus')
                            ->route('platform.acts.create', ['transaction_id' => $transaction->id])
                            ->canSee($transaction->type === 'income' && (int)$transaction->transaction_type_id !== 3),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->method('remove', [
                                'id' => $transaction->id,
                            ]),
                    ])),
        ];
    }
}
