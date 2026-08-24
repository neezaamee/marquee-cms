<?php

namespace App\Livewire\SuperAdmin\Trials;

use App\Models\User;
use App\Models\SubscriptionPlan;
use Livewire\Component;
use Livewire\WithPagination;

class TrialConversions extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = User::whereHas('role', function($q) {
            $q->whereIn('name', ['owner', 'business_owner']);
        })
        ->whereNotNull('subscription_trial_ends_at')
        // Converted: trial ends in the past or present, and they have an active paid subscription end date in the future
        ->where('subscription_ends_at', '>', now());

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('username', 'like', '%' . $this->search . '%');
            });
        }

        $conversions = $query->orderBy('subscription_ends_at', 'desc')->paginate(10);

        return view('livewire.super-admin.trials.trial-conversions', [
            'conversions' => $conversions,
        ])->layout('layouts.admin');
    }
}
