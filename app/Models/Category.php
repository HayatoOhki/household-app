<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * このカテゴリーを所有するユーザー。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このカテゴリーに属する取引。
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * このカテゴリーを使用する自動入力ルール。
     */
    public function transactionRules(): HasMany
    {
        return $this->hasMany(TransactionRule::class);
    }
}