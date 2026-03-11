<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTradeRequest;
use App\Http\Requests\UpdateTradeRequest;
use App\Models\Instrument;
use App\Models\Setup;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Services\RiskRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $filters = request()->only([
            'date_from',
            'date_to',
            'pair',
            'result',
            'session',
            'direction',
            'account_id',
        ]);

        $trades = Trade::query()
            ->forUser()
            ->when(data_get($filters, 'date_from'), fn ($query, $value) => $query->whereDate('date', '>=', $value))
            ->when(data_get($filters, 'date_to'), fn ($query, $value) => $query->whereDate('date', '<=', $value))
            ->when(data_get($filters, 'pair'), fn ($query, $value) => $query->where('pair', $value))
            ->when(data_get($filters, 'result'), fn ($query, $value) => $query->where('result', $value))
            ->when(data_get($filters, 'session'), fn ($query, $value) => $query->where('session', $value))
            ->when(data_get($filters, 'direction'), fn ($query, $value) => $query->where('direction', $value))
            ->when(data_get($filters, 'account_id'), fn ($query, $value) => $query->where('trading_account_id', $value))
            ->with(['tradingAccount:id,name', 'instrument:id,symbol', 'setupModel:id,name'])
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        $accounts = TradingAccount::query()->forUser()->where('is_active', true)->get(['id', 'name', 'account_type']);

        $pairs = Trade::query()->forUser()->select('pair')->distinct()->orderBy('pair')->pluck('pair');

        return Inertia::render('Trades/Index', [
            'trades' => $trades,
            'filters' => $filters,
            'accounts' => $accounts,
            'pairs' => $pairs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Trades/Create', [
            'accounts' => TradingAccount::query()->forUser()->where('is_active', true)->get(['id', 'name', 'account_type']),
            'instruments' => Instrument::query()->forUser()->where('is_active', true)->orderBy('symbol')->get(['id', 'symbol', 'pip_value']),
            'setups' => Setup::query()->forUser()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTradeRequest $request): RedirectResponse
    {
        $account = TradingAccount::query()
            ->forUser()
            ->where('id', $request->integer('trading_account_id'))
            ->firstOrFail();

        $validated = $request->validated();

        $payload = [
            ...$validated,
            'user_id' => auth()->id(),
            'commission' => data_get($validated, 'commission', 0) ?: 0,
            'swap' => data_get($validated, 'swap', 0) ?: 0,
            'profit_loss' => data_get($validated, 'profit_loss', 0) ?: 0,
            'profit_loss_gross' => data_get($validated, 'profit_loss_gross', 0) ?: 0,
            'followed_plan' => data_get($validated, 'followed_plan', true),
        ];

        if ($request->hasFile('screenshot_before')) {
            $payload['screenshot_before'] = $request->file('screenshot_before')
                ->store('screenshots/'.auth()->id(), 'public');
        }

        if ($request->hasFile('screenshot_after')) {
            $payload['screenshot_after'] = $request->file('screenshot_after')
                ->store('screenshots/'.auth()->id(), 'public');
        }

        if (array_key_exists('tags', $payload) && is_string($payload['tags'])) {
            $payload['tags'] = collect(explode(',', $payload['tags']))
                ->map(fn ($tag) => trim($tag))
                ->filter()
                ->values()
                ->all();
        }

        $trade = Trade::query()->create($payload);

        $trade->tradingAccount->recalculateBalance();

        $riskStatus = app(RiskRuleService::class)->checkDailyStatus($account, (string) $trade->date);
        $warningMessage = collect(data_get($riskStatus, 'warnings', []))->implode(' ');

        return redirect()->route('trades.index')
            ->with('success', 'Trade berhasil disimpan.')
            ->with('warning', $warningMessage ?: null);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trade $trade): Response
    {
        abort_if($trade->user_id !== auth()->id(), 403);

        return Inertia::render('Trades/Show', [
            'trade' => $trade->load(['tradingAccount:id,name,currency', 'instrument:id,symbol', 'setupModel:id,name']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trade $trade): Response
    {
        abort_if($trade->user_id !== auth()->id(), 403);

        return Inertia::render('Trades/Edit', [
            'trade' => $trade,
            'accounts' => TradingAccount::query()->forUser()->where('is_active', true)->get(['id', 'name', 'account_type']),
            'instruments' => Instrument::query()->forUser()->where('is_active', true)->orderBy('symbol')->get(['id', 'symbol', 'pip_value']),
            'setups' => Setup::query()->forUser()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTradeRequest $request, Trade $trade): RedirectResponse
    {
        abort_if($trade->user_id !== auth()->id(), 403);

        $validated = $request->validated();
        $oldAccountId = $trade->trading_account_id;

        $account = TradingAccount::query()
            ->forUser()
            ->where('id', data_get($validated, 'trading_account_id', $trade->trading_account_id))
            ->firstOrFail();

        $payload = [
            ...$validated,
            'commission' => data_get($validated, 'commission', 0) ?: 0,
            'swap' => data_get($validated, 'swap', 0) ?: 0,
            'followed_plan' => data_get($validated, 'followed_plan', true),
        ];

        if ($request->hasFile('screenshot_before')) {
            if ($trade->screenshot_before) {
                Storage::disk('public')->delete($trade->screenshot_before);
            }

            $payload['screenshot_before'] = $request->file('screenshot_before')
                ->store('screenshots/'.auth()->id(), 'public');
        }

        if ($request->hasFile('screenshot_after')) {
            if ($trade->screenshot_after) {
                Storage::disk('public')->delete($trade->screenshot_after);
            }

            $payload['screenshot_after'] = $request->file('screenshot_after')
                ->store('screenshots/'.auth()->id(), 'public');
        }

        if (array_key_exists('tags', $payload) && is_string($payload['tags'])) {
            $payload['tags'] = collect(explode(',', $payload['tags']))
                ->map(fn ($tag) => trim($tag))
                ->filter()
                ->values()
                ->all();
        }

        $trade->update($payload);

        $trade->tradingAccount->recalculateBalance();
        if ($oldAccountId !== $trade->trading_account_id) {
            TradingAccount::query()->where('id', $oldAccountId)->first()?->recalculateBalance();
        }

        return redirect()->route('trades.show', $trade)->with('success', 'Trade berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trade $trade): RedirectResponse
    {
        abort_if($trade->user_id !== auth()->id(), 403);

        if ($trade->screenshot_before) {
            Storage::disk('public')->delete($trade->screenshot_before);
        }

        if ($trade->screenshot_after) {
            Storage::disk('public')->delete($trade->screenshot_after);
        }

        $account = $trade->tradingAccount;
        $trade->delete();
        $account?->recalculateBalance();

        return redirect()->route('trades.index')->with('success', 'Trade berhasil dihapus.');
    }
}
