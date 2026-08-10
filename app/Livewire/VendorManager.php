<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;

class VendorManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterType = '';
    public $filterStatus = '';

    // Modal State
    public $showVendorModal = false;
    public $vendorId = null;
    public $name = '';
    public $vendor_type = 'Florist';
    public $contact_person = '';
    public $phone = '';
    public $alternate_phone = '';
    public $email = '';
    public $address = '';
    public $city = 'Lahore';
    public $branch_id = null;
    public $tax_ntn = '';
    public $bank_name = '';
    public $account_title = '';
    public $account_number_iban = '';
    public $payment_terms = 'Net 30';
    public $notes = '';
    public $opening_balance = 0.00;
    public $status = 'active';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showVendorModal = true;
    }

    public function editVendor($id)
    {
        $vendor = Vendor::where('marquee_id', auth()->user()->marquee_id)->findOrFail($id);
        $this->vendorId = $vendor->id;
        $this->name = $vendor->name;
        $this->vendor_type = $vendor->vendor_type;
        $this->contact_person = $vendor->contact_person;
        $this->phone = $vendor->phone;
        $this->alternate_phone = $vendor->alternate_phone;
        $this->email = $vendor->email;
        $this->address = $vendor->address;
        $this->city = $vendor->city;
        $this->branch_id = $vendor->branch_id;
        $this->tax_ntn = $vendor->tax_ntn;
        $this->bank_name = $vendor->bank_name;
        $this->account_title = $vendor->account_title;
        $this->account_number_iban = $vendor->account_number_iban;
        $this->payment_terms = $vendor->payment_terms;
        $this->notes = $vendor->notes;
        $this->opening_balance = $vendor->opening_balance;
        $this->status = $vendor->status;

        $this->showVendorModal = true;
    }

    public function saveVendor()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'vendor_type' => 'required|string',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'required|string|in:active,inactive,suspended',
        ]);

        $marqueeId = auth()->user()->marquee_id;

        if ($this->vendorId) {
            Vendor::where('marquee_id', $marqueeId)->findOrFail($this->vendorId);
        }

        Vendor::updateOrCreate(
            ['id' => $this->vendorId, 'marquee_id' => $marqueeId],
            [
                'name' => $this->name,
                'vendor_type' => $this->vendor_type,
                'contact_person' => $this->contact_person,
                'phone' => $this->phone,
                'alternate_phone' => $this->alternate_phone,
                'email' => $this->email,
                'address' => $this->address,
                'city' => $this->city,
                'branch_id' => $this->branch_id ?: null,
                'tax_ntn' => $this->tax_ntn,
                'bank_name' => $this->bank_name,
                'account_title' => $this->account_title,
                'account_number_iban' => $this->account_number_iban,
                'payment_terms' => $this->payment_terms,
                'notes' => $this->notes,
                'opening_balance' => floatval($this->opening_balance),
                'status' => $this->status,
                'updated_by' => auth()->id(),
            ]
        );

        $this->showVendorModal = false;
        $this->resetForm();
        session()->flash('success', 'Vendor profile saved successfully.');
    }

    public function resetForm()
    {
        $this->vendorId = null;
        $this->name = '';
        $this->vendor_type = 'Florist';
        $this->contact_person = '';
        $this->phone = '';
        $this->alternate_phone = '';
        $this->email = '';
        $this->address = '';
        $this->city = 'Lahore';
        $this->branch_id = null;
        $this->tax_ntn = '';
        $this->bank_name = '';
        $this->account_title = '';
        $this->account_number_iban = '';
        $this->payment_terms = 'Net 30';
        $this->notes = '';
        $this->opening_balance = 0.00;
        $this->status = 'active';
    }

    public function render()
    {
        $query = Vendor::query();

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('vendor_code', 'like', $term)
                  ->orWhere('contact_person', 'like', $term)
                  ->orWhere('phone', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        if (!empty($this->filterType)) {
            $query->where('vendor_type', $this->filterType);
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $vendors = $query->orderBy('name')->paginate(12);
        $branches = Branch::where('status', 'active')->get();

        $vendorTypes = [
            'Florist', 'Sound System', 'Photography', 'Videography', 'Decoration',
            'DJ', 'Makeup Artist', 'Event Planner', 'Transport', 'Furniture Rental',
            'Generator', 'Caterer', 'Security', 'Other'
        ];

        return view('livewire.vendor-manager', [
            'vendors' => $vendors,
            'branches' => $branches,
            'vendorTypes' => $vendorTypes,
        ])->layout('layouts.admin');
    }
}
