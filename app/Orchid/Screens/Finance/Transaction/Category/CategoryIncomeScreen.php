<?php

namespace App\Orchid\Screens\Finance\Transaction\Category;

use App\Models\FinanceTransaction;
use App\Models\FinanceTransactionCategory;
use App\Orchid\Layouts\Finance\Transaction\Category\CategoryListLayout;
use App\Orchid\Layouts\Finance\Transaction\Category\CategoryRows;
use App\Orchid\Layouts\Finance\Transaction\Category\TabMenuCategory;
use App\Orchid\Layouts\Finance\Transaction\TransactionEditIncomeRows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class CategoryIncomeScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            "categories" => FinanceTransactionCategory::filters()
                ->where('transaction_type_id' ,2)
                ->where('user_id' , Auth::user()->id)
                ->defaultSort('id', 'desc')
                ->paginate()
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Categories';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Add'))
                ->modal('addCategory')
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
            TabMenuCategory::class,
            CategoryListLayout::class,
            Layout::modal('addCategory', [
                new CategoryRows(2)
            ])
                ->applyButton(__('Save'))
                ->title(__('New category income')),
            Layout::modal('asyncEditCategoryModal',  new CategoryRows(2))
                ->async('asyncGetCategory'),
        ];
    }

    /**
     * @return array
     */
    public function asyncGetCategory( FinanceTransactionCategory $category): iterable
    {
        return [
            'category' => $category,
        ];
    }
    public function save(Request $request, FinanceTransactionCategory $category){
        $category->fill($request->input('category'))->save();
        Toast::info(__('You have successfully created.'));
    }
    public function remove(Request $request)
    {
        FinanceTransactionCategory::findOrFail($request->get('id'))->delete();
        Toast::info(__('You have successfully remove'));
        return redirect()->route('platform.transactions.categories.income');
    }
}
