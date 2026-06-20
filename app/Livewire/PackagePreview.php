<?php

namespace App\Livewire;

use App\Models\Package;
use App\Services\PackagePricingService;
use Livewire\Component;

class PackagePreview extends Component
{
    public Package $package;
    public $previewGuests = 100;

    public function mount(Package $package)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('view_packages'), 403);

        if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
            abort(403);
        }

        $this->package = $package;
        $this->previewGuests = $package->minimum_guests ?: 150;
    }

    public function render(PackagePricingService $service)
    {
        // Calculate quote simulation metrics
        $quoteDetails = $service->calculateQuote($this->package->id, (int)$this->previewGuests);

        // Fetch package items grouped by category for nice preview
        $packageItems = $this->package->menuItems()->with('category')->get();

        return view('livewire.package-preview', compact('quoteDetails', 'packageItems'));
    }
}
