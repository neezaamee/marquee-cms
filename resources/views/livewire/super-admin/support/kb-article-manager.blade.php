<div>
  <!-- Page Header -->
  <div class="card mb-3 shadow-sm">
    <div class="card-body">
      <div class="row flex-between-center">
        <div class="col-sm-auto mb-2 mb-sm-0">
          <h5 class="mb-0 text-primary fw-bold">
            <i class="fas fa-book-open me-2"></i>Knowledge Base Documentation Management
          </h5>
          <p class="fs-10 text-600 mb-0">Author, review, and maintain system guides, operational walkthroughs, and internal support manuals.</p>
        </div>
        <div class="col-sm-auto">
          <button class="btn btn-sm btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus-circle me-1"></i>New Article
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Flash Messages -->
  @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show fs-10 py-2 px-3 mb-3 shadow-sm" role="alert">
      <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Filters & Search Toolbar -->
  <div class="card mb-3 shadow-sm">
    <div class="card-body py-2">
      <div class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-400"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Search titles, summaries, tags..." wire:model.live.debounce.300ms="search" />
          </div>
        </div>
        <div class="col-md-4">
          <select class="form-select form-select-sm" wire:model.live="categoryFilter">
            <option value="">All Categories</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" wire:model.live="statusFilter">
            <option value="">All Statuses</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="review">Under Review</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Articles Table Card -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0 fs-10">
          <thead class="bg-200">
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th class="text-center" style="width: 100px;">Audience</th>
              <th class="text-center" style="width: 110px;">Metrics</th>
              <th class="text-center" style="width: 90px;">Status</th>
              <th>Last Updated</th>
              <th class="text-end" style="width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($articles as $article)
              <tr>
                <td>
                  <div class="fw-semibold text-dark">{{ $article->title }}</div>
                  <div class="text-500 fs-11 text-truncate" style="max-width: 380px;">
                    <code>/{{ $article->slug }}</code> &bull; {{ $article->summary }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-subtle-primary text-primary fs-11">
                    {{ $article->category ? $article->category->name : 'Uncategorized' }}
                  </span>
                </td>
                <td class="text-center">
                  <button type="button" 
                          class="btn btn-sm btn-link p-0 text-decoration-none" 
                          wire:click="toggleInternal({{ $article->id }})"
                          title="Toggle Internal vs Tenant Public">
                    @if($article->is_internal_only)
                      <span class="badge bg-subtle-danger text-danger"><i class="fas fa-lock me-1"></i>Internal</span>
                    @else
                      <span class="badge bg-subtle-success text-success"><i class="fas fa-globe me-1"></i>Public</span>
                    @endif
                  </button>
                </td>
                <td class="text-center fs-11 text-600">
                  <span title="Views"><i class="fas fa-eye me-1"></i>{{ $article->views_count }}</span> &bull; 
                  <span class="text-success" title="Helpful votes"><i class="fas fa-thumbs-up me-1"></i>{{ $article->helpful_count }}</span>
                </td>
                <td class="text-center">
                  @if($article->status === 'published')
                    <span class="badge bg-subtle-success text-success">Published</span>
                  @elseif($article->status === 'draft')
                    <span class="badge bg-subtle-secondary text-secondary">Draft</span>
                  @elseif($article->status === 'review')
                    <span class="badge bg-subtle-warning text-warning">Review</span>
                  @else
                    <span class="badge bg-subtle-dark text-dark">{{ ucfirst($article->status) }}</span>
                  @endif
                </td>
                <td class="text-nowrap fs-11 text-500">{{ $article->updated_at->format('Y-m-d H:i') }}</td>
                <td class="text-end text-nowrap">
                  <a href="{{ route('support.article.show', $article->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1" title="Preview Article">
                    <i class="fas fa-external-link-alt"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" wire:click="editArticle({{ $article->id }})" title="Edit Article">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger py-0 px-2" wire:click="deleteArticle({{ $article->id }})" onclick="return confirm('Are you sure you want to delete this article?') || event.stopImmediatePropagation()" title="Delete Article">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  <i class="fas fa-book-open fa-2x mb-2 text-300"></i>
                  <p class="mb-0">No knowledge base articles match the current filter.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($articles->hasPages())
      <div class="card-footer bg-body-tertiary py-2">
        {{ $articles->links() }}
      </div>
    @endif
  </div>

  <!-- Create / Edit Article Modal -->
  @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow-lg border">
          <div class="modal-header bg-body-tertiary">
            <h5 class="modal-title fw-bold">
              <i class="fas {{ $editingArticleId ? 'fa-edit' : 'fa-plus-circle' }} me-2 text-primary"></i>
              {{ $editingArticleId ? 'Edit Knowledge Base Article' : 'Create New Knowledge Base Article' }}
            </h5>
            <button type="button" class="btn-close" wire:click="closeModal"></button>
          </div>
          <form wire:submit.prevent="saveArticle">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label fw-semibold fs-10">Article Title <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm @error('title') is-invalid @enderror" wire:model.live.debounce.400ms="title" placeholder="e.g. Complete Guide to Multi-Tenant Marquee Setup" />
                  @error('title') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-10">URL Slug <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm @error('slug') is-invalid @enderror" wire:model="slug" placeholder="guide-to-marquee-setup" />
                  @error('slug') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-10">Category <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm @error('categoryId') is-invalid @enderror" wire:model="categoryId">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                      <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                  </select>
                  @error('categoryId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-10">Publication Status</label>
                  <select class="form-select form-select-sm" wire:model="status">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="review">Under Review</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-10">Estimated Reading Time (Minutes)</label>
                  <input type="number" class="form-control form-control-sm" wire:model="readingTimeMinutes" min="1" max="60" />
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold fs-10">Brief Summary (Callout text)</label>
                  <textarea class="form-control fs-10 @error('summary') is-invalid @enderror" rows="2" wire:model="summary" placeholder="One or two sentences explaining the purpose of this guide..."></textarea>
                  @error('summary') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold fs-10">Full Article Content (HTML / Rich text) <span class="text-danger">*</span></label>
                  <textarea class="form-control fs-10 @error('content') is-invalid @enderror" rows="10" wire:model="content" placeholder="<h3>Heading</h3><p>Detailed step-by-step documentation...</p>"></textarea>
                  @error('content') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-8">
                  <label class="form-label fw-semibold fs-10">Tags (Comma-separated)</label>
                  <input type="text" class="form-control form-control-sm" wire:model="tags" placeholder="setup,booking,finance,tax" />
                </div>

                <div class="col-md-4">
                  <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" id="articleInternalSwitch" wire:model="isInternalOnly" />
                    <label class="form-check-label fs-10 fw-semibold" for="articleInternalSwitch">
                      Internal Staff Only (Hide from Tenants)
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
              <button type="button" class="btn btn-sm btn-secondary" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-save me-1"></i>{{ $editingArticleId ? 'Update Article' : 'Save Article' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif
</div>
