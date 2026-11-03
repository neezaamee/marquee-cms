<?php

namespace App\Livewire\Support;

use App\Models\FeatureRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class FeatureRequestPortal extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = 'all';
    public $moduleFilter = 'all';

    // New Idea Form in Modal
    public $newTitle = '';
    public $newModule = 'general';
    public $newDescription = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'moduleFilter' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingModuleFilter() { $this->resetPage(); }

    public function toggleVote(int $featureId)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $feature = FeatureRequest::findOrFail($featureId);
        $voted = $feature->toggleVote($user->id, $user->getActiveMarqueeId());

        $msg = $voted ? "Vote recorded for '{$feature->title}'." : "Vote removed from '{$feature->title}'.";
        session()->flash('success', $msg);
    }

    public function submitIdea()
    {
        $this->validate([
            'newTitle' => 'required|string|min:5|max:150',
            'newModule' => 'required|string|max:50',
            'newDescription' => 'required|string|min:15|max:3000',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($user) {
            $feature = FeatureRequest::create([
                'marquee_id' => $user->getActiveMarqueeId(),
                'user_id' => $user->id,
                'title' => $this->newTitle,
                'module' => $this->newModule,
                'description' => $this->newDescription,
                'status' => 'submitted',
                'votes_count' => 1,
            ]);

            // Automatically record author's initial vote
            $feature->votes()->create([
                'user_id' => $user->id,
                'marquee_id' => $user->getActiveMarqueeId(),
            ]);
        });

        $this->reset(['newTitle', 'newModule', 'newDescription']);
        $this->dispatch('close-modal');

        session()->flash('success', 'Your feature suggestion has been published to the community roadmap!');
    }

    public function render()
    {
        $query = FeatureRequest::with(['creator', 'votes'])
            ->withCount('votes');

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->moduleFilter !== 'all') {
            $query->where('module', $this->moduleFilter);
        }

        $features = $query->orderBy('votes_count', 'desc')
            ->latest()
            ->paginate(12);

        // Calculate roadmap counts
        $base = FeatureRequest::query();
        $countPlanned = (clone $base)->where('status', 'planned')->count();
        $countInDev = (clone $base)->where('status', 'in_development')->count();
        $countReleased = (clone $base)->where('status', 'released')->count();

        return view('livewire.support.feature-requests', [
            'features' => $features,
            'countPlanned' => $countPlanned,
            'countInDev' => $countInDev,
            'countReleased' => $countReleased,
            'currentUserId' => Auth::id(),
        ])->layout('layouts.admin', ['title' => 'Feature Requests & Roadmap']);
    }
}

