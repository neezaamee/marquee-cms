<?php

namespace App\Livewire\SuperAdmin;

use App\Models\User;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use Livewire\Component;

class BusinessOwnerDetail extends Component
{
    public User $businessOwner;
    public $activeTab = 'overview';

    public function mount($id)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        
        $this->businessOwner = User::whereHas('role', function($query) {
            $query->whereIn('name', ['owner', 'business_owner']);
        })->findOrFail($id);
    }

    public function setTab($tabName)
    {
        $this->activeTab = $tabName;
    }

    public function render()
    {
        // Load relationships: ownedMarquees and their branches, subscriptionPlan
        $this->businessOwner->load([
            'subscriptionPlan', 
            'ownedMarquees.branches', 
            'ownedMarquees.users'
        ]);

        // Get invoices and payments for this owner directly
        $invoices = SaasInvoice::where('user_id', $this->businessOwner->id)
            ->where('invoice_status', '!=', 'Cancelled')
            ->with(['subscriptionPlan', 'billingCycle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $payments = SaasPayment::where('user_id', $this->businessOwner->id)
            ->with(['invoice'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.super-admin.business-owner-detail', [
            'invoices' => $invoices,
            'payments' => $payments,
        ])->layout('layouts.admin');
    }
}
