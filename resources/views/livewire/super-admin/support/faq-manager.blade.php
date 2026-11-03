<div>
  <!-- Page Header -->
  <div class="card mb-3 shadow-sm">
    <div class="card-body">
      <div class="row flex-between-center">
        <div class="col-sm-auto mb-2 mb-sm-0">
          <h5 class="mb-0 text-primary fw-bold">
            <i class="fas fa-question-circle me-2"></i>Frequently Asked Questions Management
          </h5>
          <p class="fs-10 text-600 mb-0">Publish, organize into categories, and maintain customer-facing knowledge answers.</p>
        </div>
        <div class="col-sm-auto">
          <button class="btn btn-sm btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus-circle me-1"></i>Add New FAQ
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
            <input type="text" class="form-control border-start-0" placeholder="Search questions, answers, tags..." wire:model.live.debounce.300ms="search" />
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
          <select class="form-select form-select-sm" wire:model.live="publishedFilter">
            <option value="">All Statuses</option>
            <option value="1">Published Only</option>
            <option value="0">Drafts Only</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- FAQs Table Card -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0 fs-10">
          <thead class="bg-200">
            <tr>
              <th style="width: 50px;">Order</th>
              <th>Question</th>
              <th>Category</th>
              <th class="text-center" style="width: 100px;">Stats</th>
              <th class="text-center" style="width: 80px;">Featured</th>
              <th class="text-center" style="width: 90px;">Status</th>
              <th class="text-end" style="width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($faqs as $faq)
              <tr>
                <td class="fw-bold text-center text-700">{{ $faq->display_order }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ $faq->question }}</div>
                  <div class="text-500 fs-11 text-truncate" style="max-width: 450px;">
                    {{ strip_tags($faq->answer) }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-subtle-primary text-primary fs-11">
                    {{ $faq->category ? $faq->category->name : '-' }}
                  </span>
                </td>
                <td class="text-center fs-11 text-600">
                  <span title="Views"><i class="fas fa-eye me-1"></i>{{ $faq->views_count }}</span> &bull; 
                  <span class="text-success" title="Helpful"><i class="fas fa-thumbs-up me-1"></i>{{ $faq->helpful_count }}</span>
                </td>
                <td class="text-center">
                  <button type="button" 
                          class="btn btn-sm btn-link p-0 text-decoration-none" 
                          wire:click="toggleFeatured({{ $faq->id }})"
                          title="Toggle featured status">
                    @if($faq->is_featured)
                      <i class="fas fa-star text-warning"></i>
                    @else
                      <i class="far fa-star text-400"></i>
                    @endif
                  </button>
                </td>
                <td class="text-center">
                  <button type="button" 
                          class="btn btn-sm btn-link p-0 text-decoration-none" 
                          wire:click="togglePublished({{ $faq->id }})"
                          title="Toggle publish status">
                    @if($faq->is_published)
                      <span class="badge bg-subtle-success text-success">Published</span>
                    @else
                      <span class="badge bg-subtle-secondary text-secondary">Draft</span>
                    @endif
                  </button>
                </td>
                <td class="text-end text-nowrap">
                  <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" wire:click="editFaq({{ $faq->id }})" title="Edit FAQ">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger py-0 px-2" wire:click="deleteFaq({{ $faq->id }})" onclick="return confirm('Are you sure you want to delete this FAQ?') || event.stopImmediatePropagation()" title="Delete FAQ">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  <i class="fas fa-question-circle fa-2x mb-2 text-300"></i>
                  <p class="mb-0">No FAQs match the current criteria.</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($faqs->hasPages())
      <div class="card-footer bg-body-tertiary py-2">
        {{ $faqs->links() }}
      </div>
    @endif
  </div>

  <!-- Create / Edit FAQ Modal -->
  @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border">
          <div class="modal-header bg-body-tertiary">
            <h5 class="modal-title fw-bold">
              <i class="fas {{ $editingFaqId ? 'fa-edit' : 'fa-plus-circle' }} me-2 text-primary"></i>
              {{ $editingFaqId ? 'Edit FAQ' : 'Create New FAQ' }}
            </h5>
            <button type="button" class="btn-close" wire:click="closeModal"></button>
          </div>
          <form wire:submit.prevent="saveFaq">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-8">
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
                  <label class="form-label fw-semibold fs-10">Display Order</label>
                  <input type="number" class="form-control form-control-sm" wire:model="displayOrder" min="0" />
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold fs-10">Question <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm @error('question') is-invalid @enderror" wire:model="question" placeholder="e.g. How to prevent double bookings?" />
                  @error('question') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold fs-10">Answer (HTML or Plain Text) <span class="text-danger">*</span></label>
                  <textarea class="form-control fs-10 @error('answer') is-invalid @enderror" rows="5" wire:model="answer" placeholder="Enter clear, helpful answer..."></textarea>
                  @error('answer') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-10">Keywords (Comma separated)</label>
                  <input type="text" class="form-control form-control-sm" wire:model="keywords" placeholder="booking, slot, conflict" />
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-10">Tags</label>
                  <input type="text" class="form-control form-control-sm" wire:model="tags" placeholder="booking,calendar" />
                </div>

                <div class="col-md-6">
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="faqFeaturedSwitch" wire:model="isFeatured" />
                    <label class="form-check-label fs-10 fw-semibold" for="faqFeaturedSwitch">Mark as Featured FAQ</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="faqPublishedSwitch" wire:model="isPublished" />
                    <label class="form-check-label fs-10 fw-semibold" for="faqPublishedSwitch">Publish Immediately</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
              <button type="button" class="btn btn-sm btn-secondary" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-save me-1"></i>{{ $editingFaqId ? 'Update FAQ' : 'Save FAQ' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif
</div>
