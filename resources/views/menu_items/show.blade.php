@extends('layouts.admin')

@section('title', 'Menu Item Details')

@section('content')
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><span class="fas fa-utensils me-2 text-primary"></span>Menu Item Details</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('menu-items.index') }}">
                <span class="fas fa-chevron-left me-1"></span> Back
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <img src="{{ $menuItem->image_url }}" alt="{{ $menuItem->item_name }}" class="img-fluid rounded border shadow-sm" style="max-height: 200px; object-fit: cover;">
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Item Name</h6>
                            <h4>{{ $menuItem->item_name }}</h4>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Item Code</h6>
                            <span class="badge badge-subtle-primary fs-10 font-monospace">{{ $menuItem->item_code }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Category</h6>
                            <span class="text-800 fw-semi-bold">{{ $menuItem->category->category_name }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Unit of Measure</h6>
                            <span class="badge badge-subtle-info fs-10">{{ $menuItem->unit }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Base Cost</h6>
                            <p class="text-secondary fw-semi-bold fs-9 mb-0">PKR {{ number_format($menuItem->base_cost, 2) }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Selling Price</h6>
                            <p class="text-success fw-bold fs-8 mb-0">PKR {{ number_format($menuItem->selling_price, 2) }}</p>
                        </div>
                        <div class="col-12 mb-3">
                            <h6 class="text-500 mb-1">Description</h6>
                            <p class="text-800">{{ $menuItem->description ?: 'No description provided.' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Status</h6>
                            <span class="badge badge-subtle-{{ $menuItem->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">{{ $menuItem->status }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-500 mb-1">Created By</h6>
                            <p class="text-800 mb-0">{{ $menuItem->creator->name ?? 'System' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
