<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\Announcement;
use App\Models\Marquee;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    // Create / Edit Modal State
    public $editingId = null;
    public $title = '';
    public $message = '';
    public $severity = 'info';
    public $start_date = '';
    public $end_date = '';
    public $is_published = true;
    public $target_audience = 'all';
    public $target_ids = [];
    public $requires_acknowledgment = false;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:150',
            'message' => 'required|string|min:10|max:2000',
            'severity' => 'required|in:info,warning,critical,success',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_published' => 'boolean',
            'target_audience' => 'required|in:all,selected_tenants,selected_roles',
            'target_ids' => 'nullable|array',
            'requires_acknowledgment' => 'boolean',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.announcement.manage')) {
            abort(403, 'Unauthorized access to Announcements Management.');
        }

        $this->start_date = Carbon::now()->format('Y-m-d\TH:i');
        $this->end_date = Carbon::now()->addDays(7)->format('Y-m-d\TH:i');
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->title = '';
        $this->message = '';
        $this->severity = 'info';
        $this->start_date = Carbon::now()->format('Y-m-d\TH:i');
        $this->end_date = Carbon::now()->addDays(7)->format('Y-m-d\TH:i');
        $this->is_published = true;
        $this->target_audience = 'all';
        $this->target_ids = [];
        $this->requires_acknowledgment = false;
    }

    public function edit(int $id)
    {
        $ann = Announcement::findOrFail($id);
        $this->editingId = $ann->id;
        $this->title = $ann->title;
        $this->message = $ann->message;
        $this->severity = $ann->severity;
        $this->start_date = $ann->start_date ? $ann->start_date->format('Y-m-d\TH:i') : '';
        $this->end_date = $ann->end_date ? $ann->end_date->format('Y-m-d\TH:i') : '';
        $this->is_published = $ann->is_published;
        $this->target_audience = $ann->target_audience;
        $this->target_ids = $ann->target_ids ?: [];
        $this->requires_acknowledgment = $ann->requires_acknowledgment;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'start_date' => !empty($this->start_date) ? $this->start_date : null,
            'end_date' => !empty($this->end_date) ? $this->end_date : null,
            'is_published' => (bool) $this->is_published,
            'target_audience' => $this->target_audience,
            'target_ids' => $this->target_ids,
            'requires_acknowledgment' => (bool) $this->requires_acknowledgment,
        ];

        if ($this->editingId) {
            $ann = Announcement::findOrFail($this->editingId);
            $ann->update($data);
            session()->flash('success', "Announcement updated successfully.");
        } else {
            $data['created_by'] = Auth::id();
            Announcement::create($data);
            session()->flash('success', "Announcement published successfully.");
        }

        $this->resetForm();
    }

    public function togglePublish(int $id)
    {
        $ann = Announcement::findOrFail($id);
        $ann->update(['is_published' => !$ann->is_published]);

        $status = $ann->is_published ? 'published' : 'unpublished';
        session()->flash('success', "Announcement is now {$status}.");
    }

    public function delete(int $id)
    {
        $ann = Announcement::findOrFail($id);
        $ann->delete();

        session()->flash('success', "Announcement deleted.");
    }

    public function render()
    {
        $query = Announcement::with('creator')->withCount('acknowledgments');

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', $term)
                  ->orWhere('message', 'LIKE', $term);
            });
        }

        $announcements = $query->latest('start_date')->paginate(10);

        $marquees = Marquee::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('livewire.super-admin.support.announcement-manager', [
            'announcements' => $announcements,
            'marquees' => $marquees,
            'roles' => $roles,
        ])->layout('layouts.admin', ['title' => 'Announcements Management — Operations']);
    }
}
