<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instrument_id',
        'timeframe',
        'provider',
        'model',
        'prompt_context',
        'recommendation',
        'confidence',
        'risk_flags',
        'latency_ms',
        'token_usage',
        'status',
        'error_message',
    ];

    protected $casts = [
        'prompt_context' => 'array',
        'recommendation' => 'array',
        'risk_flags' => 'array',
        'token_usage' => 'array',
        'confidence' => 'decimal:2',
        'latency_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
