<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Marquee;
use Livewire\Component;

class TaxConfiguration extends Component
{
    // Branches indexed by ID with editable tax fields
    public array $branchData = [];

    // If super admin, allow marquee switching
    public $selectedMarqueeId = null;
    public $marquees = [];

    // Feedback
    public $savedBranchId = null;

    protected function rules(): array
    {
        $rules = [];
        foreach ($this->branchData as $id => $data) {
            $rules["branchData.{$id}.tax_rate"] = 'nullable|numeric|min:0|max:100';
            $rules["branchData.{$id}.fbr_pos_id"] = 'nullable|string|max:100';
            $rules["branchData.{$id}.fbr_pos_key"] = 'nullable|string|max:255';
            $rules["branchData.{$id}.fbr_sandbox_mode"] = 'nullable|boolean';
            $rules["branchData.{$id}.pos_connection_type"] = 'nullable|string|in:cloud,local';
        }
        return $rules;
    }

    public function mount()
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasPermission('manage_settings'),
            403,
            'You do not have permission to manage tax configuration.'
        );

        if ($user->isSuperAdmin() || $user->isBusinessOwner()) {
            $this->marquees = $user->getAccessibleMarquees();
            $this->selectedMarqueeId = $user->getActiveMarqueeId() ?: $this->marquees->first()?->id;
        } else {
            $this->selectedMarqueeId = $user->getActiveMarqueeId() ?: $user->marquee_id;
        }

        $this->loadBranches();
    }

    public function updatedSelectedMarqueeId()
    {
        $this->loadBranches();
    }

    protected function loadBranches(): void
    {
        $this->branchData = [];
        $branches = Branch::where('marquee_id', $this->selectedMarqueeId)
            ->orderBy('is_head_office', 'desc')
            ->orderBy('name')
            ->get();

        foreach ($branches as $branch) {
            $this->branchData[$branch->id] = [
                'name' => $branch->name,
                'is_head_office' => (bool) $branch->is_head_office,
                'tax_rate' => $branch->tax_rate ?? 0.00,
                'fbr_pos_id' => $branch->fbr_pos_id ?? '',
                'fbr_pos_key' => $branch->fbr_pos_key ?? '',
                'fbr_sandbox_mode' => (bool) ($branch->fbr_sandbox_mode ?? false),
                'pos_connection_type' => $branch->pos_connection_type ?? 'cloud',
            ];
        }
    }

    public function saveBranch(int $branchId): void
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasPermission('manage_settings'),
            403
        );

        $this->validateOnly("branchData.{$branchId}.*");

        $data = $this->branchData[$branchId] ?? null;
        if (!$data) return;

        $branch = Branch::findOrFail($branchId);

        // Security: ensure branch can be updated by user
        abort_unless(
            $user->can('update', $branch),
            403,
            'Unauthorized operation.'
        );

        $branch->update([
            'tax_rate' => $data['tax_rate'],
            'fbr_pos_id' => $data['fbr_pos_id'] ?: null,
            'fbr_pos_key' => $data['fbr_pos_key'] ?: null,
            'fbr_sandbox_mode' => (bool) ($data['fbr_sandbox_mode'] ?? false),
            'pos_connection_type' => $data['pos_connection_type'] ?? 'cloud',
        ]);

        $this->savedBranchId = $branchId;
        session()->flash('success', "Tax settings for \"{$branch->name}\" saved successfully.");
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $canSwitchMarquee = $isSuperAdmin || ($user->isBusinessOwner() && count($this->marquees) > 1);
        $selectedMarquee = Marquee::find($this->selectedMarqueeId);
        $taxAuthority = strtoupper($selectedMarquee?->tax_authority ?? 'FBR');

        return view('livewire.finance.tax-configuration', compact('isSuperAdmin', 'canSwitchMarquee', 'taxAuthority'))
            ->layout('layouts.admin');
    }
}
