<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTradingAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'broker' => ['nullable', 'string', 'max:100'],
            'account_type' => ['required', 'in:live,demo,prop'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'max_daily_loss' => ['nullable', 'numeric', 'min:0'],
            'max_daily_loss_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_trades_per_day' => ['nullable', 'integer', 'min:1', 'max:100'],
            'max_drawdown_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
