<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class BudgetShare extends Model
{
    use HasFactory;
    use AsSource;

    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_EDITOR => 'Редактор (Повний доступ: створення та редагування)',
        self::ROLE_VIEWER => 'Спостерігач (Тільки перегляд)',
    ];

    protected $fillable = [
        'user_id',
        'partner_id',
        'nickname',
        'role',
        'can_create_transactions',
        'can_manage_bills',
        'permissions',
    ];

    protected $casts = [
        'can_create_transactions' => 'boolean',
        'can_manage_bills' => 'boolean',
        'permissions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function getRoleTitle(): string
    {
        return self::ROLES[$this->role] ?? 'Редактор';
    }
}
