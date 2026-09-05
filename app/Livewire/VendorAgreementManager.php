<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorService;
use Livewire\Component;

class VendorAgreementManager extends Component
{
    public ?Vendor $vendor = null;

    // Modal state
    public $showAgreementModal = false;
    public $agreementId = null;
    public $selectedVendorId = null;
    public $vendor_service_id = null;
    public $commission_type = 'percentage';
    public $commission_percentage = 15.00;
    public $fixed_commission_amount = 0.00;
    public $monthly_fixed_amount = 0.00;
    public $minimum_commission = 0.00;
    public $maximum_commission = 0.00;
    public $effective_from = '';
    public $effective_to = null;
    public $settlement_terms = 'Net 30 days after event completion';
    public $notes = '';
    public $status = 'active';

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        $id = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        if (!$id && $user?->isSuperAdmin()) {
            return \App\Models\Marquee::first()?->id;
        }
        return $id;
    }

    public function mount(?Vendor $vendor = null)
    {
        $user = auth()->user();
        if ($vendor && $user && !$user->isSuperAdmin()) {
            if ($vendor->marquee_id && !$user->hasAccessToMarquee($vendor->marquee_id)) {
                abort(403, 'Unauthorized access to this Service Provider.');
            }
        }
        $this->vendor = $vendor;
        if ($vendor) {
            $this->selectedVendorId = $vendor->id;
        }
        $this->effective_from = date('Y-m-d');
    }

    public function openCreateModal()
    {
        $this->resetForm();
        if ($this->vendor) {
            $this->selectedVendorId = $this->vendor->id;
        }
        $this->effective_from = date('Y-m-d');
        $this->showAgreementModal = true;
    }

    public function editAgreement($id)
    {
        $marqueeId = $this->getMarqueeId();
        $agr = VendorCommissionAgreement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->findOrFail($id);
        $this->agreementId = $agr->id;
        $this->selectedVendorId = $agr->vendor_id;
        $this->vendor_service_id = $agr->vendor_service_id;
        $this->commission_type = $agr->commission_type;
        $this->commission_percentage = $agr->commission_percentage;
        $this->fixed_commission_amount = $agr->fixed_commission_amount;
        $this->monthly_fixed_amount = $agr->monthly_fixed_amount;
        $this->minimum_commission = $agr->minimum_commission;
        $this->maximum_commission = $agr->maximum_commission;
        $this->effective_from = $agr->effective_from->format('Y-m-d');
        $this->effective_to = $agr->effective_to ? $agr->effective_to->format('Y-m-d') : null;
        $this->settlement_terms = $agr->settlement_terms;
        $this->notes = $agr->notes;
        $this->status = $agr->status;

        $this->showAgreementModal = true;
    }

    public function saveAgreement()
    {
        $marqueeId = $this->getMarqueeId();

        $this->validate([
            'selectedVendorId' => 'required|exists:vendors,id',
            'commission_type' => 'required|string|in:percentage,fixed_per_event,fixed_monthly,hybrid',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'fixed_commission_amount' => 'nullable|numeric|min:0',
            'monthly_fixed_amount' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|string|in:active,expired,draft,terminated',
        ]);

        Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->findOrFail($this->selectedVendorId);

        if ($this->agreementId) {
            VendorCommissionAgreement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->findOrFail($this->agreementId);
        }

        VendorCommissionAgreement::updateOrCreate(
            ['id' => $this->agreementId, 'marquee_id' => $marqueeId],
            [
                'vendor_id' => $this->selectedVendorId,
                'vendor_service_id' => $this->vendor_service_id ?: null,
                'commission_type' => $this->commission_type,
                'commission_percentage' => floatval($this->commission_percentage),
                'fixed_commission_amount' => floatval($this->fixed_commission_amount),
                'monthly_fixed_amount' => floatval($this->monthly_fixed_amount),
                'minimum_commission' => floatval($this->minimum_commission),
                'maximum_commission' => floatval($this->maximum_commission),
                'effective_from' => $this->effective_from,
                'effective_to' => $this->effective_to ?: null,
                'settlement_terms' => $this->settlement_terms,
                'notes' => $this->notes,
                'status' => $this->status,
            ]
        );

        $this->showAgreementModal = false;
        $this->resetForm();
        session()->flash('success', 'Commission agreement saved successfully.');
    }

    public function resetForm()
    {
        $this->agreementId = null;
        $this->vendor_service_id = null;
        $this->commission_type = 'percentage';
        $this->commission_percentage = 15.00;
        $this->fixed_commission_amount = 0.00;
        $this->monthly_fixed_amount = 0.00;
        $this->minimum_commission = 0.00;
        $this->maximum_commission = 0.00;
        $this->effective_from = date('Y-m-d');
        $this->effective_to = null;
        $this->settlement_terms = 'Net 30 days after event completion';
        $this->notes = '';
        $this->status = 'active';
        if ($this->vendor) {
            $this->selectedVendorId = $this->vendor->id;
        } else {
            $this->selectedVendorId = null;
        }
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $query = VendorCommissionAgreement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'service']);
        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        }

        $agreements = $query->orderBy('effective_from', 'desc')->get();
        $vendors = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $services = $this->selectedVendorId
            ? VendorService::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('vendor_id', $this->selectedVendorId)->get()
            : collect();

        return view('livewire.vendor-agreement-manager', [
            'agreements' => $agreements,
            'vendors' => $vendors,
            'services' => $services,
        ])->layout('layouts.admin');
    }
}
