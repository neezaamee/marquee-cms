<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceController extends Controller
{
    /**
     * Display the financial revenue dashboard.
     */
    public function revenue()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_payments'), 403);
        return view('finance.revenue');
    }

    /**
     * Display the global payments ledger list.
     */
    public function payments()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_payments'), 403);
        return view('finance.payments');
    }

    /**
     * Display the central security deposit ledger.
     */
    public function securityDeposits()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_payments'), 403);
        return view('finance.security-deposits');
    }
}
