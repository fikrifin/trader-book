<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyJournalRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'mood_before' => ['nullable', 'integer', 'min:1', 'max:5'],
            'plan' => ['nullable', 'string'],
            'review' => ['nullable', 'string'],
            'followed_risk_rules' => ['nullable', 'boolean'],
        ];
    }
}