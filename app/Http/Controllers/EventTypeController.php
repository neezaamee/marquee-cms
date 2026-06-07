<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use Illuminate\Http\Request;

class EventTypeController extends Controller
{
    /**
     * Display a listing of the event types.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.view'), 403);
        return view('event_types.index');
    }

    /**
     * Show the form for creating a new event type.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.create'), 403);
        return view('event_types.create');
    }

    /**
     * Display the specified event type details.
     */
    public function show(EventType $eventType)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.view'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $eventType->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this event type.');
        }

        return view('event_types.show', compact('eventType'));
    }

    /**
     * Show the form for editing the specified event type.
     */
    public function edit(EventType $eventType)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.edit'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $eventType->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this event type.');
        }

        return view('event_types.edit', compact('eventType'));
    }
}
