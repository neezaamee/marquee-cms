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
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $slot = Slot::findOrFail($id);

        // Tenant security check
        if (!auth()->user()->isSuperAdmin() && $slot->marquee_id !== auth()->user()->marquee_id) {
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
    public function deleteSlot(int $id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $slot = Slot::findOrFail($id);

        // Tenant security check
        if (!auth()->user()->isSuperAdmin() && $slot->marquee_id !== auth()->user()->marquee_id) {
            session()->flash('error', 'Unauthorized operation.');
            return;
        }

        $slot->delete();
        session()->flash('success', 'Slot deleted successfully.');
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
