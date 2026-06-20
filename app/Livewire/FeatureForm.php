<?php

namespace App\Livewire;

use App\Models\PlanFeature;
use Livewire\Component;
use Illuminate\Support\Str;

class FeatureForm extends Component
{
    public $isEditMode = false;
    public $featureId = null;

    // Fields
    public $feature_name = '';
    public $feature_key = '';
    public $description = '';
    public $status = 'Active';

    public function mount($feature = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($feature) {
            $this->isEditMode = true;
            $this->featureId = $feature->id;
            $this->feature_name = $feature->feature_name;
            $this->feature_key = $feature->feature_key;
            $this->description = $feature->description;
            $this->status = $feature->status;
        }
    }

    public function updatedFeatureName($value)
    {
        if (!$this->isEditMode && empty($this->feature_key)) {
            $this->feature_key = Str::slug($value, '_');
        }
    }

    protected function rules()
    {
        return [
            'feature_name' => 'required|string|max:255',
            'feature_key' => 'required|string|max:255|unique:plan_features,feature_key,' . ($this->featureId ?? 'NULL'),
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validatedData = $this->validate();

        if ($this->isEditMode) {
            $feature = PlanFeature::findOrFail($this->featureId);
            $feature->update($validatedData);
            session()->flash('success', 'Plan feature updated successfully.');
        } else {
            PlanFeature::create($validatedData);
            session()->flash('success', 'Plan feature defined successfully.');
        }

        return redirect()->route('plan-features.index');
    }

    public function render()
    {
        return view('livewire.feature-form');
    }
}
