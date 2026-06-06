@extends('layouts.admin')

@section('title', 'Manage Staff Users')

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Staff Members & Users</h5>
    <div class="d-flex align-items-center gap-2">
      <form action="{{ route('users.index') }}" method="GET" class="d-inline-block">
        <div class="input-group input-group-sm">
          <input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search..." />
          <button class="btn btn-outline-secondary" type="submit"><span class="fas fa-search"></span></button>
          @if(request('search'))
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><span class="fas fa-times"></span></a>
          @endif
        </div>
      </form>
      @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
        <a class="btn btn-falcon-primary btn-sm" href="{{ route('users.create') }}">
          <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add New User
        </a>
      @endif
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
            <th class="align-middle">Email</th>
            @if(auth()->user()->isSuperAdmin())
              <th class="align-middle">Marquee Tenant</th>
            @endif
            <th class="align-middle">Role</th>
            <th class="align-middle">Branch Location</th>
            <th class="align-middle text-center">Status</th>
            <th class="align-middle text-end px-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
            <tr>
              <td class="align-middle px-3 fw-semi-bold">
                <a href="{{ route('users.show', $user->id) }}">{{ ucwords($user->name) }}</a>
              </td>
              <td class="align-middle">{{ $user->email }}</td>
              @if(auth()->user()->isSuperAdmin())
                <td class="align-middle">{{ $user->marquee->name ?? 'SaaS Administration' }}</td>
              @endif
              <td class="align-middle">{{ $user->role->label ?? 'No Role' }}</td>
              <td class="align-middle">{{ $user->branch->name ?? 'All Branches (Company-wide)' }}</td>
              <td class="align-middle text-center">
                <span class="badge badge-subtle-{{ $user->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                  {{ ucfirst($user->status) }}
                </span>
              </td>
              <td class="align-middle text-end px-3">
                <div class="d-flex justify-content-end gap-2">
                  <a class="btn btn-link p-0" href="{{ route('users.show', $user->id) }}" data-bs-toggle="tooltip" title="View">
                    <span class="text-info fas fa-eye"></span>
                  </a>
                  @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'))
                    <a class="btn btn-link p-0" href="{{ route('users.edit', $user->id) }}" data-bs-toggle="tooltip" title="Edit">
                      <span class="text-primary fas fa-edit"></span>
                    </a>
                    @if($user->id !== auth()->id())
                      <form id="delete-form-user-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                      </form>
                      <a class="btn btn-link p-0" href="javascript:void(0);" onclick="if(confirm('Are you sure you want to delete this user?')) { document.getElementById('delete-form-user-{{ $user->id }}').submit(); }" data-bs-toggle="tooltip" title="Delete">
                        <span class="text-danger fas fa-trash-alt"></span>
                      </a>
                    @endif
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4">No users found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($users->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-center bg-light">
      {{ $users->links() }}
    </div>
  @endif
</div>
@endsection
