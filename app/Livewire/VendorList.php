<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorBooking;
use App\Models\Booking;
use Livewire\Component;

class VendorList extends Component
{
    // Vendor Form States
    public $name;
    public $vendor_type = 'Decorator';
    public $contact_person;
    public $phone;
    public $email;

    // Vendor Booking Form States
    public $selectedVendorId;
    public $booking_id;
    public $agreed_price;
    public $commission_rate = 10.00;

    protected $rules = [
        'name' => 'required|string|max:255',
        'vendor_type' => 'required|string',
        'contact_person' => 'nullable|string|max:255',
        'phone' => 'required|string|max:20',
        'email' => 'nullable|email|max:255',
    ];

    public function saveVendor()
    {
        $this->validate();

        Vendor::create([
            'marquee_id' => auth()->user()->marquee_id ?? 1,
            'name' => $this->name,
            'vendor_type' => $this->vendor_type,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => 'Active',
        ]);

        $this->name = '';
        $this->contact_person = '';
        $this->phone = '';
        $this->email = '';

        session()->flash('success', 'Vendor profile registered successfully.');
    }

    public function saveVendorBooking()
    {
        $this->validate([
            'selectedVendorId' => 'required|exists:vendors,id',
            'booking_id' => 'required|exists:bookings,id',
            'agreed_price' => 'required|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        VendorBooking::create([
            'marquee_id' => auth()->user()->marquee_id ?? 1,
            'vendor_id' => $this->selectedVendorId,
            'booking_id' => $this->booking_id,
            'agreed_price' => $this->agreed_price,
            'commission_rate' => $this->commission_rate,
            'payment_status' => 'Unpaid',
        ]);

        $this->booking_id = '';
        $this->agreed_price = '';
        
        session()->flash('success_booking', 'Vendor commission mapped to booking successfully.');
    }

    public function render()
    {
        $vendors = Vendor::with('bookings')->get();
        $bookings = Booking::with('customer')->orderBy('booking_date', 'desc')->get();
        $vendorBookings = VendorBooking::with(['vendor', 'booking.customer'])->orderBy('created_at', 'desc')->get();

        return view('livewire.vendor-list', [
            'vendors' => $vendors,
            'bookings' => $bookings,
            'vendorBookings' => $vendorBookings,
        ])->layout('layouts.admin');
    }
}
