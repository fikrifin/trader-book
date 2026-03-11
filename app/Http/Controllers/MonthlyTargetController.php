<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonthlyTargetRequest;
use App\Models\MonthlyTarget;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyTargetController extends Controller
{
    public function index(): Response
    {
        $targets = MonthlyTarget::query()
            ->forUser()
            ->with('tradingAccount:id,name')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Targets', [
            'targets' => $targets,
        ]);
    }

    public function store(StoreMonthlyTargetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        MonthlyTarget::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'trading_account_id' => $validated['trading_account_id'],
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'target_profit' => $validated['target_profit'] ?? null,
                'target_win_rate' => $validated['target_win_rate'] ?? null,
                'target_max_drawdown' => $validated['target_max_drawdown'] ?? null,
            ]
        );

        return back()->with('success', 'Target bulanan berhasil disimpan.');
    }

    public function update(StoreMonthlyTargetRequest $request, MonthlyTarget $monthlyTarget): RedirectResponse
    {
        abort_if($monthlyTarget->user_id !== auth()->id(), 403);

        $validated = $request->validated();

        $monthlyTarget->update([
            'trading_account_id' => $validated['trading_account_id'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'target_profit' => $validated['target_profit'] ?? null,
            'target_win_rate' => $validated['target_win_rate'] ?? null,
            'target_max_drawdown' => $validated['target_max_drawdown'] ?? null,
        ]);

        return back()->with('success', 'Target bulanan berhasil diperbarui.');
    }
}
