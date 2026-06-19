<?php

namespace App\Http\Controllers;

use App\Models\PlanFeature;
use Illuminate\Http\Request;

class PlanFeatureController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('plan-features.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('plan-features.create');
    }

    public function edit(PlanFeature $plan_feature)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('plan-features.edit', ['feature' => $plan_feature]);
    }
}
