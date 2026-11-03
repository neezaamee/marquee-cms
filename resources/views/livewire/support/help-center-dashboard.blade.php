<div>
  <!-- Hero Search Section -->
  <div class="card bg-primary-subtle border-0 mb-4 overflow-hidden position-relative shadow-sm">
    <div class="card-body p-4 p-md-5 text-center position-relative" style="z-index: 1;">
      <span class="badge badge-subtle-primary rounded-pill px-3 py-2 fs-10 fw-bold mb-2 text-uppercase">
        <i class="fas fa-headset me-1"></i>MarqueeCMS Help Center
      </span>
      <h2 class="fw-bold text-dark mb-2">How can we assist your business today?</h2>
      <p class="text-700 fs-10 mb-4 mx-auto" style="max-width: 620px;">
        Explore comprehensive walkthroughs, quick-reference guides, frequently asked questions, or connect directly with our 24/7 dedicated SaaS technical support desk.
      </p>

      <!-- Search Bar -->
      <div class="mx-auto position-relative" style="max-width: 680px;">
        <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-white border">
          <span class="input-group-text bg-white border-0 ps-4 text-primary">
            <i class="fas fa-search fs-9"></i>
          </span>
          <input type="text" 
                 class="form-control border-0 ps-2 fs-10 py-3 shadow-none" 
                 placeholder="Search articles, guides, billing, double-booking solutions, PRA taxes..." 
                 wire:model.live.debounce.300ms="searchQuery" 
                 aria-label="Search help documentation" />
          @if($searchQuery)
            <button class="btn btn-link text-muted border-0 pe-4" type="button" wire:click="$set('searchQuery', '')" title="Clear Search">
              <i class="fas fa-times"></i>
            </button>
          @endif
        </div>
        <div wire:loading wire:target="searchQuery" class="position-absolute end-0 top-100 mt-1 pe-3 text-primary fs-11">
          <span class="spinner-border spinner-border-sm me-1" role="status"></span> Searching documentation...
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Action Cards Hub -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card h-100 border-start border-primary border-4 shadow-sm hover-shadow transition-all">
        <div class="card-body p-3">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-l bg-subtle-primary text-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
              <i class="fas fa-ticket-alt"></i>
            </div>
            <h6 class="mb-0 fw-bold">Support Tickets</h6>
          </div>
          <p class="fs-11 text-600 mb-2">Submit and track technical inquiries, invoice discrepancies, or booking issues.</p>
          <a href="#supportCollapse" data-bs-toggle="collapse" class="btn btn-sm btn-link text-primary p-0 fw-semibold fs-11 text-decoration-none">
            Manage Tickets <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card h-100 border-start border-success border-4 shadow-sm hover-shadow transition-all">
        <div class="card-body p-3">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-l bg-subtle-success text-success rounded-circle me-2 d-flex align-items-center justify-content-center">
              <i class="fas fa-comments"></i>
            </div>
            <h6 class="mb-0 fw-bold">Live Chat Support</h6>
          </div>
          <p class="fs-11 text-600 mb-2">Instant messaging with support engineers for urgent event-day assistance.</p>
          <a href="#supportCollapse" data-bs-toggle="collapse" class="btn btn-sm btn-link text-success p-0 fw-semibold fs-11 text-decoration-none">
            Start Conversation <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card h-100 border-start border-warning border-4 shadow-sm hover-shadow transition-all">
        <div class="card-body p-3">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-l bg-subtle-warning text-warning rounded-circle me-2 d-flex align-items-center justify-content-center">
              <i class="fas fa-lightbulb"></i>
            </div>
            <h6 class="mb-0 fw-bold">Feature Requests</h6>
          </div>
          <p class="fs-11 text-600 mb-2">Suggest new software improvements, vote on ideas, and track development.</p>
          <a href="#supportCollapse" data-bs-toggle="collapse" class="btn btn-sm btn-link text-warning p-0 fw-semibold fs-11 text-decoration-none">
            Submit Suggestion <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card h-100 border-start border-info border-4 shadow-sm hover-shadow transition-all">
        <div class="card-body p-3">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar avatar-l bg-subtle-info text-info rounded-circle me-2 d-flex align-items-center justify-content-center">
              <i class="fas fa-bullhorn"></i>
            </div>
            <h6 class="mb-0 fw-bold">Release Notes</h6>
          </div>
          <p class="fs-11 text-600 mb-2">Explore the latest platform changelogs, updates, and feature upgrades.</p>
          <a href="#supportCollapse" data-bs-toggle="collapse" class="btn btn-sm btn-link text-info p-0 fw-semibold fs-11 text-decoration-none">
            View Updates <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Category Filter Pills -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body py-3">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-1 flex-wrap">
          <span class="fs-11 fw-semibold text-600 me-2"><i class="fas fa-filter me-1"></i>Browse by Topic:</span>
          <button class="btn btn-sm rounded-pill py-1 px-3 fs-11 {{ empty($selectedCategorySlug) ? 'btn-primary' : 'btn-outline-secondary' }}"
                  wire:click="clearFilters">
            All Topics
          </button>
          @foreach($categories as $category)
            <button class="btn btn-sm rounded-pill py-1 px-3 fs-11 {{ $selectedCategorySlug === $category->slug ? 'btn-primary' : 'btn-outline-secondary' }}"
                    wire:click="setCategory('{{ $category->slug }}')">
              <i class="{{ $category->icon }} me-1"></i>{{ $category->name }}
              <span class="badge {{ $selectedCategorySlug === $category->slug ? 'bg-white text-primary' : 'bg-200 text-600' }} rounded-pill ms-1">
                {{ $category->articles_count + $category->faqs_count }}
              </span>
            </button>
          @endforeach
        </div>

        @if($searchQuery || $selectedCategorySlug)
          <button class="btn btn-sm btn-link text-danger p-0 fs-11 text-decoration-none" wire:click="clearFilters">
            <i class="fas fa-undo me-1"></i>Reset Filters
          </button>
        @endif
      </div>
    </div>
  </div>

  <!-- Search Filter Active Banner -->
  @if($searchQuery || $activeCategory)
    <div class="alert alert-subtle-primary d-flex align-items-center justify-content-between py-2 px-3 mb-4 fs-10">
      <div>
        <i class="fas fa-info-circle me-1"></i>
        Filtering results for: 
        @if($searchQuery)
          <strong>"{{ $searchQuery }}"</strong>
        @endif
        @if($activeCategory)
          in <strong>{{ $activeCategory->name }}</strong>
        @endif
        ({{ $articles->total() }} articles, {{ $faqs->count() }} FAQs found)
      </div>
      <button class="btn btn-sm btn-outline-primary py-0 px-2 fs-11" wire:click="clearFilters">Clear Filter</button>
    </div>
  @endif

  <!-- Content Section: Knowledge Base & FAQs -->
  <div class="row g-4 mb-4">
    <!-- Left Column: Knowledge Base Articles -->
    <div class="col-lg-7">
      <div class="card h-100 shadow-sm">
        <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between py-2">
          <h6 class="mb-0 fw-bold">
            <i class="fas fa-book-open text-primary me-2"></i>Knowledge Base Guides
          </h6>
          <span class="badge bg-subtle-primary text-primary rounded-pill fs-11">
            {{ $articles->total() }} Guides Available
          </span>
        </div>
        <div class="card-body p-3">
          @forelse($articles as $article)
            <div class="p-3 mb-3 border rounded-3 bg-white hover-bg-100 transition-all">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="badge bg-subtle-secondary text-700 fs-11">
                  <i class="{{ $article->category ? $article->category->icon : 'fas fa-tag' }} me-1"></i>
                  {{ $article->category ? $article->category->name : 'General' }}
                </span>
                <span class="fs-11 text-500">
                  <i class="far fa-clock me-1"></i>{{ $article->reading_time_minutes }} min read
                </span>
              </div>
              <h6 class="fw-bold mb-1">
                <a href="{{ route('support.article.show', $article->slug) }}" class="text-dark text-decoration-none hover-primary">
                  {{ $article->title }}
                </a>
              </h6>
              <p class="fs-10 text-600 mb-2">{{ Str::limit($article->summary, 160) }}</p>
              <div class="d-flex align-items-center justify-content-between fs-11 text-500">
                <span>
                  <i class="fas fa-eye me-1"></i>{{ $article->views_count }} views &bull; 
                  <i class="fas fa-thumbs-up me-1 text-success"></i>{{ $article->helpful_count }}
                </span>
                <a href="{{ route('support.article.show', $article->slug) }}" class="text-primary fw-semibold text-decoration-none">
                  Read Guide <i class="fas fa-arrow-right ms-1 fs-11"></i>
                </a>
              </div>
            </div>
          @empty
            <div class="text-center py-5 text-muted">
              <i class="fas fa-search fa-3x text-300 mb-3"></i>
              <h6 class="fw-bold">No documentation articles found</h6>
              <p class="fs-10 text-600 mb-3">Try refining your search terms or selecting a different category.</p>
              <button class="btn btn-sm btn-outline-primary" wire:click="clearFilters">View All Articles</button>
            </div>
          @endforelse

          @if($articles->hasPages())
            <div class="mt-3">
              {{ $articles->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: FAQs Accordion -->
    <div class="col-lg-5">
      <div class="card h-100 shadow-sm">
        <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between py-2">
          <h6 class="mb-0 fw-bold">
            <i class="fas fa-question-circle text-success me-2"></i>Frequently Asked Questions
          </h6>
          <span class="badge bg-subtle-success text-success rounded-pill fs-11">
            {{ $faqs->count() }} FAQs
          </span>
        </div>
        <div class="card-body p-3">
          @if($faqs->isNotEmpty())
            <div class="accordion" id="faqAccordion">
              @foreach($faqs as $index => $faq)
                <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-none">
                  <h2 class="accordion-header" id="headingFaq{{ $faq->id }}">
                    <button class="accordion-button collapsed py-2 px-3 fs-10 fw-semibold bg-white" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#collapseFaq{{ $faq->id }}" 
                            aria-expanded="false" 
                            aria-controls="collapseFaq{{ $faq->id }}">
                      <span class="text-truncate">{{ $faq->question }}</span>
                    </button>
                  </h2>
                  <div id="collapseFaq{{ $faq->id }}" 
                       class="accordion-collapse collapse" 
                       aria-labelledby="headingFaq{{ $faq->id }}" 
                       data-bs-parent="#faqAccordion">
                    <div class="accordion-body p-3 fs-10 text-700 bg-50 border-top">
                      <div class="mb-2">
                        {!! $faq->answer !!}
                      </div>

                      <div class="d-flex align-items-center justify-content-between pt-2 border-top fs-11 text-500">
                        <span>Was this answer helpful?</span>
                        <div class="d-flex align-items-center gap-1">
                          @if(session()->has('faq_message_' . $faq->id))
                            <span class="badge bg-subtle-info text-info py-1 px-2 fs-11">
                              {{ session('faq_message_' . $faq->id) }}
                            </span>
                          @else
                            <button type="button" 
                                    class="btn btn-sm btn-outline-success py-0 px-2 fs-11" 
                                    wire:click="voteFaq({{ $faq->id }}, true)"
                                    title="Yes, helpful">
                              <i class="fas fa-thumbs-up me-1"></i>Yes ({{ $faq->helpful_count }})
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger py-0 px-2 fs-11" 
                                    wire:click="voteFaq({{ $faq->id }}, false)"
                                    title="No, not helpful">
                              <i class="fas fa-thumbs-down me-1"></i>No ({{ $faq->unhelpful_count }})
                            </button>
                          @endif
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="text-center py-5 text-muted">
              <i class="fas fa-question fa-3x text-300 mb-3"></i>
              <h6 class="fw-bold">No FAQs match your search</h6>
              <p class="fs-10 text-600 mb-0">Try searching with alternative keywords or clearing topic filters.</p>
            </div>
          @endif

          <!-- Still Need Help Box -->
          <div class="card bg-body-tertiary border border-dashed mt-4">
            <div class="card-body p-3 text-center">
              <div class="avatar avatar-m bg-subtle-primary text-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center">
                <i class="fas fa-envelope-open-text"></i>
              </div>
              <h6 class="fw-bold mb-1">Didn't find what you need?</h6>
              <p class="fs-11 text-600 mb-3">Our technical support engineers can assist you with your queries directly.</p>
              <div class="d-flex justify-content-center gap-2">
                <a href="#supportCollapse" data-bs-toggle="collapse" class="btn btn-sm btn-primary fs-11">
                  <i class="fas fa-plus me-1"></i>Submit a Ticket
                </a>
                <a href="#supportCollapse" data-bs-toggle="collapse" class="btn btn-sm btn-outline-secondary fs-11">
                  <i class="fas fa-comment-dots me-1"></i>Live Chat
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
