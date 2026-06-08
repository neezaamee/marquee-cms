@extends('layouts.admin')

@section('title', 'Menu Category Details')

@section('content')
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><span class="fas fa-list me-2 text-primary"></span>Category Details</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('menu-categories.index') }}">
                <span class="fas fa-chevron-left me-1"></span> Back
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-500 mb-1">Category Name</h6>
                    <h4>{{ $menuCategory->category_name }}</h4>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-500 mb-1">Category Code</h6>
                    <span class="badge badge-subtle-primary fs-10 font-monospace">{{ $menuCategory->category_code }}</span>
                </div>
                <div class="col-12 mb-3">
                    <h6 class="text-500 mb-1">Description</h6>
                    <p class="text-800">{{ $menuCategory->description ?: 'No description provided.' }}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-500 mb-1">Sort Order</h6>
                    <span class="badge badge-subtle-secondary fs-10">{{ $menuCategory->sort_order }}</span>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-500 mb-1">Status</h6>
                    <span class="badge badge-subtle-{{ $menuCategory->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">{{ $menuCategory->status }}</span>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-500 mb-1">Created By</h6>
                    <p class="text-800 mb-0">{{ $menuCategory->creator->name ?? 'System' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
