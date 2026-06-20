<?php

namespace App\Http\Controllers;

use App\Models\BillingCycle;
use Illuminate\Http\Request;

class BillingCycleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('billing-cycles.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('billing-cycles.create');
    }

    public function edit(BillingCycle $billing_cycle)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('billing-cycles.edit', ['cycle' => $billing_cycle]);
    }
}
