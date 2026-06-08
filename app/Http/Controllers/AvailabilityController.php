<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Display the availability checker page.
     */
    public function index()
    {
        return view('availability.index');
    }
}
