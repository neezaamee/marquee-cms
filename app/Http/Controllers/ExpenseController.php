<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Marquee;
use App\Models\Supplier;
use App\Repositories\ExpenseRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
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
     * Printable view of the expense register.
     */
    public function printRegister(Request $request, ExpenseRepositoryInterface $repository)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_expenses'), 403);

        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $filters = [
            'marquee_id' => $marqueeId,
            'status' => $request->input('status'),
            'branch_id' => $request->input('branch_id'),
            'expense_category_id' => $request->input('expense_category_id'),
            'supplier_id' => $request->input('supplier_id'),
            'payment_method' => $request->input('payment_method'),
            'payment_status' => $request->input('payment_status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
        ];

        $expenses = $repository->all($filters);

        $totalAmount = (float)$expenses->sum('total_amount');
        $totalTax = (float)$expenses->sum('tax_amount');
        $totalSubtotal = (float)$expenses->sum('amount');
        $count = $expenses->count();

        $branchName = null;
        if (!empty($filters['branch_id'])) {
            $branch = Branch::find($filters['branch_id']);
            $branchName = $branch ? $branch->name : null;
        }

        $categoryName = null;
        if (!empty($filters['expense_category_id'])) {
            $cat = ExpenseCategory::find($filters['expense_category_id']);
            $categoryName = $cat ? $cat->name : null;
        }

        $supplierName = null;
        if (!empty($filters['supplier_id'])) {
            $sup = Supplier::find($filters['supplier_id']);
            $supplierName = $sup ? $sup->name : null;
        }

        $isPdf = false;

        return view('finance.expenses.expense-register-pdf', compact(
            'expenses', 'marquee', 'filters', 'totalAmount', 'totalTax', 'totalSubtotal', 'count',
            'branchName', 'categoryName', 'supplierName', 'isPdf'
        ));
    }

    /**
     * Stream or download PDF of the expense register.
     */
    public function pdfRegister(Request $request, ExpenseRepositoryInterface $repository)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_expenses'), 403);

        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $filters = [
            'marquee_id' => $marqueeId,
            'status' => $request->input('status'),
            'branch_id' => $request->input('branch_id'),
            'expense_category_id' => $request->input('expense_category_id'),
            'supplier_id' => $request->input('supplier_id'),
            'payment_method' => $request->input('payment_method'),
            'payment_status' => $request->input('payment_status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
        ];

        $expenses = $repository->all($filters);

        $totalAmount = (float)$expenses->sum('total_amount');
        $totalTax = (float)$expenses->sum('tax_amount');
        $totalSubtotal = (float)$expenses->sum('amount');
        $count = $expenses->count();

        $branchName = null;
        if (!empty($filters['branch_id'])) {
            $branch = Branch::find($filters['branch_id']);
            $branchName = $branch ? $branch->name : null;
        }

        $categoryName = null;
        if (!empty($filters['expense_category_id'])) {
            $cat = ExpenseCategory::find($filters['expense_category_id']);
            $categoryName = $cat ? $cat->name : null;
        }

        $supplierName = null;
        if (!empty($filters['supplier_id'])) {
            $sup = Supplier::find($filters['supplier_id']);
            $supplierName = $sup ? $sup->name : null;
        }

        $isPdf = true;

        $pdf = Pdf::loadView('finance.expenses.expense-register-pdf', compact(
            'expenses', 'marquee', 'filters', 'totalAmount', 'totalTax', 'totalSubtotal', 'count',
            'branchName', 'categoryName', 'supplierName', 'isPdf'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('Expense_Register_' . date('Ymd_His') . '.pdf');
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
        
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasAccessToMarquee($expense->marquee_id)) {
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
        
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasAccessToMarquee($expense->marquee_id)) {
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
