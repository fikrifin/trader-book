<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Services\TwelveDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InstrumentController extends Controller
{
    public function __construct(protected TwelveDataService $twelveDataService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $instruments = Instrument::query()
            ->forUser()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Instruments', [
            'instruments' => $instruments,
            'twelvedataConfigured' => $this->twelveDataService->isConfigured(),
        ]);
    }

    public function syncFromProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:30'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'category' => ['nullable', 'in:forex,commodity,crypto,index,stock'],
        ]);

        if (! $this->twelveDataService->isConfigured()) {
            return back()->with('error', 'TWELVEDATA_API_KEY belum dikonfigurasi.');
        }

        try {
            $results = $this->twelveDataService->searchSymbols($validated['query'], (int) ($validated['limit'] ?? 20));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if (filled($validated['category'] ?? null)) {
            $results = collect($results)
                ->where('category', $validated['category'])
                ->values()
                ->all();
        }

        if (empty($results)) {
            return back()->with('error', 'Tidak ada instrument yang cocok dari Twelve Data.');
        }

        $created = 0;
        $updated = 0;

        foreach ($results as $item) {
            $existing = Instrument::query()->where([
                'user_id' => Auth::id(),
                'symbol' => $item['symbol'],
            ])->first();

            if ($existing) {
                $existing->update([
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'is_active' => true,
                ]);
                $updated++;

                continue;
            }

            Instrument::query()->create([
                'user_id' => Auth::id(),
                'symbol' => $item['symbol'],
                'name' => $item['name'],
                'category' => $item['category'],
                'is_active' => true,
            ]);

            $created++;
        }

        return back()->with('success', "Sinkronisasi selesai. {$created} ditambahkan, {$updated} diperbarui.");
    }

    public function prices(Request $request): JsonResponse
    {
        if (! $this->twelveDataService->isConfigured()) {
            return response()->json([
                'message' => 'TWELVEDATA_API_KEY belum dikonfigurasi.',
                'prices' => [],
            ], 422);
        }

        $symbols = collect(explode(',', (string) $request->query('symbols', '')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values();

        if ($symbols->isEmpty()) {
            $symbols = Instrument::query()
                ->forUser()
                ->where('is_active', true)
                ->latest('id')
                ->limit(20)
                ->pluck('symbol');
        }

        $prices = $symbols->map(function (string $symbol) {
            $instrument = Instrument::query()
                ->forUser()
                ->where('symbol', $symbol)
                ->first();

            if ($instrument) {
                return $this->twelveDataService->refreshInstrumentPrice($instrument);
            }

            $price = $this->twelveDataService->getPrice($symbol);

            return [
                ...$price,
                'updated_at' => now()->toDateTimeString(),
            ];
        })->keyBy('symbol');

        return response()->json([
            'prices' => $prices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('settings.instruments.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:forex,commodity,crypto,index,stock'],
            'pip_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Instrument::query()->create([
            ...$validated,
            'user_id' => Auth::id(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Instrument berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): RedirectResponse
    {
        return redirect()->route('settings.instruments.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): RedirectResponse
    {
        return redirect()->route('settings.instruments.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Instrument $instrument): RedirectResponse
    {
        abort_if($instrument->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:forex,commodity,crypto,index,stock'],
            'pip_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $instrument->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Instrument berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Instrument $instrument): RedirectResponse
    {
        abort_if($instrument->user_id !== Auth::id(), 403);

        $instrument->delete();

        return back()->with('success', 'Instrument berhasil dihapus.');
    }
}
