<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstrumentController extends Controller
{
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
            'user_id' => auth()->id(),
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
        abort_if($instrument->user_id !== auth()->id(), 403);

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
        abort_if($instrument->user_id !== auth()->id(), 403);

        $instrument->delete();

        return back()->with('success', 'Instrument berhasil dihapus.');
    }
}
