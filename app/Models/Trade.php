<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'instrument_id',
        'setup_id',
        'date',
        'open_time',
        'close_time',
        'session',
        'pair',
        'direction',
        'entry_price',
        'stop_loss',
        'take_profit_1',
        'take_profit_2',
        'take_profit_3',
        'close_price',
        'lot_size',
        'risk_amount',
        'commission',
        'swap',
        'result',
        'profit_loss',
        'profit_loss_gross',
        'pips',
        'rr_ratio',
        'rr_planned',
        'setup',
        'timeframe',
        'followed_plan',
        'mistake',
        'notes',
        'screenshot_before',
        'screenshot_after',
        'tags',
    ];

    protected $casts = [
        'date' => 'date',
        'tags' => 'array',
        'followed_plan' => 'boolean',
        'entry_price' => 'decimal:5',
        'stop_loss' => 'decimal:5',
        'take_profit_1' => 'decimal:5',
        'take_profit_2' => 'decimal:5',
        'take_profit_3' => 'decimal:5',
        'close_price' => 'decimal:5',
        'lot_size' => 'decimal:2',
        'risk_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'swap' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'profit_loss_gross' => 'decimal:2',
        'pips' => 'decimal:1',
        'rr_ratio' => 'decimal:2',
        'rr_planned' => 'decimal:2',
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

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function setupModel(): BelongsTo
    {
        return $this->belongsTo(Setup::class, 'setup_id');
    }
}
