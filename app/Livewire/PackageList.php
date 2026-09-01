<?php

namespace App\Livewire;

use App\Models\Package;
use App\Services\PackagePricingService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class PackageList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    protected $paginationTheme = 'bootstrap';

    // Package Duplication Fields
    public $clonePackageId = null;
    public $cloneName = '';
    public $cloneCode = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public $confirmingDeletionId = null;

    /**
     * Set package ID for deletion confirmation.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete Package.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_packages'), 403);

        if ($this->confirmingDeletionId) {
            $package = Package::findOrFail($this->confirmingDeletionId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if package is referenced in bookings
            if (\App\Models\Booking::where('package_id', $package->id)->exists()) {
                session()->flash('error', 'Cannot delete this package because it is attached to existing bookings. Set its status to Inactive or Archived instead.');
                $this->confirmingDeletionId = null;
                return;
            }

            $package->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Package deleted successfully.');
        }
    }

    /**
     * Setup package for cloning.
     */
    public function setupClone(int $id)
    {
        $package = Package::findOrFail($id);
        $this->clonePackageId = $id;
        $this->cloneName = $package->package_name . ' (Copy)';
        $this->cloneCode = $package->package_code . '-COPY';
        $this->resetValidation();
    }

    /**
     * Perform package cloning.
     */
    public function clonePackage(PackagePricingService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_packages'), 403);

        $marqueeId = auth()->user()->marquee_id;

        $this->validate([
            'cloneName' => 'required|string|max:255',
            'cloneCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('packages', 'package_code')
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
        ], [
            'cloneCode.unique' => 'This package code is already registered in your Marquee database.',
        ]);

        if ($this->clonePackageId) {
            $sourcePackage = Package::findOrFail($this->clonePackageId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $sourcePackage->marquee_id !== $marqueeId) {
                session()->flash('error', 'Unauthorized operation.');
                return;
            }

            $service->clonePackage($this->clonePackageId, $this->cloneName, $this->cloneCode);

            $this->clonePackageId = null;
            $this->cloneName = '';
            $this->cloneCode = '';

            session()->flash('success', 'Package cloned successfully as a Draft. You can now configure its contents.');
        }
    }

    public function render()
    {
        $query = Package::with(['creator', 'menuItems']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('package_name', 'like', '%' . $this->search . '%')
                  ->orWhere('package_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterType)) {
            $query->where('package_type', $this->filterType);
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $packages = $query->orderBy('package_name', 'asc')
            ->paginate(15);

        return view('livewire.package-list', compact('packages'));
    }
}
