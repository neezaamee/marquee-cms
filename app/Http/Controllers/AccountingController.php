<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountingController extends Controller
{
    /**
     * Display the financial years manager.
     */
    public function financialYears()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.financial-years');
    }

    /**
     * Display the hierarchical Chart of Accounts.
     */
    public function chartOfAccounts()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.chart-of-accounts');
    }

    /**
     * Display the opening balances entry screen.
     */
    public function openingBalances()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.opening-balances');
    }

    /**
     * Display the journal vouchers list.
     */
    public function journalVouchers()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.journal-vouchers');
    }

    /**
     * Display the form to record a new journal voucher.
     */
    public function createJournalVoucher()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.journal-voucher-create');
    }

    /**
     * Display the form to edit an existing journal voucher.
     */
    public function editJournalVoucher($id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.journal-voucher-edit', compact('id'));
    }

    /**
     * Display the general ledger query screen.
     */
    public function generalLedger()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.general-ledger');
    }

    /**
     * Display the trial balance report.
     */
    public function trialBalance()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.trial-balance');
    }

    /**
     * Display the Profit & Loss statement report.
     */
    public function profitLoss()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.profit-loss');
    }

    /**
     * Display the Balance Sheet statement report.
     */
    public function balanceSheet()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.balance-sheet');
    }

    /**
     * Display the cash and bank management screen.
     */
    public function cashBank()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.cash-bank');
    }
}
