<?php

namespace App\Livewire\Support;

use App\Models\SupportFeedback;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackForm extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $activeTab = 'submit'; // 'submit' or 'history'
    public $feedbackType = 'suggestion';
    public $subject = '';
    public $description = '';
    public $rating = 5;
    public $module = 'general';

    protected function rules(): array
    {
        return [
            'feedbackType' => 'required|in:general,bug_report,suggestion,feature_request,ux,service_rating',
            'subject' => 'required|string|min:5|max:200',
            'description' => 'required|string|min:15|max:3000',
            'rating' => 'nullable|integer|min:1|max:5',
            'module' => 'nullable|string|max:50',
        ];
    }

    public function submitFeedback()
    {
        $this->validate();

        $user = Auth::user();

        SupportFeedback::create([
            'marquee_id' => $user->getActiveMarqueeId(),
            'user_id' => $user->id,
            'feedback_type' => $this->feedbackType,
            'subject' => $this->subject,
            'description' => $this->description,
            'rating' => $this->rating,
            'module' => !empty($this->module) ? $this->module : 'general',
            'status' => 'new',
        ]);

        $this->reset(['subject', 'description', 'rating', 'feedbackType', 'module']);
        $this->activeTab = 'history';

        session()->flash('success', 'Thank you! Your feedback has been submitted to the product management team.');
    }

    public function render()
    {
        $user = Auth::user();

        $feedbacks = SupportFeedback::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('livewire.support.feedback-form', [
            'feedbacks' => $feedbacks,
        ])->layout('layouts.admin', ['title' => 'Submit Feedback & Suggestions']);
    }
}

