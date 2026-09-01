<div>
    <!-- Page Header -->
    <div class="row align-items-center justify-content-between g-3 mb-4">
        <div class="col-12 col-md-auto">
            <h1 class="h3 mb-0 text-gray-800">Access Control Matrix</h1>
            <p class="mb-0 text-muted fs-10">Map permissions and fine-grained access policies directly to security roles. Changes take effect in real-time.</p>
        </div>
    </div>

    <!-- Instructions / Context -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 bg-light rounded d-flex align-items-start gap-3">
            <div class="bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                <span class="fas fa-info-circle fs-8"></span>
            </div>
            <div>
                <h6 class="mb-1 text-800 fw-bold">Access Matrix Rules</h6>
                <ul class="mb-0 fs-10 text-600 ps-3">
                    <li><strong>Super Administrators</strong> have global, immutable full access (all checkboxes are pre-selected and locked).</li>
                    <li><strong>Business Owners</strong> and Marquee Owners have full access to their company resources, but cannot edit this global role configuration.</li>
                    <li>Only the <strong>Super Admin</strong> can check/uncheck permissions on this page. Other administrators can view this page as read-only.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Matrix Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 text-900">Roles vs. Permissions Matrix</h5>
        </div>

        <div class="card-body p-0">
            <!-- Messages -->
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center m-3" role="alert">
                    <div class="bg-success text-white rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <span class="fas fa-check fs-10"></span>
                    </div>
                    <p class="mb-0 flex-1 fw-semi-bold">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger text-white rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <span class="fas fa-times fs-10"></span>
                    </div>
                    <p class="mb-0 flex-1 fw-semi-bold">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-bordered table-hover align-middle mb-0 fs-10">
                    <thead class="bg-light text-900">
                        <tr>
                            <th class="align-middle px-3 py-3" style="width: 30%;">Permission / Capability</th>
                            @foreach($roles as $role)
                                <th class="align-middle text-center py-3" style="width: calc(70% / {{ count($roles) }});">
                                    <div class="fw-bold text-800">{{ $role->label }}</div>
                                    <span class="font-monospace fs-11 text-400">({{ $role->name }})</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedPermissions as $category => $permissions)
                            <!-- Category Header Row -->
                            <tr class="bg-200">
                                <td colspan="{{ count($roles) + 1 }}" class="py-2 px-3 fw-bold text-700 uppercase tracking-wider fs-11">
                                    <span class="fas fa-folder-open me-2 text-primary"></span>{{ $category }}
                                </td>
                            </tr>
                            
                            @foreach($permissions as $perm)
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="fw-semi-bold text-800">{{ $perm->label }}</div>
                                        <span class="font-monospace text-500 fs-11">{{ $perm->name }}</span>
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="text-center py-2">
                                            @php
                                                $hasPerm = $role->permissions->contains('id', $perm->id);
                                                $isSuperAdminRole = $role->name === 'super_admin';
                                                $canEdit = auth()->user()->isSuperAdmin() && !$isSuperAdminRole;
                                            @endphp
                                            
                                            <div class="form-check form-switch d-inline-block">
                                                <input 
                                                    type="checkbox" 
                                                    class="form-check-input @if($isSuperAdminRole) bg-primary border-primary @endif" 
                                                    style="cursor: @if($canEdit) pointer @else not-allowed @endif;"
                                                    @if($hasPerm || $isSuperAdminRole) checked @endif
                                                    @if(!$canEdit) disabled @endif
                                                    @if($canEdit) wire:click="togglePermission({{ $role->id }}, {{ $perm->id }})" @endif
                                                />
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
