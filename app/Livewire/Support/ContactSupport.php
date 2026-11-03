<?php

namespace App\Livewire\Support;

use App\Models\ContactRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContactSupport extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';
    public $company_name = '';
    public $category = 'general';
    public $preferred_contact_method = 'email';
    public $subject = '';
    public $message = '';
    public $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:150',
            'category' => 'required|in:general,sales,billing,partnership,enterprise',
            'preferred_contact_method' => 'required|in:email,phone,whatsapp',
            'subject' => 'required|string|min:5|max:200',
            'message' => 'required|string|min:15|max:3000',
        ];
    }

    public function mount()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone;
            if ($user->marquee) {
                $this->company_name = $user->marquee->name;
            }
        }
    }

    public function submitInquiry()
    {
        $this->validate();

        $user = Auth::user();

        ContactRequest::create([
            'marquee_id' => $user?->getActiveMarqueeId(),
            'user_id' => $user?->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'category' => $this->category,
            'preferred_contact_method' => $this->preferred_contact_method,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => 'new',
        ]);

        $this->submitted = true;
        $this->reset(['subject', 'message']);

        session()->flash('success', 'Your contact request has been received. Our enterprise support team will reach out to you via ' . ucfirst($this->preferred_contact_method) . '.');
    }

    public function render()
    {
        return view('livewire.support.contact-support')
            ->layout('layouts.admin', ['title' => 'Contact Support Desk']);
    }
}
