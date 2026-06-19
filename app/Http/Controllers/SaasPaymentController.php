<?php

namespace App\Http\Controllers;

use App\Models\SaasPayment;
use Illuminate\Http\Request;

class SaasPaymentController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('saas-payments.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('saas-payments.create');
    }
}
