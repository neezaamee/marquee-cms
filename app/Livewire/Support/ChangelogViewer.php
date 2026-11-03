<?php

namespace App\Livewire\Support;

use App\Models\ProductChangelog;
use Livewire\Component;
use Livewire\WithPagination;

class ChangelogViewer extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $typeFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingTypeFilter() { $this->resetPage(); }

    public function render()
    {
        $query = ProductChangelog::published();

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('version', 'LIKE', $term)
                  ->orWhere('title', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }

        if ($this->typeFilter !== 'all') {
            $query->where('release_type', $this->typeFilter);
        }

        $changelogs = $query->orderBy('release_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.support.changelog-viewer', [
            'changelogs' => $changelogs,
        ])->layout('layouts.admin', ['title' => 'Product Changelog & Updates']);
    }
}
