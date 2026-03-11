<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyJournalRequest;
use App\Models\DailyJournal;
use App\Models\Trade;
use App\Models\TradingAccount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyJournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        [$year, $monthNumber] = explode('-', $month);

        $start = Carbon::createFromDate((int) $year, (int) $monthNumber, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $activeAccountId = (int) ($request->input('account_id') ?: auth()->user()?->active_account_id);

        $journals = DailyJournal::query()
            ->forUser()
            ->when($activeAccountId, fn ($query) => $query->where('trading_account_id', $activeAccountId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('tradingAccount:id,name')
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        $tradeSummaryByDate = Trade::query()
            ->forUser()
            ->when($activeAccountId, fn ($query) => $query->where('trading_account_id', $activeAccountId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($trade) => Carbon::parse($trade->date)->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'trade_count' => $group->count(),
                'total_pl' => round((float) $group->sum('profit_loss'), 2),
            ])
            ->values();

        return Inertia::render('Journal/Index', [
            'journals' => $journals,
            'month' => $month,
            'trade_summary' => $tradeSummaryByDate,
            'accounts' => TradingAccount::query()->forUser()->where('is_active', true)->get(['id', 'name', 'account_type']),
            'active_account_id' => $activeAccountId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(StoreDailyJournalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $account = TradingAccount::query()
            ->forUser()
            ->where('id', $validated['trading_account_id'])
            ->firstOrFail();

        $journal = DailyJournal::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'trading_account_id' => $account->id,
                'date' => $validated['date'],
            ],
            [
                'mood_before' => data_get($validated, 'mood_before'),
                'plan' => data_get($validated, 'plan'),
                'review' => data_get($validated, 'review'),
                'followed_risk_rules' => data_get($validated, 'followed_risk_rules'),
            ]
        );

        return redirect()->route('journals.show', $journal)->with('success', 'Daily journal berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyJournal $journal): Response
    {
        abort_if($journal->user_id !== auth()->id(), 403);

        $trades = Trade::query()
            ->forUser()
            ->whereDate('date', Carbon::parse($journal->date)->toDateString())
            ->where('trading_account_id', $journal->trading_account_id)
            ->orderBy('id')
            ->get();

        return Inertia::render('Journal/Show', [
            'journal' => $journal->load('tradingAccount:id,name'),
            'trades' => $trades,
            'day_summary' => [
                'trade_count' => $trades->count(),
                'total_pl' => round((float) $trades->sum('profit_loss'), 2),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(StoreDailyJournalRequest $request, DailyJournal $journal): RedirectResponse
    {
        abort_if($journal->user_id !== auth()->id(), 403);

        $validated = $request->validated();

        $journal->update([
            'trading_account_id' => data_get($validated, 'trading_account_id', $journal->trading_account_id),
            'date' => data_get($validated, 'date', $journal->date),
            'mood_before' => data_get($validated, 'mood_before'),
            'plan' => data_get($validated, 'plan'),
            'review' => data_get($validated, 'review'),
            'followed_risk_rules' => data_get($validated, 'followed_risk_rules'),
        ]);

        return back()->with('success', 'Daily journal berhasil diperbarui.');
    }
}
