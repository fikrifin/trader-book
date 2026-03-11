<?php

namespace App\Http\Controllers;

use App\Models\Setup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $setups = Setup::query()
            ->forUser()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Setups', [
            'setups' => $setups,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('settings.setups.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Setup::query()->create([
            ...$validated,
            'user_id' => auth()->id(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Setup berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): RedirectResponse
    {
        return redirect()->route('settings.setups.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): RedirectResponse
    {
        return redirect()->route('settings.setups.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setup $setup): RedirectResponse
    {
        abort_if($setup->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $setup->update([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Setup berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setup $setup): RedirectResponse
    {
        abort_if($setup->user_id !== auth()->id(), 403);

        $setup->delete();

        return back()->with('success', 'Setup berhasil dihapus.');
    }
}
