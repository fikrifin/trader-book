<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'year',
        'month',
        'target_profit',
        'target_win_rate',
        'target_max_drawdown',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'target_profit' => 'decimal:2',
        'target_win_rate' => 'decimal:2',
        'target_max_drawdown' => 'decimal:2',
    ];

    public function scopeForUser($query)
    {
        return $query->where('user_id', auth()->id());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }
}
