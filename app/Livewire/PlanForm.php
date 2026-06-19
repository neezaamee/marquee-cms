<?php

namespace App\Livewire;

use App\Models\SubscriptionPlan;
use App\Models\BillingCycle;
use Livewire\Component;
use Illuminate\Support\Str;

class PlanForm extends Component
{
    public $isEditMode = false;
    public $planId = null;

    // Fields
    public $name = '';
    public $slug = '';
    public $description = '';
    public $price = 0.00; // Deprecated/Legacy field
    public $monthly_price = 0.00;
    public $quarterly_price = 0.00;
    public $semi_annual_price = 0.00;
    public $annual_price = 0.00;
    public $currency = 'PKR';
    public $trial_days = 14;
    public $max_storage = 1024;
    public $status = 'active';
    public $sort_order = 0;
    public $is_popular = false;

    // Billing Cycle relations
    public $selectedBillingCycles = [];
    public $availableBillingCycles = [];

    public function mount($plan = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->availableBillingCycles = BillingCycle::where('status', 'Active')->get();

        if ($plan) {
            $this->isEditMode = true;
            $this->planId = $plan->id;
            $this->name = $plan->name;
            $this->slug = $plan->slug;
            $this->description = $plan->description;
            $this->price = $plan->price;
            $this->monthly_price = $plan->monthly_price;
            $this->quarterly_price = $plan->quarterly_price;
            $this->semi_annual_price = $plan->semi_annual_price;
            $this->annual_price = $plan->annual_price;
            $this->currency = $plan->currency;
            $this->trial_days = $plan->trial_days;
            $this->max_storage = $plan->max_storage;
            $this->status = $plan->status;
            $this->sort_order = $plan->sort_order;
            $this->is_popular = (bool)$plan->is_popular;
            
            $this->selectedBillingCycles = $plan->billingCycles()->pluck('billing_cycles.id')->map(fn($id) => (string)$id)->toArray();
        }
    }

    public function updatedName($value)
    {
        if (!$this->isEditMode && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug,' . ($this->planId ?? 'NULL'),
            'description' => 'nullable|string',
            'monthly_price' => 'required|numeric|min:0',
            'quarterly_price' => 'required|numeric|min:0',
            'semi_annual_price' => 'required|numeric|min:0',
            'annual_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'trial_days' => 'required|integer|min:0',
            'max_storage' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'required|integer',
            'is_popular' => 'boolean',
            'selectedBillingCycles' => 'nullable|array',
            'selectedBillingCycles.*' => 'exists:billing_cycles,id',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validatedData = $this->validate();
        
        // Sync custom pricing fields to legacy price
        $validatedData['price'] = $this->monthly_price;
        $validatedData['is_popular'] = $this->is_popular;
        
        if ($this->isEditMode) {
            $plan = SubscriptionPlan::findOrFail($this->planId);
            $validatedData['updated_by'] = auth()->id();
            $plan->update($validatedData);
            
            $plan->billingCycles()->sync($this->selectedBillingCycles);
            
            session()->flash('success', 'Subscription plan updated successfully.');
        } else {
            $validatedData['created_by'] = auth()->id();
            $plan = SubscriptionPlan::create($validatedData);
            
            if (!empty($this->selectedBillingCycles)) {
                $plan->billingCycles()->sync($this->selectedBillingCycles);
            }
            
            session()->flash('success', 'Subscription plan created successfully.');
        }

        return redirect()->route('subscription-plans.index');
    }

    public function render()
    {
        return view('livewire.plan-form');
    }
}
