<?php

namespace App\Repositories;

use App\Models\Expense;

interface ExpenseRepositoryInterface
{
    public function all(array $filters = []);
    
    public function find(int $id): ?Expense;
    
    public function create(array $data): Expense;
    
    public function update(int $id, array $data): Expense;
    
    public function delete(int $id): bool;
}
