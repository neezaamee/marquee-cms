<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Repositories\ExpenseRepositoryInterface;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Expense::query()->with(['category', 'type', 'branch', 'supplier', 'employee', 'currency']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['expense_category_id'])) {
            $query->where('expense_category_id', $filters['expense_category_id']);
        }

        if (!empty($filters['expense_type_id'])) {
            $query->where('expense_type_id', $filters['expense_type_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('expense_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('expense_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('cost_center', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();
    }

    public function find(int $id): ?Expense
    {
        return Expense::with(['category', 'type', 'branch', 'supplier', 'employee', 'currency', 'items.category', 'utilityBill', 'maintenanceRecord', 'approvals.user', 'approvals.role', 'attachments'])->find($id);
    }

    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function update(int $id, array $data): Expense
    {
        $expense = Expense::findOrFail($id);
        $expense->update($data);
        return $expense;
    }

    public function delete(int $id): bool
    {
        $expense = Expense::findOrFail($id);
        return $expense->delete();
    }
}
