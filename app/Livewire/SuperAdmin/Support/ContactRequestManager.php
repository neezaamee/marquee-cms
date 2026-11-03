<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\ContactRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ContactRequestManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = 'all';
    public $categoryFilter = 'all';

    // Inquiry Review Modal State
    public $editingId = null;
    public $contactStatus = '';
    public $assignedTo = null;
    public $internalNotes = '';
    public $followUpDate = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.contact_request.manage')) {
            abort(403, 'Unauthorized access to Contact Requests Management.');
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingCategoryFilter() { $this->resetPage(); }

    public function review(int $id)
    {
        $cr = ContactRequest::findOrFail($id);
        $this->editingId = $cr->id;
        $this->contactStatus = $cr->status;
        $this->assignedTo = $cr->assigned_to;
        $this->internalNotes = $cr->internal_notes;
        $this->followUpDate = $cr->follow_up_date ? $cr->follow_up_date->format('Y-m-d') : '';
    }

    public function saveReview()
    {
        if (!$this->editingId) {
            return;
        }

        $cr = ContactRequest::findOrFail($this->editingId);
        $cr->update([
            'status' => $this->contactStatus,
            'assigned_to' => !empty($this->assignedTo) ? $this->assignedTo : null,
            'internal_notes' => $this->internalNotes,
            'follow_up_date' => !empty($this->followUpDate) ? $this->followUpDate : null,
        ]);

        $this->editingId = null;
        session()->flash('success', "Inquiry from {$cr->name} updated successfully.");
    }

    public function render()
    {
        $query = ContactRequest::with(['marquee', 'assignedAgent']);

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)
                  ->orWhere('email', 'LIKE', $term)
                  ->orWhere('company_name', 'LIKE', $term)
                  ->orWhere('subject', 'LIKE', $term)
                  ->orWhere('message', 'LIKE', $term);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        $contacts = $query->latest()->paginate(10);

        $agents = User::whereHas('role', fn($r) => $r->whereIn('name', ['super_admin', 'support_agent', 'admin']))
            ->orWhereNull('marquee_id')
            ->orderBy('name')
            ->get();

        if ($agents->isEmpty()) {
            $agents = User::where('status', 'active')->orderBy('name')->take(20)->get();
        }

        $activeContact = $this->editingId ? ContactRequest::with(['marquee', 'assignedAgent'])->find($this->editingId) : null;

        return view('livewire.super-admin.support.contact-request-manager', [
            'contacts' => $contacts,
            'agents' => $agents,
            'activeContact' => $activeContact,
        ])->layout('layouts.admin', ['title' => 'Contact Requests & Leads — Operations']);
    }
}
