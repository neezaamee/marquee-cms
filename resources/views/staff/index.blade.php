@extends('layouts.admin')

@section('title', 'Staff Management')

@section('content')
<div class="card mb-3">
  <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0"><span class="fas fa-id-badge me-2 text-primary"></span>Staff Management</h5>
    <div class="d-flex align-items-center gap-2 flex-wrap">

      {{-- Search form --}}
      <form action="{{ route('staff.index') }}" method="GET" class="d-flex align-items-center gap-1">
        <div class="input-group input-group-sm">
          <input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, CNIC, ID..." />
          <button class="btn btn-outline-secondary" type="submit"><span class="fas fa-search"></span></button>
          @if(request('search') || request('status') || request('designation'))
            <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary"><span class="fas fa-times"></span></a>
          @endif
        </div>

        {{-- Status Filter --}}
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:130px">
          <option value="">All Statuses</option>
          @foreach(\App\Models\Employee::STATUSES as $key => $label)
            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>

        {{-- Designation Filter --}}
        <select name="designation" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:160px">
          <option value="">All Designations</option>
          @foreach(\App\Models\Employee::DESIGNATIONS as $d)
            <option value="{{ $d }}" {{ request('designation') === $d ? 'selected' : '' }}>{{ $d }}</option>
          @endforeach
        </select>
      </form>

      <a class="btn btn-falcon-primary btn-sm" href="{{ route('staff.create') }}">
        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Employee
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

    <div class="table-responsive scrollbar">
      <table class="table table-sm table-striped fs-10 mb-0 align-middle">
        <thead class="bg-200 text-900">
          <tr>
            <th class="px-3" style="width:50px">Photo</th>
            <th>Emp. ID</th>
            <th>Name</th>
            <th>Designation</th>
            <th>Branch</th>
            <th>Mobile</th>
            <th>Type</th>
            <th>Salary</th>
            <th class="text-center">Status</th>
            <th class="text-center">CMS Login</th>
            <th class="text-end px-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($employees as $employee)
            <tr>
              <td class="px-3">
                @if($employee->photo)
                  <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="rounded-circle" width="36" height="36" style="object-fit:cover;">
                @else
                  <div class="avatar avatar-xl" style="width:36px;height:36px;background:var(--falcon-200);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <span class="fas fa-user text-500"></span>
                  </div>
                @endif
              </td>
              <td>
                <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $employee->employee_id }}</span>
              </td>
              <td class="fw-semi-bold">
                <a href="{{ route('staff.show', $employee->id) }}">{{ $employee->name }}</a>
              </td>
              <td>{{ $employee->designation }}</td>
              <td>{{ $employee->branch->name ?? '—' }}</td>
              <td>{{ $employee->mobile_number }}</td>
              <td>
                @php
                  $typeColors = ['Permanent' => 'success', 'Contract' => 'info', 'Daily Wages' => 'warning', 'Part-Time' => 'secondary'];
                  $color = $typeColors[$employee->employment_type] ?? 'secondary';
                @endphp
                <span class="badge badge-subtle-{{ $color }}">{{ $employee->employment_type }}</span>
              </td>
              <td>PKR {{ number_format($employee->salary, 0) }}</td>
              <td class="text-center">
                @php
                  $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'resigned' => 'warning', 'terminated' => 'danger'];
                  $sc = $statusColors[$employee->status] ?? 'secondary';
                @endphp
                <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ ucfirst($employee->status) }}</span>
              </td>
              <td class="text-center">
                @if($employee->user_id)
                  <span class="badge badge-subtle-primary rounded-pill">
                    <span class="fas fa-key me-1"></span>Active
                  </span>
                @else
                  <span class="text-muted fs-11">—</span>
                @endif
              </td>
              <td class="text-end px-3">
                <div class="d-flex justify-content-end gap-2">
                  <a class="btn btn-link p-0" href="{{ route('staff.show', $employee->id) }}" data-bs-toggle="tooltip" title="View Profile">
                    <span class="text-info fas fa-eye"></span>
                  </a>
                  <a class="btn btn-link p-0" href="{{ route('staff.edit', $employee->id) }}" data-bs-toggle="tooltip" title="Edit">
                    <span class="text-primary fas fa-edit"></span>
                  </a>
                  <form id="delete-form-staff-{{ $employee->id }}" action="{{ route('staff.destroy', $employee->id) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                  </form>
                  <a class="btn btn-link p-0" href="javascript:void(0);"
                     onclick="if(confirm('Remove {{ addslashes($employee->name) }} from staff? This will also disable their CMS login if they have one.')) { document.getElementById('delete-form-staff-{{ $employee->id }}').submit(); }"
                     data-bs-toggle="tooltip" title="Delete">
                    <span class="text-danger fas fa-trash-alt"></span>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center py-5 text-muted">
                <span class="fas fa-users fa-2x mb-2 d-block"></span>
                No staff members found. <a href="{{ route('staff.create') }}">Add your first employee</a>.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if($employees->hasPages())
    <div class="card-footer d-flex align-items-center justify-content-center bg-light">
      {{ $employees->links() }}
    </div>
  @endif
</div>
@endsection
