<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display the expense dashboard.
     */
    public function dashboard()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_expenses'), 403);
        return view('finance.expenses.dashboard');
    }

    /**
     * Display a listing of expenses.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_expenses'), 403);
        return view('finance.expenses.index');
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_expenses'), 403);
        return view('finance.expenses.create');
    }

    /**
     * Display the specified expense detail.
     */
    public function show($id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_expenses'), 403);
        
        $expense = Expense::findOrFail($id);
        
        if (!auth()->user()->isSuperAdmin() && $expense->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this expense record.');
        }

        return view('finance.expenses.show', compact('id'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit($id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_expenses'), 403);
        
        $expense = Expense::findOrFail($id);
        
        if (!auth()->user()->isSuperAdmin() && $expense->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this expense record.');
        }

        if ($expense->status !== Expense::STATUS_DRAFT && $expense->status !== Expense::STATUS_REJECTED) {
            return redirect()->route('expenses.show', $id)->with('error', 'Only Draft or Rejected expenses can be modified.');
        }

        return view('finance.expenses.edit', compact('id'));
    }

    /**
     * Manage expense categories.
     */
    public function categories()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'), 403);
        return view('finance.expenses.categories');
    }

    /**
     * Manage petty cash drawer accounts.
     */
    public function pettyCash()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expenses') || auth()->user()->hasPermission('manage_accounting'), 403);
        return view('finance.expenses.petty-cash');
    }

    /**
     * Monitor budgets.
     */
    public function budgets()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'), 403);
        return view('finance.expenses.budgets');
    }

    /**
     * Manage recurring template generation.
     */
    public function recurring()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_expense_settings'), 403);
        return view('finance.expenses.recurring');
    }

    /**
     * Financial analytical reports.
     */
    public function reports()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_reports'), 403);
        return view('finance.expenses.reports');
    }
}
