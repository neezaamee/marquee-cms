<?php

namespace App\Livewire;

use App\Models\ExtraService;
use Livewire\Component;

class ExtraServiceForm extends Component
{
    public $isEditMode = false;
    public $extraServiceId = null;

    // Form Fields
    public $service_name = '';
    public $default_price = 0.00;
    public $status = 'Active';

    public function mount($extraService = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('manage_settings'), 403);

        if ($extraService) {
            $this->isEditMode = true;
            $this->extraServiceId = $extraService->id;
            $this->service_name = $extraService->service_name;
            $this->default_price = $extraService->default_price;
            $this->status = $extraService->status;
        }
    }

    protected function rules()
    {
        return [
            'service_name' => 'required|string|max:255',
            'default_price' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ];
    }

    /**
     * Save the extra service.
     */
    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('manage_settings'), 403);

        $this->validate();

        $serviceData = [
            'service_name' => $this->service_name,
            'default_price' => $this->default_price,
            'status' => $this->status,
        ];

        if ($user->marquee_id) {
            $serviceData['marquee_id'] = $user->marquee_id;
        }

        if ($this->isEditMode) {
            $extraService = ExtraService::findOrFail($this->extraServiceId);

            // Scope Check
            if (!$user->isSuperAdmin() && $extraService->marquee_id !== $user->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            $extraService->update($serviceData);
            session()->flash('success', 'Add-on updated successfully.');
        } else {
            ExtraService::create($serviceData);
            session()->flash('success', 'Add-on created successfully.');
        }

        return redirect()->route('extra-services.index');
    }

    public function render()
    {
        return view('livewire.extra-service-form');
    }
}
