<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'keyword',
        'display_name',
        'category_id',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
        ];
    }

    /**
     * このルールを所有するユーザー。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ルール適用時に設定するカテゴリー。
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}