<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Filters\Types\WhereMaxMin;
use App\Custom\Metrics\Chartable;
use Orchid\Screen\AsSource;

class FinanceTransaction extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    use Chartable;
    use SoftDeletes;
    use \Orchid\Attachment\Attachable;

    protected $guarded = [];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'transaction_type_id'  => Where::class,
        'customer_id'  => Where::class,
        'counterparty_id' => Where::class,
        'transaction_category_id'=> Where::class,
        'finance_bill_id' => Where::class,
        'fop_id' => Where::class,
        'tax_type' => Where::class,
        'tax_quarter' => Where::class,
        'tax_year' => Where::class,
        'created_at' => WhereDateStartEnd::class,
        'amount' => WhereMaxMin::class,
        'accrual_date'=>WhereDateStartEnd::class,
    ];


    protected $allowedSorts = [
        'id',
        'transaction_type_id',
        'transaction_category_id',
        'amount',
        'finance_bill_id',
        'created_at',
        'customer_id',
        'counterparty_id',
        'accrual_date',
        'mcc_code',
        'tax_year',
        'tax_quarter',
    ];

    public const TAX_TYPES = [
        'single_tax'   => 'Єдиний податок (5%)',
        'military_tax' => 'Військовий збір (1%)',
        'esv'          => 'ЄСВ',
    ];

    public function getTaxTypeLabelAttribute(): ?string
    {
        return self::TAX_TYPES[$this->tax_type] ?? $this->tax_type;
    }

    public function getTaxPeriodLabelAttribute(): ?string
    {
        if (!$this->tax_quarter || !$this->tax_year) {
            return null;
        }
        return "{$this->tax_quarter} кв. {$this->tax_year} р.";
    }

    public function scopeTaxPayments($query, $fopId = null, $year = null, $quarter = null, $taxType = null)
    {
        $query->where('type', 'expenses');

        if ($fopId) {
            $query->where('fop_id', $fopId);
        }
        if ($year) {
            $query->where('tax_year', $year);
        }
        if ($quarter) {
            $query->where('tax_quarter', $quarter);
        }
        if ($taxType) {
            $query->where('tax_type', $taxType);
        }

        return $query;
    }


    public function getCurrencyAmountAttribute($value)
    {
        return abs((float)$value);
    }
    public function scopeTotalAmount($query)
    {
        return $query->sum(DB::raw('currency_value * amount'));
    }


    public function bill(){
        return $this->belongsTo(FinanceBill::class , 'finance_bill_id');
    }

    public function category(){
        return $this->belongsTo(FinanceTransactionCategory::class, 'transaction_category_id');
    }

    public function currency(){
        return $this->belongsTo(FinanceCurrency::class, 'finance_currency_id');
    }
    public function invoice(){
        return $this->belongsTo(FinanceInvoice::class, 'finance_invoice_id');
    }

    public function type(){
        return $this->belongsTo(FinanceTransactionType::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function taxes(){
        return $this->belongsToMany(TaxRate::class, 'finance_transaction_tax_rate')
                    ->withPivot('amount')
                    ->withTimestamps();
    }

    public function fop(){
        return $this->belongsTo(Fop::class, 'fop_id');
    }

    public function counterparty(){
        return $this->belongsTo(CustomerCounterparty::class, 'counterparty_id');
    }

    public function act(){
        return $this->hasOne(Act::class, 'finance_transaction_id');
    }
}
