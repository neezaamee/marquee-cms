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
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner(), 403);

        return view('marquees.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner(), 403);

        $plans = SubscriptionPlan::all();
        $businessOwners = $user->isSuperAdmin()
            ? \App\Models\User::whereHas('role', function ($q) {
                $q->whereIn('name', ['business_owner', 'owner']);
            })->orderBy('name')->get()
            : collect();

        return view('marquees.create', compact('plans', 'businessOwners'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner(), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048', // 2MB max
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'email' => 'required|email|max:255|unique:marquees,email',
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'tax_authority' => 'required|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'nullable|date',
            'owner_user_id' => 'nullable|exists:users,id',
        ];

        // Owner fields are required if provided in standard HTTP requests (for API/post compatibility)
        if ($request->has('owner_name') || $request->has('owner_email')) {
            $rules['owner_name'] = 'required|string|max:255';
            $rules['owner_username'] = 'required|string|max:255|unique:users,username';
            $rules['owner_email'] = 'required|email|max:255|unique:users,email';
            $rules['owner_password'] = 'required|string|min:8';
            $rules['owner_phone'] = ['nullable', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'];
        }

        $validated = $request->validate($rules);

        if ($user->isBusinessOwner()) {
            $validated['owner_user_id'] = $user->id;
        }

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
                $ownerRole = \App\Models\Role::whereIn('name', ['business_owner', 'owner'])->first();
                $ownerUser = \App\Models\User::create([
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
                $marquee->update(['owner_user_id' => $ownerUser->id]);
            }
        });

        return redirect()->route('marquees.index')->with('success', 'Marquee tenant and Owner user account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Marquee $marquee)
    {
        abort_unless(auth()->user()->can('view', $marquee), 403);

        $marquee->load(['owners.subscriptionPlan', 'branches', 'users']);
        return view('marquees.show', compact('marquee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Marquee $marquee)
    {
        abort_unless(auth()->user()->can('update', $marquee), 403);

        $plans = SubscriptionPlan::all();
        return view('marquees.edit', compact('marquee', 'plans'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Marquee $marquee)
    {
        abort_unless(auth()->user()->can('update', $marquee), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
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
        abort_unless(auth()->user()->can('delete', $marquee), 403);

        $marquee->delete();

        return redirect()->route('marquees.index')->with('success', 'Marquee tenant deleted successfully.');
    }
}
