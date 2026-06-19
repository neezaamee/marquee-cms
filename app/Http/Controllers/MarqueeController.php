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

        $rules = [
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
        ];

        // Owner fields are required if provided in standard HTTP requests (for API/post compatibility)
        if ($request->has('owner_name') || $request->has('owner_email')) {
            $rules['owner_name'] = 'required|string|max:255';
            $rules['owner_username'] = 'required|string|max:255|unique:users,username';
            $rules['owner_email'] = 'required|email|max:255|unique:users,email';
            $rules['owner_password'] = 'required|string|min:8';
            $rules['owner_phone'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $marqueeData = $validated;
            unset(
                $marqueeData['owner_name'],
                $marqueeData['owner_username'],
                $marqueeData['owner_email'],
                $marqueeData['owner_password'],
                $marqueeData['owner_phone']
            );

            $marquee = Marquee::create($marqueeData);

            // Create Default Head Office Branch
            \App\Models\Branch::create([
                'marquee_id' => $marquee->id,
                'name' => 'Head Office',
                'address' => $marquee->address,
                'city' => $marquee->city,
                'province' => $marquee->province,
                'phone' => $marquee->phone,
                'status' => 'active',
            ]);

            if ($request->has('owner_name')) {
                $ownerRole = \App\Models\Role::where('name', 'owner')->first();
                \App\Models\User::create([
                    'name' => $validated['owner_name'],
                    'email' => $validated['owner_email'],
                    'username' => $validated['owner_username'],
                    'password' => \Illuminate\Support\Facades\Hash::make($validated['owner_password']),
                    'marquee_id' => $marquee->id,
                    'branch_id' => null,
                    'role_id' => $ownerRole ? $ownerRole->id : null,
                    'phone' => $validated['owner_phone'] ?? null,
                    'status' => 'active',
                ]);
            }
        });

        return redirect()->route('marquees.index')->with('success', 'Marquee tenant and Owner user account created successfully.');
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
