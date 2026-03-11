<?php

namespace App\Http\Middleware;

use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'trading_accounts' => fn () => $user
                ? TradingAccount::query()
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'account_type', 'currency'])
                : [],
            'active_account' => fn () => $user?->active_account_id
                ? TradingAccount::query()
                    ->where('id', $user->active_account_id)
                    ->where('user_id', $user->id)
                    ->first(['id', 'name', 'account_type', 'currency', 'current_balance'])
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
