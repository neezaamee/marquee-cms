<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\ProductChangelog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ChangelogManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    // Create / Edit Form State
    public $editingId = null;
    public $version = '';
    public $title = '';
    public $summary = '';
    public $release_date = '';
    public $release_type = 'improvement';
    public $affected_modules = [];
    public $description = '';
    public $is_published = true;

    protected function rules(): array
    {
        return [
            'version' => 'required|string|max:30',
            'title' => 'required|string|min:5|max:150',
            'summary' => 'nullable|string|max:300',
            'release_date' => 'required|date',
            'release_type' => 'required|in:new_feature,improvement,bug_fix,security_update,performance,maintenance',
            'affected_modules' => 'nullable|array',
            'description' => 'required|string|min:10',
            'is_published' => 'boolean',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.changelog.manage')) {
            abort(403, 'Unauthorized access to Changelog Management.');
        }

        $this->release_date = Carbon::today()->format('Y-m-d');
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->version = '';
        $this->title = '';
        $this->summary = '';
        $this->release_date = Carbon::today()->format('Y-m-d');
        $this->release_type = 'improvement';
        $this->affected_modules = [];
        $this->description = '';
        $this->is_published = true;
    }

    public function edit(int $id)
    {
        $cl = ProductChangelog::findOrFail($id);
        $this->editingId = $cl->id;
        $this->version = $cl->version;
        $this->title = $cl->title;
        $this->summary = $cl->summary;
        $this->release_date = $cl->release_date ? $cl->release_date->format('Y-m-d') : '';
        $this->release_type = $cl->release_type;
        $this->affected_modules = $cl->affected_modules ?: [];
        $this->description = $cl->description;
        $this->is_published = $cl->is_published;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'version' => $this->version,
            'title' => $this->title,
            'summary' => $this->summary,
            'release_date' => $this->release_date,
            'release_type' => $this->release_type,
            'affected_modules' => $this->affected_modules,
            'description' => $this->description,
            'is_published' => (bool) $this->is_published,
        ];

        if ($this->editingId) {
            $cl = ProductChangelog::findOrFail($this->editingId);
            $cl->update($data);
            session()->flash('success', "Changelog {$cl->version} updated successfully.");
        } else {
            $data['created_by'] = Auth::id();
            $cl = ProductChangelog::create($data);
            session()->flash('success', "Changelog {$cl->version} published successfully.");
        }

        $this->resetForm();
    }

    public function togglePublish(int $id)
    {
        $cl = ProductChangelog::findOrFail($id);
        $cl->update(['is_published' => !$cl->is_published]);

        $status = $cl->is_published ? 'published' : 'unpublished';
        session()->flash('success', "Changelog {$cl->version} is now {$status}.");
    }

    public function delete(int $id)
    {
        $cl = ProductChangelog::findOrFail($id);
        $cl->delete();

        session()->flash('success', "Changelog entry removed.");
    }

    public function render()
    {
        $query = ProductChangelog::with('creator');

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('version', 'LIKE', $term)
                  ->orWhere('title', 'LIKE', $term);
            });
        }

        $changelogs = $query->orderBy('release_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.super-admin.support.changelog-manager', [
            'changelogs' => $changelogs,
        ])->layout('layouts.admin', ['title' => 'Changelog Management — Operations']);
    }
}
