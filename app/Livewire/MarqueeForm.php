<?php

namespace App\Livewire;

use App\Models\Marquee;
use App\Models\SubscriptionPlan;
use Livewire\Component;
use Livewire\WithFileUploads;

class MarqueeForm extends Component
{
    use WithFileUploads;

    public $isEditMode = false;
    public $marqueeId = null;

    // Fields
    public $name = '';
    public $logo = null; // New uploaded logo
    public $existingLogo = null; // Current logo path
    public $address = '';
    public $city = '';
    public $province = '';
    public $phone = '';
    public $email = '';
    public $ntn = '';
    public $strn = '';
    public $tax_authority = '';
    public $status = 'active';
    public $subscription_plan_id = '';
    public $subscription_ends_at = '';

    // Lists
    public $plans = [];

    public function mount($marquee = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->plans = SubscriptionPlan::orderBy('name')->get();

        if ($marquee) {
            $this->isEditMode = true;
            $this->marqueeId = $marquee->id;
            $this->name = $marquee->name;
            $this->existingLogo = $marquee->logo;
            $this->address = $marquee->address;
            $this->city = $marquee->city;
            $this->province = $marquee->province;
            $this->phone = $marquee->phone;
            $this->email = $marquee->email;
            $this->ntn = $marquee->ntn;
            $this->strn = $marquee->strn;
            $this->tax_authority = $marquee->tax_authority;
            $this->status = $marquee->status;
            $this->subscription_plan_id = $marquee->subscription_plan_id;
            $this->subscription_ends_at = $marquee->subscription_ends_at ? $marquee->subscription_ends_at->format('Y-m-d') : '';
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048', // 2MB max
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:marquees,email,' . ($this->marqueeId ?? 'NULL'),
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'tax_authority' => 'required|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'nullable|date',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validatedData = $this->validate();

        // Handle logo upload
        if ($this->logo) {
            $path = $this->logo->store('logos', 'public');
            $validatedData['logo'] = $path;
        } else {
            // Keep current logo
            $validatedData['logo'] = $this->existingLogo;
        }

        if ($this->isEditMode) {
            $marquee = Marquee::findOrFail($this->marqueeId);
            $marquee->update($validatedData);
            session()->flash('success', 'Marquee updated successfully.');
        } else {
            Marquee::create($validatedData);
            session()->flash('success', 'Marquee created successfully.');
        }

        return redirect()->route('marquees.index');
    }

    public function render()
    {
        return view('livewire.marquee-form');
    }
}
