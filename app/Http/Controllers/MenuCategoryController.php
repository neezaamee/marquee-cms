<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    /**
     * Display a listing of the menu categories.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus'), 403);
        return view('menu_categories.index');
    }

    /**
     * Show the form for creating a new menu category.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_menus'), 403);
        return view('menu_categories.create');
    }

    /**
     * Display the specified menu category.
     */
    public function show(MenuCategory $menuCategory)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_menus'), 403);

        if (!auth()->user()->isSuperAdmin() && $menuCategory->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this menu category.');
        }

        return view('menu_categories.show', compact('menuCategory'));
    }

    /**
     * Show the form for editing the specified menu category.
     */
    public function edit(MenuCategory $menuCategory)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_menus'), 403);

        if (!auth()->user()->isSuperAdmin() && $menuCategory->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this menu category.');
        }

        return view('menu_categories.edit', compact('menuCategory'));
    }
}
