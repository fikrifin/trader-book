<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Instrument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'name',
        'category',
        'pip_value',
        'last_price',
        'price_change_pct',
        'price_source',
        'price_updated_at',
        'is_active',
    ];

    protected $casts = [
        'pip_value' => 'decimal:4',
        'last_price' => 'decimal:6',
        'price_change_pct' => 'decimal:4',
        'price_updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class);
    }
}
