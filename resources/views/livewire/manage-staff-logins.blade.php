<div>
    <!-- Employee Info Bar -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="fas fa-key me-2 text-primary"></span>Manage CMS Logins for: <strong class="text-primary">{{ $staff->name }}</strong>
            </h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('staff.index') }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back to Staff List
            </a>
        </div>
        <div class="card-body bg-light border-top">
            <div class="row align-items-center">
                <div class="col-md-auto text-center mb-2 mb-md-0">
                    @if($staff->photo)
                        <img src="{{ asset('storage/' . $staff->photo) }}" alt="{{ $staff->name }}" class="rounded-circle border border-2 border-primary" width="60" height="60" style="object-fit:cover;">
                    @else
                        <div class="avatar avatar-3xl" style="width:60px;height:60px;background:var(--falcon-200);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
                            <span class="fas fa-user text-500 fs-4"></span>
                        </div>
                    @endif
                </div>
                <div class="col-md">
                    <h5 class="mb-1">{{ $staff->name }}</h5>
                    <p class="mb-0 fs-10 text-600">
                        <span class="badge badge-subtle-secondary me-2 font-monospace">{{ $staff->employee_id }}</span>
                        <strong>Designation:</strong> {{ $staff->designation }} | 
                        <strong>Primary Branch:</strong> {{ $staff->branch->name ?? '—' }} |
                        <strong>Status:</strong> <span class="badge badge-subtle-{{ $staff->status === 'active' ? 'success' : 'secondary' }} rounded-pill">{{ ucfirst($staff->status) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('error') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Logins List -->
        <div class="col-lg-8">
            <div class="card h-100 mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><span class="fas fa-list me-2"></span>Active Login Profiles</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3">Branch</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end px-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staff->users as $user)
                                    @if($editingUserId === $user->id)
                                        <!-- Edit Mode Row -->
                                        <tr>
                                            <td colspan="3" class="px-3 py-3">
                                                <div class="mb-1 text-muted fs-11">Editing login for: <strong class="text-primary">{{ $user->email }} ({{ $user->branch->name ?? 'All Branches' }})</strong></div>
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <label class="form-label mb-0 fs-11">Role *</label>
                                                        <select wire:model="edit_role_id" class="form-select form-select-sm @error('edit_role_id') is-invalid @enderror" required>
                                                            <option value="">Select Role...</option>
                                                            @foreach($roles as $r)
                                                                <option value="{{ $r->id }}">{{ $r->label }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('edit_role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label mb-0 fs-11">New Password (optional)</label>
                                                        <input wire:model="edit_password" type="password" class="form-control form-control-sm @error('edit_password') is-invalid @enderror" placeholder="Min 6 characters">
                                                        @error('edit_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                </div>
                                            </td>
                                            <td colspan="3" class="text-end px-3 align-bottom py-3">
                                                <button wire:click="saveEdit" class="btn btn-primary btn-xs me-1">Save</button>
                                                <button wire:click="cancelEditing" class="btn btn-falcon-default btn-xs">Cancel</button>
                                            </td>
                                        </tr>
                                    @else
                                        <!-- View Mode Row -->
                                        <tr>
                                            <td class="px-3 fw-semi-bold">{{ $user->branch->name ?? 'All Branches' }}</td>
                                            <td class="font-monospace text-700">{{ $user->username }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge badge-subtle-primary">{{ $user->role->label ?? '—' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <button wire:click="toggleStatus({{ $user->id }})" class="btn p-0 border-0" type="button" data-bs-toggle="tooltip" title="Click to Toggle Status">
                                                    <span class="badge badge-subtle-{{ $user->status === 'active' ? 'success' : 'secondary' }} rounded-pill cursor-pointer">
                                                        {{ ucfirst($user->status) }}
                                                    </span>
                                                </button>
                                            </td>
                                            <td class="text-end px-3">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button wire:click="startEditing({{ $user->id }})" class="btn btn-link p-0 text-primary" type="button" data-bs-toggle="tooltip" title="Reset Password / Change Role">
                                                        <span class="fas fa-user-cog"></span>
                                                    </button>
                                                    @if($user->id !== auth()->id())
                                                        <button wire:click="deleteLogin({{ $user->id }})" onclick="confirm('Are you sure you want to delete this login profile? This cannot be undone.') || event.stopImmediatePropagation()" class="btn btn-link p-0 text-danger" type="button" data-bs-toggle="tooltip" title="Delete Login Profile">
                                                            <span class="fas fa-trash-alt"></span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <span class="fas fa-lock fa-2x mb-2 d-block text-400"></span>
                                            No login profiles configured for this staff member.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Login Form -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><span class="fas fa-plus me-2 text-success"></span>Create Login Profile</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="addLogin">
                        <div class="mb-3">
                            <label class="form-label" for="branch_id">Branch *</label>
                            <select wire:model="branch_id" class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" required {{ count($branches) === 1 ? 'disabled' : '' }}>
                                <option value="">Select Branch...</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Login Email *</label>
                            <input wire:model="email" class="form-control @error('email') is-invalid @enderror" id="email" type="email" placeholder="e.g. ajmal.brancha@gmail.com" required />
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="username">Username *</label>
                            <input wire:model="username" class="form-control @error('username') is-invalid @enderror" id="username" type="text" placeholder="e.g. ajmal7122" required />
                            <div class="fs-11 text-muted mt-1">Alphanumeric, dots, dashes, underscores only.</div>
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="role_id">Role *</label>
                            <select wire:model="role_id" class="form-select @error('role_id') is-invalid @enderror" id="role_id" required>
                                <option value="">Select Role...</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->label }}</option>
                                @endforeach
                            </select>
                            @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password *</label>
                            <input wire:model="password" class="form-control @error('password') is-invalid @enderror" id="password" type="password" placeholder="Password min 6 chars" required />
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button class="btn btn-primary btn-sm d-block w-100" type="submit">
                            <span wire:loading class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Create Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
