@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-12">
        <!-- Success Alert -->
        @if(session('success'))
            <div class="alert alert-success border-2 d-flex align-items-center role-alert mb-3" role="alert">
                <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-10"></span></div>
                <p class="mb-0 flex-1">{{ session('success') }}</p>
                <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Error Alerts -->
        @if($errors->any())
            <div class="alert alert-danger border-2 d-flex align-items-center role-alert mb-3" role="alert">
                <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-10"></span></div>
                <div class="flex-1">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Profile Header Banner -->
        <div class="card mb-3">
            <div class="card-header position-relative min-vh-25 mb-7">
                <!-- Cover Image -->
                <div class="bg-holder rounded-3 rounded-bottom-0" style="background-image:url({{ asset('assets/img/generic/4.jpg') }}); background-position: center; background-size: cover;"></div>
                
                <!-- Avatar Positioned on Banner (Responsive styling handled by Falcon class 'avatar-profile') -->
                <div class="avatar avatar-5xl avatar-profile">
                    @if($user->profile_photo)
                        <img class="rounded-circle img-thumbnail shadow-sm bg-white" src="{{ asset('storage/' . $user->profile_photo) }}" alt="Avatar" style="object-fit: cover;" />
                    @elseif($user->employee && $user->employee->photo)
                        <img class="rounded-circle img-thumbnail shadow-sm bg-white" src="{{ asset('storage/' . $user->employee->photo) }}" alt="Avatar" style="object-fit: cover;" />
                    @else
                        <div class="avatar-name rounded-circle bg-subtle-primary text-primary fw-bold img-thumbnail shadow-sm d-flex align-items-center justify-content-center" style="width: 100%; height: 100%; font-size: 2.5rem;">
                            <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <h4 class="mb-1 text-900 d-flex align-items-center">
                            {{ $user->name }}
                            <span class="badge badge-subtle-{{ $user->status === 'active' ? 'success' : 'secondary' }} rounded-pill fs-11 ms-2">
                                {{ ucfirst($user->status) }}
                            </span>
                        </h4>
                        <h6 class="fs-9 fw-normal text-600 mb-2">{{ $user->role->label ?? 'No Role Assigned' }}</h6>
                        <p class="text-500 mb-0">
                            <span class="fas fa-building me-1"></span>{{ $user->marquee->name ?? 'SaaS Administration' }} 
                            @if($user->branch)
                                &bull; <span class="fas fa-map-marker-alt me-1 ms-1"></span>{{ $user->branch->name }}
                            @endif
                        </p>
                        <div class="border-bottom border-dashed my-4 d-lg-none"></div>
                    </div>
                    <div class="col-lg-4 mt-3 mt-lg-0 d-flex justify-content-lg-end align-items-center">
                        <div class="px-3 border-end border-300 text-center">
                            <h6 class="text-600 fs-11 mb-1">Bookings Created</h6>
                            <h3 class="text-primary mb-0 fw-semi-bold">{{ $bookingsCount }}</h3>
                        </div>
                        @if($bookingsValue > 0)
                        <div class="px-3 border-end border-300 text-center">
                            <h6 class="text-600 fs-11 mb-1">Bookings Value</h6>
                            <h3 class="text-success mb-0 fw-semi-bold">Rs. {{ number_format($bookingsValue) }}</h3>
                        </div>
                        @endif
                        <div class="ps-3 text-center">
                            <h6 class="text-600 fs-11 mb-1">Audit Logs</h6>
                            <h3 class="text-info mb-0 fw-semi-bold">{{ $activityCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs and Details Row -->
<div class="row g-3">
    <!-- Left Column: Navigation & Form details -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-light p-0 border-bottom">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs border-0" id="profileTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 px-4 border-0 rounded-0" id="personal-tab" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button" role="tab" aria-controls="tab-personal" aria-selected="true">
                            <span class="fas fa-user-edit me-2"></span>Personal Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 border-0 rounded-0" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab" aria-controls="tab-security" aria-selected="false">
                            <span class="fas fa-shield-alt me-2"></span>Security & Credentials
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 border-0 rounded-0" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#tab-permissions" type="button" role="tab" aria-controls="tab-permissions" aria-selected="false">
                            <span class="fas fa-key me-2"></span>Access & Permissions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 border-0 rounded-0" id="activity-tab" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button" role="tab" aria-controls="tab-activity" aria-selected="false">
                            <span class="fas fa-history me-2"></span>Activity Log
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="profileTabContent">
                    
                    <!-- TAB 1: PERSONAL DETAILS & EDIT PROFILE -->
                    <div class="tab-pane fade show active" id="tab-personal" role="tabpanel" aria-labelledby="personal-tab">
                        <h5 class="mb-3 text-800"><span class="fas fa-user-circle text-primary me-2"></span>Edit Profile Details</h5>
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Display Name <span class="text-danger">*</span></label>
                                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="username">Username</label>
                                    <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" type="text" value="{{ old('username', $user->username) }}" />
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Phone / Mobile</label>
                                    <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" />
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="profile_photo">Upload Profile Photo</label>
                                <input class="form-control @error('profile_photo') is-invalid @enderror" id="profile_photo" name="profile_photo" type="file" accept="image/*" />
                                <div class="form-text fs-11">Supported formats: JPEG, PNG, JPG, GIF. Max file size: 2MB.</div>
                                @error('profile_photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary px-4" type="submit">
                                    <span class="fas fa-save me-2"></span>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- TAB 2: CHANGE PASSWORD -->
                    <div class="tab-pane fade" id="tab-security" role="tabpanel" aria-labelledby="security-tab">
                        <h5 class="mb-3 text-800"><span class="fas fa-lock text-warning me-2"></span>Update Account Password</h5>
                        <form action="{{ route('profile.password') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="current_password">Current Password <span class="text-danger">*</span></label>
                                <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" required />
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="password">New Password <span class="text-danger">*</span></label>
                                    <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required />
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
                                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required />
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-warning px-4" type="submit">
                                    <span class="fas fa-key me-2"></span>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- TAB 3: ROLES & SYSTEM PERMISSIONS -->
                    <div class="tab-pane fade" id="tab-permissions" role="tabpanel" aria-labelledby="permissions-tab">
                        <h5 class="mb-2 text-800"><span class="fas fa-shield-alt text-success me-2"></span>Access Level & Role Details</h5>
                        <div class="bg-light p-3 rounded mb-4">
                            <div class="row align-items-center">
                                <div class="col-sm-auto">
                                    <div class="avatar avatar-3xl">
                                        <div class="avatar-name rounded-circle bg-subtle-success text-success fs-2 d-flex align-items-center justify-content-center fw-bold">
                                            <span>{{ strtoupper(substr($user->role->label ?? 'R', 0, 1)) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col mt-2 mt-sm-0">
                                    <h4 class="text-primary mb-1">{{ $user->role->label ?? 'No Role Assigned' }}</h4>
                                    <p class="text-600 mb-0 fs-10">{{ $user->role->description ?? 'This account does not have a description specified for its role.' }}</p>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3 text-800"><span class="fas fa-shield-virus text-info me-2"></span>Active System Permissions</h5>
                        <div>
                            @if($user->isSuperAdmin())
                                <div class="alert alert-warning border-0 d-flex align-items-center role-alert py-2 mb-0" role="alert">
                                    <div class="bg-warning me-3 icon-item py-1"><span class="fas fa-exclamation-circle text-white fs-11"></span></div>
                                    <p class="mb-0 flex-1 fw-semi-bold text-dark fs-10">This profile has Super Administrator access. You have full read, write, update, and delete access across the entire application and SaaS environment.</p>
                                </div>
                            @elseif($user->role && $user->role->permissions->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($user->role->permissions as $permission)
                                        <span class="badge badge-subtle-success rounded-pill px-3 py-2 fs-10 d-flex align-items-center">
                                            <span class="fas fa-check-double me-1 text-success"></span>{{ $permission->label }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted fs-11">No specific permissions are enabled for this role profile.</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- TAB 4: AUDIT ACTIVITY LOGS -->
                    <div class="tab-pane fade" id="tab-activity" role="tabpanel" aria-labelledby="activity-tab">
                        <h5 class="mb-3 text-800"><span class="fas fa-history text-info me-2"></span>Recent User Activity Logs</h5>
                        
                        @if($activityLogs->isNotEmpty())
                            <div class="table-responsive scrollbar">
                                <table class="table table-hover table-striped fs-10">
                                    <thead class="bg-200 text-900">
                                        <tr>
                                            <th>Action</th>
                                            <th>Activity Description</th>
                                            <th>IP Address</th>
                                            <th>Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activityLogs as $log)
                                            <tr>
                                                <td>
                                                    <span class="badge rounded-pill badge-subtle-{{ $log->action === 'created' ? 'success' : ($log->action === 'updated' ? 'primary' : 'danger') }} px-2 py-1">
                                                        {{ ucfirst($log->action) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-semi-bold">{{ $log->description }}</div>
                                                    @if($log->user_agent)
                                                        <div class="text-500 fs-11 text-truncate" style="max-width: 320px;" title="{{ $log->user_agent }}">
                                                            {{ $log->user_agent }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td><code>{{ $log->ip_address }}</code></td>
                                                <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination links -->
                            <div class="mt-3">
                                {{ $activityLogs->links() }}
                            </div>
                        @else
                            <div class="text-center py-4">
                                <span class="fas fa-folder-open text-300 fs-1 mb-2"></span>
                                <p class="text-500 mb-0">No recent activity logs recorded for this user profile.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Employee profile meta & stats -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0"><span class="fas fa-id-card text-primary me-2"></span>Linked Profile Meta</h5>
            </div>
            <div class="card-body">
                @if($user->employee)
                    <div class="mb-4 text-center">
                        <div class="avatar avatar-4xl shadow-sm img-thumbnail rounded-circle mx-auto bg-light" style="width: 90px; height: 90px; display: flex; align-items: center; justify-content: center;">
                            @if($user->employee->photo)
                                <img class="rounded-circle" src="{{ asset('storage/' . $user->employee->photo) }}" alt="Employee Photo" style="object-fit: cover; height: 100%; width: 100%;" />
                            @else
                                <div class="avatar-name rounded-circle bg-subtle-primary text-primary h-100 fs-2 d-flex align-items-center justify-content-center fw-bold">
                                    <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                </div>
                            @endif
                        </div>
                        <h5 class="mt-2 mb-1">{{ $user->employee->name }}</h5>
                        <p class="text-600 fs-10 mb-0">{{ $user->employee->designation }}</p>
                        <span class="badge badge-subtle-info rounded-pill fs-11 mt-1">{{ $user->employee->employee_id }}</span>
                    </div>

                    <div class="border-top pt-3">
                        <div class="row g-3 mb-2">
                            <div class="col-6">
                                <h6 class="text-500 fs-11 mb-1">CNIC / ID Number</h6>
                                <p class="fw-semi-bold mb-0 fs-10">{{ $user->employee->cnic ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6">
                                <h6 class="text-500 fs-11 mb-1">Joining Date</h6>
                                <p class="fw-semi-bold mb-0 fs-10">{{ $user->employee->joining_date ? \Carbon\Carbon::parse($user->employee->joining_date)->format('M d, Y') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-6">
                                <h6 class="text-500 fs-11 mb-1">Employment Type</h6>
                                <p class="fw-semi-bold mb-0 fs-10">{{ $user->employee->employment_type ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6">
                                <h6 class="text-500 fs-11 mb-1">Salary Status</h6>
                                <p class="fw-semi-bold mb-0 fs-10">
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasRole('owner') || auth()->id() === $user->id)
                                        Rs. {{ number_format($user->employee->salary) }}
                                    @else
                                        <span class="text-muted italic">Confidential</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="text-500 fs-11 mb-1">Official Mobile</h6>
                                <p class="fw-semi-bold mb-0 fs-10 text-primary">{{ $user->employee->mobile_number ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 my-3">
                        <span class="fas fa-user-tie text-300 fs-2 mb-3"></span>
                        <h6 class="text-700">Standalone User Account</h6>
                        <p class="text-500 fs-10 mx-3 mb-0">This profile is a standalone system account and is not linked to any specific employee record in the staff directory.</p>
                    </div>
                @endif

                <div class="border-top mt-4 pt-3">
                    <h6 class="text-800 mb-2"><span class="fas fa-info-circle text-info me-2"></span>Account Timestamps</h6>
                    <ul class="list-unstyled mb-0 fs-11 text-600">
                        <li class="mb-1 d-flex justify-content-between">
                            <span>Created At:</span>
                            <span class="fw-semi-bold">{{ $user->created_at->format('M d, Y h:i A') }}</span>
                        </li>
                        @if($user->updated_at)
                        <li class="d-flex justify-content-between">
                            <span>Last Updated:</span>
                            <span class="fw-semi-bold">{{ $user->updated_at->format('M d, Y h:i A') }}</span>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Check if there is a hash in the URL
        const hash = window.location.hash;
        if (hash) {
            const tabEl = document.querySelector(`button[data-bs-target="${hash}"]`);
            if (tabEl) {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }

        // Update hash on tab change
        const tabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function (e) {
                const target = e.target.getAttribute('data-bs-target');
                history.pushState(null, null, target);
            });
        });
    });
</script>
@endsection

@endsection
