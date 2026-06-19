<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('subscription-plans.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('subscription-plans.create');
    }

    public function edit(SubscriptionPlan $subscription_plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('subscription-plans.edit', ['plan' => $subscription_plan]);
    }

    public function show(SubscriptionPlan $subscription_plan)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $subscription_plan->load(['planFeatures', 'billingCycles']);
        return view('subscription-plans.show', ['plan' => $subscription_plan]);
    }
}
