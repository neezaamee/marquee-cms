<div>
  <!-- Breadcrumb Navigation -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb fs-10 mb-0">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Help Center</a></li>
      @if($article->category)
        <li class="breadcrumb-item">
          <a href="{{ route('support.index', ['selectedCategorySlug' => $article->category->slug]) }}">
            {{ $article->category->name }}
          </a>
        </li>
      @endif
      <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($article->title, 40) }}</li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- Main Article Body -->
    <div class="col-lg-8">
      <div class="card shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
          <!-- Article Metadata Header -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span class="badge bg-subtle-primary text-primary px-3 py-2 rounded-pill fs-11">
              <i class="{{ $article->category ? $article->category->icon : 'fas fa-book' }} me-1"></i>
              {{ $article->category ? $article->category->name : 'General Guide' }}
            </span>
            <div class="d-flex align-items-center gap-3 fs-11 text-500">
              <span><i class="far fa-clock me-1"></i>{{ $article->reading_time_minutes }} min read</span>
              <span><i class="fas fa-eye me-1"></i>{{ $article->views_count }} views</span>
              <span><i class="far fa-calendar-alt me-1"></i>Updated {{ $article->updated_at->format('M d, Y') }}</span>
            </div>
          </div>

          <h1 class="fw-bold text-dark mb-3 fs-3">{{ $article->title }}</h1>

          @if($article->summary)
            <div class="p-3 mb-4 rounded-3 bg-50 border-start border-primary border-4 text-700 fs-10 fst-italic">
              {{ $article->summary }}
            </div>
          @endif

          <hr class="my-4 text-300" />

          <!-- Formatted Content -->
          <div class="article-content fs-10 text-800 lh-lg mb-5">
            {!! $article->content !!}
          </div>

          @if($article->tags)
            <div class="mb-4">
              <span class="fs-11 text-600 me-2"><i class="fas fa-tags me-1"></i>Tags:</span>
              @foreach(explode(',', $article->tags) as $tag)
                <span class="badge bg-200 text-700 rounded-pill fs-11 me-1 mb-1">{{ trim($tag) }}</span>
              @endforeach
            </div>
          @endif

          <hr class="my-4 text-300" />

          <!-- Helpfulness Feedback Widget -->
          <div class="bg-body-tertiary rounded-3 p-4 text-center border">
            <h6 class="fw-bold mb-2">Was this guide helpful to you?</h6>
            <p class="fs-11 text-600 mb-3">Your ratings help us continually refine our system documentation.</p>

            @if(session()->has('feedback_status'))
              <div class="alert alert-success py-2 px-3 fs-10 d-inline-block mb-3">
                <i class="fas fa-check-circle me-1"></i>{{ session('feedback_status') }}
              </div>
            @endif

            <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
              <button type="button" 
                      class="btn btn-sm {{ $hasVoted && $userVote === true ? 'btn-success' : 'btn-outline-success' }} px-4 py-2 fs-10 rounded-pill" 
                      wire:click="submitVote(true)"
                      @if($hasVoted) disabled @endif>
                <i class="fas fa-thumbs-up me-1"></i>Yes, it helped
              </button>
              <button type="button" 
                      class="btn btn-sm {{ $hasVoted && $userVote === false ? 'btn-danger' : 'btn-outline-danger' }} px-4 py-2 fs-10 rounded-pill" 
                      wire:click="submitVote(false)"
                      @if($hasVoted) disabled @endif>
                <i class="fas fa-thumbs-down me-1"></i>No, I need more details
              </button>
            </div>

            @if($showCommentInput)
              <div class="mt-3 mx-auto" style="max-width: 500px;">
                <p class="fs-11 text-600 mb-1">What was missing or unclear in this article? (Optional)</p>
                <div class="input-group">
                  <input type="text" class="form-control form-control-sm fs-11" wire:model="feedbackComment" placeholder="e.g. Needs clarification on PRA tax calculation..." />
                  <button class="btn btn-sm btn-primary fs-11" type="button" wire:click="submitComment">Submit Feedback</button>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Right Sidebar: Related Articles & Support Escalation -->
    <div class="col-lg-4">
      <!-- Related Articles -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-body-tertiary py-2">
          <h6 class="mb-0 fw-bold">
            <i class="fas fa-bookmark text-primary me-2"></i>Related Guides
          </h6>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            @forelse($relatedArticles as $related)
              <a href="{{ route('support.article.show', $related->slug) }}" class="list-group-item list-group-item-action py-3 px-3">
                <div class="fw-semibold text-dark fs-10 mb-1">{{ $related->title }}</div>
                <div class="d-flex align-items-center justify-content-between fs-11 text-500">
                  <span><i class="far fa-clock me-1"></i>{{ $related->reading_time_minutes }} min read</span>
                  <span><i class="fas fa-eye me-1"></i>{{ $related->views_count }}</span>
                </div>
              </a>
            @empty
              <div class="p-3 text-center text-muted fs-11">No other guides found in this category.</div>
            @endforelse
          </div>
        </div>
      </div>

      <!-- Still Need Help Box -->
      <div class="card border-primary border-2 shadow-sm">
        <div class="card-body p-4 text-center">
          <div class="avatar avatar-xl bg-subtle-primary text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="fas fa-user-headset fa-2x"></i>
          </div>
          <h5 class="fw-bold mb-1">Still need assistance?</h5>
          <p class="fs-10 text-600 mb-3">Our dedicated SaaS customer engineers can resolve operational and billing matters with you directly.</p>
          <div class="d-grid gap-2">
            <a href="{{ route('support.index') }}" class="btn btn-primary btn-sm fs-10">
              <i class="fas fa-ticket-alt me-1"></i>Open a Support Ticket
            </a>
            <a href="{{ route('support.index') }}" class="btn btn-outline-secondary btn-sm fs-10">
              <i class="fas fa-arrow-left me-1"></i>Back to Help Center
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
