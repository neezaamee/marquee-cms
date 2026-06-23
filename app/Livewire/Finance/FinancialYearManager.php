<?php

namespace App\Livewire\Finance;

use App\Models\FinancialYear;
use Livewire\Component;
use Livewire\WithPagination;

class FinancialYearManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $name = '';
    public $start_date = '';
    public $end_date = '';
    public $status = 'active';
    public $is_default = false;

    public $editId = null;
    public $isFormOpen = false;

    protected $rules = [
        'name' => 'required|string|max:100',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'status' => 'required|in:active,closed',
        'is_default' => 'boolean',
    ];

    public function openCreateForm()
    {
        $this->resetInputFields();
        $this->isFormOpen = true;
    }

    public function closeForm()
    {
        $this->isFormOpen = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->status = 'active';
        $this->is_default = false;
        $this->editId = null;
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;

        // Business rules validation
        \Illuminate\Support\Facades\DB::transaction(function () use ($marqueeId) {
            if ($this->is_default && $this->status === 'active') {
                FinancialYear::where('marquee_id', $marqueeId)
                    ->update(['is_default' => false]);
            }

            if ($this->editId) {
                $fy = FinancialYear::findOrFail($this->editId);
                $fy->update([
                    'name' => $this->name,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'status' => $this->status,
                    'is_default' => $this->is_default,
                ]);
                session()->flash('success', 'Financial Year updated successfully.');
            } else {
                FinancialYear::create([
                    'marquee_id' => $marqueeId,
                    'name' => $this->name,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'status' => $this->status,
                    'is_default' => $this->is_default,
                ]);
                session()->flash('success', 'Financial Year created successfully.');
            }
        });

        $this->closeForm();
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $fy = FinancialYear::findOrFail($id);
        $this->editId = $fy->id;
        $this->name = $fy->name;
        $this->start_date = $fy->start_date->format('Y-m-d');
        $this->end_date = $fy->end_date->format('Y-m-d');
        $this->status = $fy->status;
        $this->is_default = $fy->is_default;
        $this->isFormOpen = true;
    }

    public function makeDefault($id)
    {
        $marqueeId = auth()->user()->marquee_id;
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $marqueeId) {
            FinancialYear::where('marquee_id', $marqueeId)->update(['is_default' => false]);

            $fy = FinancialYear::findOrFail($id);
            $fy->update([
                'is_default' => true,
                'status' => 'active'
            ]);
        });

        session()->flash('success', 'Default Financial Year changed successfully.');
    }

    public function render()
    {
        $financialYears = FinancialYear::orderBy('start_date', 'desc')
            ->paginate(10);

        return view('livewire.finance.financial-year-manager', [
            'financialYears' => $financialYears,
        ]);
    }
}
