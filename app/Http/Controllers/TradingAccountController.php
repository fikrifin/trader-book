<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTradingAccountRequest;
use App\Models\TradingAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TradingAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $accounts = TradingAccount::query()
            ->forUser()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Accounts', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('settings.accounts.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTradingAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $account = TradingAccount::query()->create([
            ...$validated,
            'user_id' => auth()->id(),
            'current_balance' => (float) data_get($validated, 'initial_balance', 0),
        ]);

        if (! auth()->user()?->active_account_id) {
            auth()->user()?->update(['active_account_id' => $account->id]);
        }

        return back()->with('success', 'Trading account berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): RedirectResponse
    {
        return redirect()->route('settings.accounts.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): RedirectResponse
    {
        return redirect()->route('settings.accounts.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTradingAccountRequest $request, TradingAccount $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->update($request->validated());
        $account->recalculateBalance();

        return back()->with('success', 'Trading account berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TradingAccount $account): RedirectResponse
    {
        Gate::authorize('delete', $account);

        $user = auth()->user();

        if ($user && (int) $user->active_account_id === (int) $account->id) {
            $nextAccountId = TradingAccount::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $account->id)
                ->value('id');

            $user->update(['active_account_id' => $nextAccountId]);
        }

        $account->delete();

        return back()->with('success', 'Trading account berhasil dihapus.');
    }

    public function switchActive(Request $request): RedirectResponse
    {
        $request->validate([
            'trading_account_id' => [
                'required',
                'integer',
                Rule::exists('trading_accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
        ]);

        $account = TradingAccount::where('id', $request->integer('trading_account_id'))
            ->where('user_id', auth()->id())
            ->firstOrFail();

        auth()->user()->update([
            'active_account_id' => $account->id,
        ]);

        return back();
    }
}
