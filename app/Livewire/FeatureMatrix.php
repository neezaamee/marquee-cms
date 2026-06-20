<?php

namespace App\Livewire;

use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class FeatureMatrix extends Component
{
    public $plans = [];
    public $features = [];
    
    // Matrix state: matrix[plan_id][feature_id] = [enabled => true/false, limit_value => '']
    public $matrix = [];

    public function mount()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->loadMatrix();
    }

    public function loadMatrix()
    {
        $this->plans = SubscriptionPlan::orderBy('sort_order')->orderBy('name')->get();
        $this->features = PlanFeature::where('status', 'Active')->orderBy('feature_name')->get();

        $this->matrix = [];
        foreach ($this->plans as $plan) {
            $mappedFeatures = $plan->planFeatures()->get()->keyBy('id');
            $this->matrix[$plan->id] = [];

            foreach ($this->features as $feature) {
                $isMapped = $mappedFeatures->has($feature->id);
                $limitValue = $isMapped ? $mappedFeatures->get($feature->id)->pivot->limit_value : '';

                $this->matrix[$plan->id][$feature->id] = [
                    'enabled' => $isMapped,
                    'limit_value' => $limitValue,
                ];
            }
        }
    }

    public function saveMatrix()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        DB::transaction(function () {
            foreach ($this->plans as $plan) {
                $syncData = [];
                if (isset($this->matrix[$plan->id])) {
                    foreach ($this->matrix[$plan->id] as $featureId => $data) {
                        if ($data['enabled']) {
                            $syncData[$featureId] = [
                                'limit_value' => $data['limit_value'] ?: 'Unlimited'
                            ];
                        }
                    }
                }
                $plan->planFeatures()->sync($syncData);
            }
        });

        session()->flash('success', 'Plan Features Matrix updated successfully.');
        $this->loadMatrix();
    }

    public function render()
    {
        return view('livewire.feature-matrix');
    }
}
