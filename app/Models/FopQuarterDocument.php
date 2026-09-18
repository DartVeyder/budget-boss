<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\AsSource;

class FopQuarterDocument extends Model
{
    use HasFactory, AsSource;

    public const TYPE_DECLARATION = 'declaration';
    public const TYPE_RECEIPT_1 = 'receipt_1';
    public const TYPE_RECEIPT_2 = 'receipt_2';
    public const TYPE_TAX_PAYMENT = 'tax_payment';
    public const TYPE_MILITARY_TAX_PAYMENT = 'military_tax_payment';
    public const TYPE_ESV_PAYMENT = 'esv_payment';
    public const TYPE_BANK_STATEMENT = 'bank_statement';
    public const TYPE_ACT = 'act';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_DECLARATION => [
            'label' => 'Декларація платника ЄП',
            'badge' => 'bg-primary text-white',
            'icon'  => 'bi-file-earmark-text',
        ],
        self::TYPE_RECEIPT_1 => [
            'label' => 'Квитанція №1 (Доставка ДПС)',
            'badge' => 'bg-info text-white',
            'icon'  => 'bi-send-check',
        ],
        self::TYPE_RECEIPT_2 => [
            'label' => 'Квитанція №2 (Прийнято ДПС)',
            'badge' => 'bg-success text-white',
            'icon'  => 'bi-check2-all',
        ],
        self::TYPE_TAX_PAYMENT => [
            'label' => 'Сплата Єдиного Податку (5%)',
            'badge' => 'bg-indigo text-white',
            'icon'  => 'bi-credit-card-2-front',
        ],
        self::TYPE_MILITARY_TAX_PAYMENT => [
            'label' => 'Сплата Військового збору (1%)',
            'badge' => 'bg-dark text-white',
            'icon'  => 'bi-shield-check',
        ],
        self::TYPE_ESV_PAYMENT => [
            'label' => 'Сплата ЄСВ',
            'badge' => 'bg-teal text-white',
            'icon'  => 'bi-wallet2',
        ],
        self::TYPE_BANK_STATEMENT => [
            'label' => 'Банківська виписка',
            'badge' => 'bg-secondary text-white',
            'icon'  => 'bi-bank',
        ],
        self::TYPE_ACT => [
            'label' => 'Акт / Договір',
            'badge' => 'bg-warning text-dark',
            'icon'  => 'bi-file-earmark-check',
        ],
        self::TYPE_OTHER => [
            'label' => 'Інший документ',
            'badge' => 'bg-secondary-subtle text-secondary border',
            'icon'  => 'bi-paperclip',
        ],
    ];

    protected $fillable = [
        'fop_id',
        'user_id',
        'year',
        'quarter',
        'document_type',
        'title',
        'notes',
        'attachment_id',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'year'      => 'integer',
        'quarter'   => 'integer',
        'file_size' => 'integer',
    ];

    public function fop(): BelongsTo
    {
        return $this->belongsTo(Fop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->document_type]['label'] ?? 'Документ';
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return self::TYPES[$this->document_type]['badge'] ?? 'bg-secondary text-white';
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPES[$this->document_type]['icon'] ?? 'bi-file-earmark';
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, '.', ' ') . ' МБ';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, '.', ' ') . ' КБ';
        }
        return $bytes . ' байт';
    }

    public function getDownloadUrlAttribute(): string
    {
        if ($this->attachment) {
            return $this->attachment->url();
        }

        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return route('platform.fop.document.download', $this->id);
    }

    public function isPdf(): bool
    {
        return str_ends_with(strtolower($this->original_name), '.pdf') || $this->mime_type === 'application/pdf';
    }

    public function isXml(): bool
    {
        return str_ends_with(strtolower($this->original_name), '.xml') || str_contains((string)$this->mime_type, 'xml');
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp', 'gif']);
    }
}
