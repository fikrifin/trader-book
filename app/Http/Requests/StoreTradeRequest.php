<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTradeRequest extends FormRequest
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
            'instrument_id' => ['nullable', 'integer', 'exists:instruments,id'],
            'setup_id' => ['nullable', 'integer', 'exists:setups,id'],
            'date' => ['required', 'date'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'session' => ['required', 'in:asia,london,new_york,overlap'],
            'pair' => ['required', 'string', 'max:50'],
            'direction' => ['required', 'in:buy,sell'],
            'entry_price' => ['required', 'numeric'],
            'stop_loss' => ['required', 'numeric'],
            'take_profit_1' => ['required', 'numeric'],
            'take_profit_2' => ['nullable', 'numeric'],
            'take_profit_3' => ['nullable', 'numeric'],
            'close_price' => ['nullable', 'numeric'],
            'lot_size' => ['required', 'numeric', 'min:0'],
            'risk_amount' => ['nullable', 'numeric', 'min:0'],
            'commission' => ['nullable', 'numeric', 'min:0'],
            'swap' => ['nullable', 'numeric'],
            'result' => ['required', 'in:win,loss,breakeven,partial'],
            'profit_loss' => ['nullable', 'numeric'],
            'profit_loss_gross' => ['nullable', 'numeric'],
            'pips' => ['nullable', 'numeric'],
            'rr_ratio' => ['nullable', 'numeric'],
            'rr_planned' => ['nullable', 'numeric'],
            'setup' => ['nullable', 'string', 'max:100'],
            'timeframe' => ['nullable', 'string', 'max:20'],
            'followed_plan' => ['nullable', 'boolean'],
            'mistake' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'screenshot_before' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'screenshot_after' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
