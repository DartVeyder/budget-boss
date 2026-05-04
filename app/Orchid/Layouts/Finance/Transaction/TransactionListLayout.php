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
            TD::make('id', __('ID')),
            TD::make('transaction_category_id', __('Category'))
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    FinanceTransactionCategory::where('user_id', Auth::user()->id)
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
                    FinanceBill::where('user_id', Auth::user()->id)
                        ->pluck('name', 'id'))
                ->render(
                fn(FinanceTransaction $transaction) => $transaction->bill->name
            ),
            TD::make('customer_id', __('Customer'))
                ->sort()
                ->filter(
                    TD::FILTER_SELECT,
                    Customer::where('user_id', Auth::user()->id)
                        ->pluck('name', 'id'))
                ->render(
                    fn(FinanceTransaction $transaction) => ($transaction->customer)?  $transaction->customer->name: ''
                ),
            TD::make('finance_invoice_id', __('№ Invoice'))
                ->render(

                    fn(FinanceTransaction $transaction) => ($transaction->invoice)?$transaction->invoice->invoice_number : ''
                ),
            TD::make('amount', __('Amount'))
                ->sort()
                ->filter(
                    TD::FILTER_NUMBER_RANGE
                )
                ->render(function ($transaction){
                    return view('finance.transaction.partials.amount',
                        [
                            'type' => $transaction['type'],
                            'amount' => $transaction['amount'],
                            'symbol' =>  $transaction->currency->symbol
                        ]
                    );
                }),
            TD::make('tax_amount', __('Tax amount'))
                ->render(
                fn(FinanceTransaction $transaction) => $transaction->tax_amount . ' '. $transaction->currency->symbol
            ),
            TD::make('comment', __('Comment')) ,
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

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->method('remove', [
                                'id' => $transaction->id,
                            ]),
                    ])),
        ];
    }
}
