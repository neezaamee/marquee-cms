<?php

namespace App\Http\Controllers;

use App\Models\Marquee;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class MarqueeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('marquees.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plans = SubscriptionPlan::all();
        return view('marquees.create', compact('plans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048', // 2MB max
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:marquees,email',
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'tax_authority' => 'required|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'nullable|date',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        Marquee::create($validated);

        return redirect()->route('marquees.index')->with('success', 'Marquee tenant created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Marquee $marquee)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $marquee->load(['subscriptionPlan', 'branches', 'users']);
        return view('marquees.show', compact('marquee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Marquee $marquee)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $plans = SubscriptionPlan::all();
        return view('marquees.edit', compact('marquee', 'plans'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Marquee $marquee)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:marquees,email,' . $marquee->id,
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'tax_authority' => 'required|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'nullable|date',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        $marquee->update($validated);

        return redirect()->route('marquees.index')->with('success', 'Marquee tenant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Marquee $marquee)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $marquee->delete();

        return redirect()->route('marquees.index')->with('success', 'Marquee tenant deleted successfully.');
    }
}
