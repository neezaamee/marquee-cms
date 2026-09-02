<?php

namespace App\Livewire;

use App\Models\Slot;
use Livewire\Component;
use Livewire\WithPagination;

class SlotList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /**
     * Quick status toggle.
     */
    public function toggleStatus(int $id)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasPermission('manage_settings'), 403);

        $slot = Slot::findOrFail($id);

        // Tenant security check
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($slot->marquee_id)) {
            session()->flash('error', 'Unauthorized operation.');
            return;
        }

        $slot->status = $slot->status === 'active' ? 'inactive' : 'active';
        $slot->save();
        
        session()->flash('success', 'Slot status updated successfully.');
    }

    /**
     * Delete slot.
     */
    public $confirmingDeletionId = null;

    /**
     * Set the record ID for deletion confirmation.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete the confirmed slot.
     */
    public function deleteRecord()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasPermission('manage_settings'), 403);

        if ($this->confirmingDeletionId) {
            $slot = Slot::findOrFail($this->confirmingDeletionId);

            // Tenant security check
            if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($slot->marquee_id)) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            $slot->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Slot deleted successfully.');
        }
    }

    /**
     * Render component.
     */
    public function render()
    {
        $query = Slot::with('creator');
        $shiftSlots = $query->orderBy('start_time')->paginate(10);
 
        return view('livewire.slot-list', compact('shiftSlots'));
    }
}
