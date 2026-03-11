<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'broker',
        'account_type',
        'account_number',
        'initial_balance',
        'current_balance',
        'currency',
        'max_daily_loss',
        'max_daily_loss_pct',
        'max_trades_per_day',
        'max_drawdown_pct',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'max_daily_loss' => 'decimal:2',
        'max_daily_loss_pct' => 'decimal:2',
        'max_drawdown_pct' => 'decimal:2',
        'max_trades_per_day' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeForUser($query)
    {
        return $query->where('user_id', auth()->id());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function dailyJournals(): HasMany
    {
        return $this->hasMany(DailyJournal::class);
    }

    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(MonthlyTarget::class);
    }

    public function recalculateBalance(): void
    {
        $totalProfitLoss = $this->trades()->sum('profit_loss');
        $this->current_balance = (float) $this->initial_balance + (float) $totalProfitLoss;
        $this->saveQuietly();
    }
}
