<?php

namespace App\Livewire;

use App\Models\BillingCycle;
use Livewire\Component;

class BillingCycleForm extends Component
{
    public $isEditMode = false;
    public $cycleId = null;

    // Fields
    public $cycle_name = '';
    public $duration_in_months = 1;
    public $discount_percentage = 0.00;
    public $status = 'Active';

    public function mount($cycle = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($cycle) {
            $this->isEditMode = true;
            $this->cycleId = $cycle->id;
            $this->cycle_name = $cycle->cycle_name;
            $this->duration_in_months = $cycle->duration_in_months;
            $this->discount_percentage = $cycle->discount_percentage;
            $this->status = $cycle->status;
        }
    }

    protected function rules()
    {
        return [
            'cycle_name' => 'required|string|max:255',
            'duration_in_months' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:Active,Inactive',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validatedData = $this->validate();

        if ($this->isEditMode) {
            $cycle = BillingCycle::findOrFail($this->cycleId);
            $cycle->update($validatedData);
            session()->flash('success', 'Billing cycle updated successfully.');
        } else {
            BillingCycle::create($validatedData);
            session()->flash('success', 'Billing cycle defined successfully.');
        }

        return redirect()->route('billing-cycles.index');
    }

    public function render()
    {
        return view('livewire.billing-cycle-form');
    }
}
