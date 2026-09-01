<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerCommunicationLog;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CustomerProfile extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $customer;

    // Active tab
    public $activeTab = 'overview'; // overview, financials, documents, crm

    // Document Upload fields
    public $document_name = '';
    public $document_type = 'CNIC Front';
    public $document_file = null;

    // Communication Log fields
    public $comm_medium = 'Call';
    public $comm_subject = '';
    public $comm_content = '';

    public $documentTypes = [
        'CNIC Front',
        'CNIC Back',
        'Contract',
        'Agreement',
        'Event Document',
        'Other'
    ];

    public $mediums = [
        'Call' => 'Phone Call',
        'WhatsApp' => 'WhatsApp Message',
        'SMS' => 'SMS Text',
        'Email' => 'Email',
        'Note' => 'Manual Note'
    ];

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Upload dynamic document.
     */
    public function uploadDocument()
    {
        $this->validate([
            'document_name' => 'required|string|max:255',
            'document_type' => 'required|string|max:255',
            'document_file' => 'required|file|max:5120', // Max 5MB
        ]);

        $filePath = $this->document_file->store("customers/documents/{$this->customer->id}", 'public');

        CustomerDocument::create([
            'customer_id' => $this->customer->id,
            'document_name' => $this->document_name,
            'file_path' => $filePath,
            'document_type' => $this->document_type,
            'file_size' => $this->document_file->getSize(),
            'mime_type' => $this->document_file->getMimeType(),
        ]);

        // Reset fields
        $this->document_name = '';
        $this->document_type = 'CNIC Front';
        $this->document_file = null;

        $this->resetPage('docsPage');

        session()->flash('success_doc', 'Document uploaded successfully.');
    }

    /**
     * Delete customer document.
     */
    public function deleteDocument($id)
    {
        $doc = CustomerDocument::findOrFail($id);
        
        // Scope Check
        if ($doc->customer->marquee_id !== auth()->user()->marquee_id && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Delete physical file
        if ($doc->file_path) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->delete();
        $this->resetPage('docsPage');
        session()->flash('success_doc', 'Document deleted successfully.');
    }

    /**
     * Log new CRM communication entry.
     */
    public function logCommunication()
    {
        $this->validate([
            'comm_medium' => 'required|in:Call,WhatsApp,SMS,Email,Note',
            'comm_subject' => 'nullable|string|max:255',
            'comm_content' => 'required|string',
        ]);

        CustomerCommunicationLog::create([
            'customer_id' => $this->customer->id,
            'communication_medium' => $this->comm_medium,
            'subject' => $this->comm_subject,
            'content' => $this->comm_content,
            'status' => 'logged',
        ]);

        // Reset fields
        $this->comm_subject = '';
        $this->comm_content = '';
        
        $this->resetPage('logsPage');

        session()->flash('success_crm', 'Communication log entry added.');
    }

    public function render()
    {
        $bookings = $this->customer->bookings()
            ->with(['payments', 'halls', 'eventType'])
            ->latest()
            ->paginate(5, ['*'], 'bookingsPage');
            
        $documents = $this->customer->documents()
            ->with('uploader')
            ->latest()
            ->paginate(5, ['*'], 'docsPage');
            
        $communicationLogs = $this->customer->communicationLogs()
            ->with('logger')
            ->latest()
            ->paginate(5, ['*'], 'logsPage');

        $customerLedgers = $this->customer->ledgers()
            ->with(['booking', 'bookingPayment', 'journalVoucher'])
            ->paginate(15, ['*'], 'ledgerPage');

        return view('livewire.customer-profile', compact('bookings', 'documents', 'communicationLogs', 'customerLedgers'));
    }
}
