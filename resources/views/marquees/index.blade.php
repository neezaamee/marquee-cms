@extends('layouts.admin')

@section('title', 'Manage Marquees')

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Marquee Tenants</h5>
    <div class="d-flex align-items-center gap-2">
      <form action="{{ route('marquees.index') }}" method="GET" class="d-inline-block">
        <div class="input-group input-group-sm">
          <input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search..." />
          <button class="btn btn-outline-secondary" type="submit"><span class="fas fa-search"></span></button>
          @if(request('search'))
            <a href="{{ route('marquees.index') }}" class="btn btn-outline-secondary"><span class="fas fa-times"></span></a>
          @endif
        </div>
      </form>
      <a class="btn btn-falcon-primary btn-sm" href="{{ route('marquees.create') }}">
        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add New Marquee
      </a>
    </div>
  </div>
  
  <div class="card-body p-0">
    @if(session('success'))
      <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
        <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
        <p class="mb-0 flex-1">{{ session('success') }}</p>
        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
        <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
        <p class="mb-0 flex-1">{{ session('error') }}</p>
        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="table-responsive scrollbar">
      <table class="table table-sm table-striped fs-10 mb-0">
        <thead class="bg-200 text-900">
          <tr>
            <th class="align-middle px-3">Name</th>
            <th class="align-middle">City</th>
            <th class="align-middle">Phone</th>
            <th class="align-middle">Subscription Plan</th>
            <th class="align-middle">Expires At</th>
            <th class="align-middle text-center">Status</th>
            <th class="align-middle text-end px-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($marquees as $marquee)
            <tr>
              <td class="align-middle px-3 fw-semi-bold">
                <a href="{{ route('marquees.show', $marquee->id) }}">{{ $marquee->name }}</a>
              </td>
              <td class="align-middle">{{ $marquee->city }}</td>
              <td class="align-middle">{{ $marquee->phone }}</td>
              <td class="align-middle">{{ $marquee->subscriptionPlan->name ?? 'None' }}</td>
              <td class="align-middle">
                {{ $marquee->subscription_ends_at ? $marquee->subscription_ends_at->format('M d, Y') : 'N/A' }}
              </td>
              <td class="align-middle text-center">
                @if($marquee->status === 'active')
                  <span class="badge badge-subtle-success rounded-pill">Active</span>
                @elseif($marquee->status === 'inactive')
                  <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                @else
                  <span class="badge badge-subtle-danger rounded-pill">Suspended</span>
                @endif
              </td>
              <td class="align-middle text-end px-3">
                <div class="d-flex justify-content-end gap-2">
                  <a class="btn btn-link p-0" href="{{ route('marquees.show', $marquee->id) }}" data-bs-toggle="tooltip" title="View">
                    <span class="text-info fas fa-eye"></span>
                  </a>
                  <a class="btn btn-link p-0" href="{{ route('marquees.edit', $marquee->id) }}" data-bs-toggle="tooltip" title="Edit">
                    <span class="text-primary fas fa-edit"></span>
                  </a>
                  <form id="delete-form-marquee-{{ $marquee->id }}" action="{{ route('marquees.destroy', $marquee->id) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                  </form>
                  <a class="btn btn-link p-0" href="javascript:void(0);" onclick="if(confirm('Are you sure you want to delete this marquee?')) { document.getElementById('delete-form-marquee-{{ $marquee->id }}').submit(); }" data-bs-toggle="tooltip" title="Delete">
                    <span class="text-danger fas fa-trash-alt"></span>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4">No marquees found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($marquees->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-center bg-light">
      {{ $marquees->links() }}
    </div>
  @endif
</div>
@endsection
