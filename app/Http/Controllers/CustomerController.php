<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);
        return view('customers.index');
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_bookings'), 403);
        return view('customers.create');
    }

    /**
     * Display the specified customer profile.
     */
    public function show(Customer $customer)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $customer->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this customer profile.');
        }

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $customer->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this customer record.');
        }

        return view('customers.edit', compact('customer'));
    }
}
