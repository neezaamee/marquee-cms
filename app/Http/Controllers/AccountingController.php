<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccountingService;
use App\Models\Branch;
use App\Models\FinancialYear;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403);
        $voucher = \App\Models\JournalVoucher::withoutGlobalScopes()->findOrFail($id);
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($voucher->marquee_id)) {
            abort(403, 'Unauthorized access to this journal voucher.');
        }
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
     * Export General Ledger report to PDF.
     */
    public function generalLedgerPdf(Request $request, AccountingService $accountingService)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);

        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'financial_year_id' => 'required|exists:financial_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = auth()->user();
        $isSaas = $request->boolean('is_saas');
        $marquee = $isSaas ? null : $user->marquee;

        $accountId = (int)$request->input('account_id');
        $financialYearId = (int)$request->input('financial_year_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id') ? (int)$request->input('branch_id') : null;

        // Enforce user branch scope
        if (!$isSaas && $user->branch_id && !$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }

        try {
            $ledgerData = $accountingService->getGeneralLedger(
                $accountId,
                $startDate,
                $endDate,
                $branchId,
                $financialYearId
            );
        } catch (\Exception $e) {
            abort(400, 'Unable to generate General Ledger PDF: ' . $e->getMessage());
        }

        $branchName = $isSaas 
            ? 'SaaS Platform' 
            : ($branchId ? (Branch::find($branchId)?->name ?? 'Branch') : 'All Branches / Central');
            
        $financialYear = FinancialYear::find($financialYearId);

        $pdf = Pdf::loadView('finance.general-ledger-pdf', [
            'ledgerData' => $ledgerData,
            'account' => $ledgerData['account'],
            'marquee' => $marquee,
            'branchName' => $branchName,
            'financialYear' => $financialYear,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isSaas' => $isSaas,
            'isPdf' => true,
        ])->setPaper('a4', 'portrait');

        $accountCode = $ledgerData['account']->account_code;
        $filename = 'General_Ledger_' . $accountCode . '_' . date('Ymd_His') . '.pdf';
        
        return $pdf->stream($filename);
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

    /**
     * Display the payment vouchers (CPV / BPV) list.
     */
    public function paymentVouchers()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.payment-vouchers.index');
    }

    /**
     * Display the create payment voucher screen.
     */
    public function createPaymentVoucher()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.payment-vouchers.create');
    }

    /**
     * Display the edit payment voucher screen.
     */
    public function editPaymentVoucher($id)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403);
        $voucher = \App\Models\PaymentVoucher::withoutGlobalScopes()->findOrFail($id);
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($voucher->marquee_id)) {
            abort(403, 'Unauthorized access to this payment voucher.');
        }
        return view('finance.payment-vouchers.edit', compact('id'));
    }

    /**
     * Display payment voucher details.
     */
    public function showPaymentVoucher($id)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403);
        $voucher = \App\Models\PaymentVoucher::withoutGlobalScopes()->findOrFail($id);
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($voucher->marquee_id)) {
            abort(403, 'Unauthorized access to this payment voucher.');
        }
        return view('finance.payment-vouchers.show', compact('id'));
    }

    /**
     * Display printable A4 / A5 formal payment voucher slip.
     */
    public function printPaymentVoucher($id)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403);
        $voucher = \App\Models\PaymentVoucher::withoutGlobalScopes()->with(['branch', 'marquee', 'cashBankAccount.account', 'debitAccount', 'supplier', 'vendor', 'expense', 'preparedBy', 'approvedBy', 'disbursedBy', 'journalVoucher'])->findOrFail($id);
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($voucher->marquee_id)) {
            abort(403, 'Unauthorized access to this payment voucher.');
        }
        return view('finance.payment-vouchers.print', compact('voucher'));
    }
}

