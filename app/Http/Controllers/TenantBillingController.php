<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TenantBillingController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isBusinessOwner() || auth()->user()->isSuperAdmin(), 403);
        return view('billing.index');
    }

    public function success(Request $request)
    {
        abort_unless(auth()->user()->isBusinessOwner() || auth()->user()->isSuperAdmin(), 403);
        return view('billing.success');
    }

    public function cancel(Request $request)
    {
        abort_unless(auth()->user()->isBusinessOwner() || auth()->user()->isSuperAdmin(), 403);
        return view('billing.cancel');
    }
}
