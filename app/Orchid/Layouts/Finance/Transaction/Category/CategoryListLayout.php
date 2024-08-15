<?php
namespace App\Orchid\Layouts\Finance\Transaction\Category;


use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use Orchid\Platform\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use App\Orchid\Screens\Components\Cells\DateTime;
use App\Orchid\Screens\Components\Cells\DateTimeSplit;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CategoryListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'categories';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('name', __('Name'))
            ->render(fn (FinanceTransactionCategory $category) =>
            ModalToggle::make($category->name)
                ->modal('asyncEditCategoryModal')
                ->modalTitle( __('Edit category '). '"'.$category->name.'"')
                ->method('save')
                ->asyncParameters([
                    'category' => $category->id,
                ])),
            TD::make('mcc', 'Mcc code')
                ->render(fn(FinanceTransactionCategory $category) =>  implode(", ",$category->mccs->pluck('code')->toArray())),
            TD::make('created_at', __('Created'))
                ->usingComponent(DateTime::class)
                ->align(TD::ALIGN_RIGHT)
                ->sort(),
            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (FinanceTransactionCategory $category) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->method('remove', [
                                'id' => $category->id,
                            ]),
                    ])),
        ];
    }
}
