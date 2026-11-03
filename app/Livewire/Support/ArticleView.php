<?php

namespace App\Livewire\Support;

use App\Models\KbArticle;
use App\Models\KbArticleFeedback;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class ArticleView extends Component
{
    public string $slug = '';
    public ?KbArticle $article = null;

    // Feedback voting state
    public bool $hasVoted = false;
    public ?bool $userVote = null;
    public string $feedbackComment = '';
    public bool $showCommentInput = false;

    public function mount(string $slug)
    {
        $this->slug = $slug;
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        $query = KbArticle::where('slug', $slug);
        if (!$isSuperAdmin) {
            $query->publicForTenants();
        }

        $this->article = $query->with('category')->firstOrFail();

        // Increment view count if not viewed during this session
        $viewedKey = 'viewed_article_' . $this->article->id;
        if (!Session::has($viewedKey)) {
            $this->article->recordView();
            Session::put($viewedKey, true);
        }

        // Check if user/session already voted
        $existingFeedback = null;
        if ($user) {
            $existingFeedback = KbArticleFeedback::where('article_id', $this->article->id)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $existingFeedback = KbArticleFeedback::where('article_id', $this->article->id)
                ->where('ip_address', Request::ip())
                ->first();
        }

        if ($existingFeedback) {
            $this->hasVoted = true;
            $this->userVote = $existingFeedback->is_helpful;
        }
    }

    /**
     * Submit helpfulness vote.
     */
    public function submitVote(bool $isHelpful)
    {
        if ($this->hasVoted) {
            session()->flash('feedback_status', 'You have already submitted feedback for this article.');
            return;
        }

        $user = auth()->user();
        $feedback = KbArticleFeedback::create([
            'article_id' => $this->article->id,
            'user_id' => $user ? $user->id : null,
            'ip_address' => Request::ip(),
            'is_helpful' => $isHelpful,
            'comments' => null,
        ]);

        if ($isHelpful) {
            $this->article->voteHelpful();
        } else {
            $this->article->voteUnhelpful();
            $this->showCommentInput = true;
        }

        $this->hasVoted = true;
        $this->userVote = $isHelpful;
        session()->flash('feedback_status', 'Thank you! Your feedback helps us improve our documentation.');
    }

    /**
     * Optional comments on unhelpful feedback.
     */
    public function submitComment()
    {
        if (trim($this->feedbackComment) === '') {
            return;
        }

        $user = auth()->user();
        $feedback = KbArticleFeedback::where('article_id', $this->article->id)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->first();

        if ($feedback) {
            $feedback->update(['comments' => trim($this->feedbackComment)]);
        }

        $this->showCommentInput = false;
        $this->feedbackComment = '';
        session()->flash('feedback_status', 'Thank you for your constructive comments! Our team will review this guide.');
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // Related articles in the same category
        $relatedArticles = KbArticle::where('category_id', $this->article->category_id)
            ->where('id', '!=', $this->article->id)
            ->when(!$isSuperAdmin, fn($q) => $q->publicForTenants())
            ->take(5)
            ->get();

        return view('livewire.support.article-view', [
            'relatedArticles' => $relatedArticles,
        ])->layout('layouts.admin');
    }
}
