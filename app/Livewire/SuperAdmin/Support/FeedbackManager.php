<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\FeatureRequest;
use App\Models\SupportFeedback;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $activeTab = 'feedback'; // 'feedback' or 'features'
    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';

    // Feedback Moderation Modal State
    public $editingFeedbackId = null;
    public $feedbackStatus = '';
    public $feedbackAdminResponse = '';

    // Feature Request Moderation Modal State
    public $editingFeatureId = null;
    public $featureStatus = '';
    public $featurePublicNotes = '';
    public $featureAdminNotes = '';
    public $featureTargetRelease = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.feedback.manage')) {
            abort(403, 'Unauthorized access to Feedback & Roadmap Moderation.');
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingTypeFilter() { $this->resetPage(); }
    public function updatingActiveTab() { $this->resetPage(); }

    public function editFeedback(int $id)
    {
        $fb = SupportFeedback::withoutGlobalScopes()->findOrFail($id);
        $this->editingFeedbackId = $fb->id;
        $this->feedbackStatus = $fb->status;
        $this->feedbackAdminResponse = $fb->admin_response;
    }

    public function saveFeedback()
    {
        if (!$this->editingFeedbackId) {
            return;
        }

        $fb = SupportFeedback::withoutGlobalScopes()->findOrFail($this->editingFeedbackId);
        $fb->update([
            'status' => $this->feedbackStatus,
            'admin_response' => $this->feedbackAdminResponse,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now(),
        ]);

        $this->editingFeedbackId = null;
        session()->flash('success', 'Customer feedback updated successfully.');
    }

    public function editFeature(int $id)
    {
        $feat = FeatureRequest::findOrFail($id);
        $this->editingFeatureId = $feat->id;
        $this->featureStatus = $feat->status;
        $this->featurePublicNotes = $feat->public_notes;
        $this->featureAdminNotes = $feat->admin_notes;
        $this->featureTargetRelease = $feat->target_release;
    }

    public function saveFeature()
    {
        if (!$this->editingFeatureId) {
            return;
        }

        $feat = FeatureRequest::findOrFail($this->editingFeatureId);
        $updates = [
            'status' => $this->featureStatus,
            'public_notes' => $this->featurePublicNotes,
            'admin_notes' => $this->featureAdminNotes,
            'target_release' => $this->featureTargetRelease,
        ];

        if ($this->featureStatus === 'released' && !$feat->released_at) {
            $updates['released_at'] = Carbon::now();
        }

        $feat->update($updates);

        $this->editingFeatureId = null;
        session()->flash('success', 'Feature request roadmap status updated successfully.');
    }

    public function render()
    {
        // 1. Feedbacks query
        $fbQuery = SupportFeedback::withoutGlobalScopes()->with(['user', 'marquee', 'reviewer']);
        if (!empty($this->search) && $this->activeTab === 'feedback') {
            $term = '%' . trim($this->search) . '%';
            $fbQuery->where(function ($q) use ($term) {
                $q->where('subject', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term)
                  ->orWhereHas('user', fn($u) => $u->where('name', 'LIKE', $term));
            });
        }
        if ($this->statusFilter !== 'all' && $this->activeTab === 'feedback') {
            $fbQuery->where('status', $this->statusFilter);
        }
        if ($this->typeFilter !== 'all' && $this->activeTab === 'feedback') {
            $fbQuery->where('feedback_type', $this->typeFilter);
        }
        $feedbacks = $fbQuery->latest()->paginate(10);

        // 2. Features query
        $featQuery = FeatureRequest::with(['creator', 'marquee'])->withCount('votes');
        if (!empty($this->search) && $this->activeTab === 'features') {
            $term = '%' . trim($this->search) . '%';
            $featQuery->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }
        if ($this->statusFilter !== 'all' && $this->activeTab === 'features') {
            $featQuery->where('status', $this->statusFilter);
        }
        $features = $featQuery->orderBy('votes_count', 'desc')->paginate(10);

        return view('livewire.super-admin.support.feedback-manager', [
            'feedbacks' => $feedbacks,
            'features' => $features,
        ])->layout('layouts.admin', ['title' => 'Feedback & Roadmap Moderation — Operations']);
    }
}
