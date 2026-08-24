<?php

namespace App\Http\Controllers;

use App\Models\SaasInvoice;
use Illuminate\Http\Request;

class SaasInvoiceController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('saas-invoices.index');
    }

    public function show(SaasInvoice $saas_invoice)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $saas_invoice->load(['user', 'subscriptionPlan', 'billingCycle', 'payments']);
        return view('saas-invoices.show', ['invoice' => $saas_invoice]);
    }
}
