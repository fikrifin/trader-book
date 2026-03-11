<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyTargetRequest extends FormRequest
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
            'trading_account_id' => ['required', 'integer', 'exists:trading_accounts,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'target_profit' => ['nullable', 'numeric'],
            'target_win_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_max_drawdown' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
