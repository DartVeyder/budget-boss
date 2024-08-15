<?php

namespace App\Orchid\Layouts\Finance\Transaction\Category;

use App\Models\FinanceTransactionMcc;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

class CategoryRows extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;
    protected int $type_id;
    public function __construct(int $type_id)
    {
        $this->type_id = $type_id;
    }
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Input::make('category.name')
                ->title('Name')
                ->required(),
            Relation::make('category.mccs')
                    ->fromModel(FinanceTransactionMcc::class, 'code')
                    ->multiple()
                    ->title('MCC') ,
            Input::make('category.transaction_type_id')
                ->value( $this->type_id)
                ->hidden(),
            Input::make('category.user_id')
                ->value(Auth::user()->id)
                ->hidden(),
        ];
    }
}
