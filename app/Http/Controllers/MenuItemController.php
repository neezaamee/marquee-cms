<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the menu items.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus'), 403);
        return view('menu_items.index');
    }

    /**
     * Show the form for creating a new menu item.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_menus'), 403);
        return view('menu_items.create');
    }

    /**
     * Display the specified menu item.
     */
    public function show(MenuItem $menuItem)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus'), 403);

        if (!auth()->user()->isSuperAdmin() && $menuItem->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this menu item.');
        }

        return view('menu_items.show', compact('menuItem'));
    }

    /**
     * Show the form for editing the specified menu item.
     */
    public function edit(MenuItem $menuItem)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_menus'), 403);

        if (!auth()->user()->isSuperAdmin() && $menuItem->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this menu item.');
        }

        return view('menu_items.edit', compact('menuItem'));
    }
}
