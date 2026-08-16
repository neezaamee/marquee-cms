<div>
  <!-- Header Breadcrumb & Actions -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="row flex-between-center">
        <div class="col-sm-auto mb-2 mb-sm-0">
          <h5 class="mb-0 text-primary fw-bold">
            <i class="fas fa-database me-2"></i>Backup Management
          </h5>
          <p class="fs-10 text-600 mb-0">Manage system database dumps, automated schedules, data restoration, and security audit logs.</p>
        </div>
        <div class="col-sm-auto">
          <button class="btn btn-sm btn-primary" wire:click="setTab('manual')">
            <i class="fas fa-plus-circle me-1"></i>New Manual Backup
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Flash Messages -->
  @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Top Metric Cards -->
  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-md-3">
      <div class="card overflow-hidden style="min-height: 100px;">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-xl me-3 bg-subtle-primary rounded-circle">
              <div class="avatar-name text-primary"><i class="fas fa-archive"></i></div>
            </div>
            <div>
              <h6 class="text-700 mb-0">Total Backups</h6>
              <h4 class="fw-bold mb-0 text-primary">{{ $totalBackupsCount }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-md-3">
      <div class="card overflow-hidden style="min-height: 100px;">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-xl me-3 bg-subtle-success rounded-circle">
              <div class="avatar-name text-success"><i class="fas fa-hdd"></i></div>
            </div>
            <div>
              <h6 class="text-700 mb-0">Total Storage</h6>
              <h4 class="fw-bold mb-0 text-success">
                @if($totalStorageBytes >= 1073741824)
                  {{ number_format($totalStorageBytes / 1073741824, 2) }} GB
                @elseif($totalStorageBytes >= 1048576)
                  {{ number_format($totalStorageBytes / 1048576, 2) }} MB
                @else
                  {{ number_format($totalStorageBytes / 1024, 2) }} KB
                @endif
              </h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-md-3">
      <div class="card overflow-hidden style="min-height: 100px;">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-xl me-3 bg-subtle-info rounded-circle">
              <div class="avatar-name text-info"><i class="fas fa-clock"></i></div>
            </div>
            <div>
              <h6 class="text-700 mb-0">Last Successful</h6>
              <h6 class="fw-bold mb-0 text-dark">
                {{ $lastBackup ? $lastBackup->created_at->diffForHumans() : 'No backups yet' }}
              </h6>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-md-3">
      <div class="card overflow-hidden style="min-height: 100px;">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-xl me-3 bg-subtle-warning rounded-circle">
              <div class="avatar-name text-warning"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div>
              <h6 class="text-700 mb-0">Schedule Status</h6>
              <span class="badge bg-subtle-success text-success fw-bold">Active (Daily 02:00)</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <ul class="nav nav-tabs border-bottom mb-3" id="backupTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link {{ $activeTab === 'manual' ? 'active fw-bold border-primary text-primary' : 'text-600' }}" wire:click="setTab('manual')">
        <i class="fas fa-download me-1"></i>Manual Backup
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link {{ $activeTab === 'scheduled' ? 'active fw-bold border-primary text-primary' : 'text-600' }}" wire:click="setTab('scheduled')">
        <i class="fas fa-calendar-check me-1"></i>Scheduled Backups
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link {{ $activeTab === 'restore' ? 'active fw-bold border-primary text-primary' : 'text-600' }}" wire:click="setTab('restore')">
        <i class="fas fa-history me-1"></i>Restore Backup
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link {{ $activeTab === 'activity_logs' ? 'active fw-bold border-primary text-primary' : 'text-600' }}" wire:click="setTab('activity_logs')">
        <i class="fas fa-shield-alt me-1"></i>Activity & Audit Logs
      </button>
    </li>
  </ul>

  <!-- TAB 1: MANUAL BACKUP -->
  @if ($activeTab === 'manual')
    <div class="row g-3">
      <!-- Create Backup Form Card -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header bg-body-tertiary">
            <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-1 text-primary"></i>Generate Backup Archive</h6>
          </div>
          <div class="card-body">
            <form wire:submit.prevent="createBackup">
              <div class="mb-3">
                <label class="form-label fw-semibold">Backup Scope</label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="typeDbOnly" value="db_only" wire:model="backupType">
                  <label class="form-check-label" for="typeDbOnly">
                    <strong>Database Only (.sql + .zip)</strong>
                    <div class="fs-10 text-500">Dumps all MySQL/SQLite database tables. Highly compact.</div>
                  </label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="radio" id="typeFull" value="full" wire:model="backupType">
                  <label class="form-check-label" for="typeFull">
                    <strong>Full Backup (DB + Uploaded Files)</strong>
                    <div class="fs-10 text-500">Includes database dump + public media attachments.</div>
                  </label>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Backup Note / Description (Optional)</label>
                <textarea class="form-control" rows="3" wire:model="backupNotes" placeholder="e.g. Pre-deployment snapshot before major release"></textarea>
              </div>

              <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                <span wire:loading.remove><i class="fas fa-play me-1"></i>Start Backup Generation</span>
                <span wire:loading><i class="fas fa-spinner fa-spin me-1"></i>Generating Backup Archive...</span>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Backup History Table Card -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold"><i class="fas fa-list me-1 text-primary"></i>Available Backup Snapshots</h6>
            <span class="badge bg-subtle-primary text-primary">{{ $backups->total() }} Archives</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped align-middle mb-0 fs-10">
                <thead class="bg-200">
                  <tr>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Trigger</th>
                    <th>Created At</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($backups as $backup)
                    <tr>
                      <td class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $backup->file_name }}">
                        <i class="fas fa-file-archive me-1 text-warning"></i>{{ $backup->file_name }}
                      </td>
                      <td>
                        @if($backup->type === 'full')
                          <span class="badge bg-subtle-primary text-primary">Full</span>
                        @else
                          <span class="badge bg-subtle-info text-info">DB Only</span>
                        @endif
                      </td>
                      <td>{{ $backup->human_size }}</td>
                      <td>
                        @if($backup->trigger_type === 'scheduled')
                          <span class="badge bg-subtle-warning text-warning"><i class="fas fa-clock me-1"></i>Scheduled</span>
                        @else
                          <span class="badge bg-subtle-secondary text-secondary"><i class="fas fa-user me-1"></i>Manual</span>
                        @endif
                      </td>
                      <td>{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                      <td>
                        @if($backup->status === 'completed')
                          <span class="badge bg-subtle-success text-success"><i class="fas fa-check-circle me-1"></i>Completed</span>
                        @elseif($backup->status === 'in_progress')
                          <span class="badge bg-subtle-warning text-warning"><i class="fas fa-spinner fa-spin me-1"></i>In Progress</span>
                        @else
                          <span class="badge bg-subtle-danger text-danger"><i class="fas fa-times-circle me-1"></i>Failed</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @if($backup->status === 'completed')
                          <a href="{{ route('super-admin.backups.download', $backup->id) }}" class="btn btn-xs btn-outline-success me-1" title="Download Backup Archive">
                            <i class="fas fa-download"></i>
                          </a>
                          <button class="btn btn-xs btn-outline-primary me-1" wire:click="prepareRestore({{ $backup->id }})" title="Restore Data">
                            <i class="fas fa-history"></i>
                          </button>
                        @endif
                        <button class="btn btn-xs btn-outline-danger" onclick="confirm('Delete this backup archive permanently?') || event.stopImmediatePropagation()" wire:click="deleteBackup({{ $backup->id }})" title="Delete Backup">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-archive fa-2x mb-2"></i>
                        <p class="mb-0">No backup archives available. Click "Start Backup Generation" to create one.</p>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          @if ($backups->hasPages())
            <div class="card-footer bg-body-tertiary py-2">
              {{ $backups->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif

  <!-- TAB 2: SCHEDULED BACKUPS -->
  @if ($activeTab === 'scheduled')
    <div class="card col-lg-8 mx-auto">
      <div class="card-header bg-body-tertiary">
        <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-1 text-primary"></i>Automated Backup Schedule Settings</h6>
      </div>
      <div class="card-body">
        <form wire:submit.prevent="saveScheduleSettings">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Schedule Frequency</label>
              <select class="form-select" wire:model="scheduleFrequency">
                <option value="disabled">Disabled</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly (Every Sunday)</option>
                <option value="monthly">Monthly (1st of Month)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Execution Time (24h)</label>
              <input type="time" class="form-control" wire:model="scheduleTime">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Retention Policy (Days to Keep)</label>
              <input type="number" class="form-control" wire:model="retentionDays" min="1" max="365">
              <div class="fs-10 text-500">Backups older than this number of days will be automatically purged.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Backup Scope</label>
              <select class="form-select" wire:model="scheduleType">
                <option value="db_only">Database Only (.sql zip)</option>
                <option value="full">Full Backup (DB + Media Files)</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Alert Notification Email</label>
              <input type="email" class="form-control" wire:model="notificationEmail" placeholder="admin@marquee.com">
              <div class="fs-10 text-500">Email address to notify upon backup completion or failure.</div>
            </div>
          </div>

          <div class="mt-4 text-end">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i>Save Schedule Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <!-- TAB 3: RESTORE BACKUP -->
  @if ($activeTab === 'restore')
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card h-100 border-warning">
          <div class="card-header bg-warning-subtle text-warning-emphasis">
            <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Upload External Backup Archive</h6>
          </div>
          <div class="card-body">
            <p class="fs-10 text-600">Select a previously created `.zip` or `.sql` backup file from your computer to restore database tables.</p>
            <form wire:submit.prevent="prepareUploadRestore">
              <div class="mb-3">
                <label class="form-label fw-semibold">Select Backup File (.zip or .sql)</label>
                <input type="file" class="form-control" wire:model="uploadedBackup">
                @error('uploadedBackup') <span class="text-danger fs-10">{{ $message }}</span> @enderror
              </div>

              <button type="submit" class="btn btn-warning w-100" @if(!$uploadedBackup) disabled @endif>
                <i class="fas fa-upload me-1"></i>Proceed to Restore
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header bg-body-tertiary">
            <h6 class="mb-0 fw-bold"><i class="fas fa-history me-1 text-primary"></i>Restore from Existing Snapshots</h6>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              @forelse ($backups->where('status', 'completed') as $backup)
                <div class="list-group-item d-flex align-items-center justify-content-between py-2">
                  <div>
                    <div class="fw-bold text-dark fs-10">{{ $backup->file_name }}</div>
                    <div class="fs-10 text-500">{{ $backup->human_size }} &bull; Created {{ $backup->created_at->diffForHumans() }}</div>
                  </div>
                  <button class="btn btn-sm btn-outline-primary" wire:click="prepareRestore({{ $backup->id }})">
                    <i class="fas fa-redo me-1"></i>Restore
                  </button>
                </div>
              @empty
                <div class="p-3 text-center text-muted fs-10">No completed backup archives found to restore.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- TAB 4: ACTIVITY & AUDIT LOGS -->
  @if ($activeTab === 'activity_logs')
    <div class="card">
      <div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="fas fa-shield-alt me-1 text-primary"></i>Backup Download & Restoration Audit Logs</h6>
        <div class="d-flex align-items-center">
          <label class="fs-10 text-600 me-2 mb-0">Filter Action:</label>
          <select class="form-select form-select-sm" wire:model.live="logActionFilter" style="width: 180px;">
            <option value="">All Actions</option>
            <option value="backup_downloaded">Downloads Only</option>
            <option value="backup_restored">Restorations Only</option>
            <option value="backup_created">Creations Only</option>
            <option value="backup_deleted">Deletions Only</option>
            <option value="schedule_updated">Schedule Updates</option>
          </select>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0 fs-10">
            <thead class="bg-200">
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>File Name</th>
                <th>Details</th>
                <th>IP Address</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($activityLogs as $log)
                <tr>
                  <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                  <td class="fw-semibold">{{ $log->user ? $log->user->name : 'System Scheduler' }}</td>
                  <td>
                    @if($log->action === 'backup_downloaded')
                      <span class="badge bg-subtle-success text-success"><i class="fas fa-download me-1"></i>Downloaded</span>
                    @elseif($log->action === 'backup_restored')
                      <span class="badge bg-subtle-warning text-warning"><i class="fas fa-history me-1"></i>Restored</span>
                    @elseif($log->action === 'backup_created')
                      <span class="badge bg-subtle-primary text-primary"><i class="fas fa-plus-circle me-1"></i>Created</span>
                    @elseif($log->action === 'backup_deleted')
                      <span class="badge bg-subtle-danger text-danger"><i class="fas fa-trash me-1"></i>Deleted</span>
                    @else
                      <span class="badge bg-subtle-secondary text-secondary">{{ $log->action }}</span>
                    @endif
                  </td>
                  <td class="text-truncate" style="max-width: 180px;">{{ $log->file_name ?? '-' }}</td>
                  <td class="text-wrap" style="max-width: 250px;">{{ $log->description }}</td>
                  <td><code>{{ $log->ip_address ?? '127.0.0.1' }}</code></td>
                  <td>
                    @if($log->status === 'success')
                      <span class="badge bg-subtle-success text-success">Success</span>
                    @else
                      <span class="badge bg-subtle-danger text-danger">Failed</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                    <p class="mb-0">No audit log records found for the selected criteria.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if ($activityLogs->hasPages())
        <div class="card-footer bg-body-tertiary py-2">
          {{ $activityLogs->links() }}
        </div>
      @endif
    </div>
  @endif

  <!-- RESTORE CONFIRMATION MODAL -->
  @if ($isRestoreModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Data Restoration</h5>
            <button type="button" class="btn-close btn-close-white" wire:click="closeRestoreModal"></button>
          </div>
          <div class="modal-content-body p-3">
            <div class="alert alert-danger" role="alert">
              <strong>CRITICAL WARNING:</strong> Restoring database tables will overwrite current database records with the contents of the backup snapshot. This process cannot be undone.
            </div>

            <p class="fs-10 text-700">To proceed with data restoration, please type <strong>RESTORE</strong> in capital letters below:</p>

            <input type="text" class="form-control mb-3" wire:model="restoreConfirmationText" placeholder="Type RESTORE to confirm">

            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" wire:click="closeRestoreModal">Cancel</button>
              <button type="button" class="btn btn-danger" wire:click="executeRestore" @if(trim(strtoupper($restoreConfirmationText)) !== 'RESTORE') disabled @endif>
                <i class="fas fa-redo me-1"></i>Confirm & Execute Restore
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
