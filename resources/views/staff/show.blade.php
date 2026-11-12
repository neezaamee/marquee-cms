@extends('layouts.admin')

@section('title', 'Employee Profile: ' . $staff->name)

@section('content')
<div class="row g-3">
  {{-- Left Column: Profile Card --}}
  <div class="col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center pt-4">
        {{-- Photo --}}
        @if($staff->photo)
          <img src="{{ asset('storage/' . $staff->photo) }}" alt="{{ $staff->name }}"
               class="rounded-circle mb-3 border border-3 shadow-sm"
               style="width:120px;height:120px;object-fit:cover;border-color:var(--falcon-primary)!important">
        @else
          <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-200 border border-3 shadow-sm"
               style="width:120px;height:120px;border-color:var(--falcon-primary)!important">
            <span class="fas fa-user fa-3x text-500"></span>
          </div>
        @endif

        <h5 class="mb-1 fw-bold text-900">{{ $staff->name }}</h5>
        <p class="text-muted mb-1">{{ $staff->designation }}</p>
        <span class="badge bg-secondary-subtle text-secondary font-monospace fs-11 mb-2">{{ $staff->employee_id }}</span>

        {{-- Status Badge --}}
        @php
          $sc = ['active' => 'success', 'inactive' => 'secondary', 'resigned' => 'warning', 'terminated' => 'danger'];
        @endphp
        <div class="mb-3">
          <span class="badge bg-{{ $sc[$staff->status] ?? 'secondary' }}-subtle text-{{ $sc[$staff->status] ?? 'secondary' }} rounded-pill fs-11 px-3">
            {{ ucfirst($staff->status) }}
          </span>
        </div>

        {{-- Outstanding Advance Balance Callout --}}
        @php
          $pendingAdvance = $staff->pending_advance_balance;
        @endphp
        @if($pendingAdvance > 0)
          <div class="alert alert-warning-subtle text-warning-emphasis border border-warning-subtle py-2 px-3 fs-11 mb-3 text-start shadow-sm rounded-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="fas fa-hand-holding-usd me-1"></span>
                <strong>Pending Advance:</strong>
              </div>
              <span class="fw-bold fs-10">Rs. {{ number_format($pendingAdvance, 2) }}</span>
            </div>
            <div class="fs-11 text-600 mt-1">To be recovered in upcoming monthly payroll.</div>
          </div>
        @endif

        {{-- CMS Login Badge --}}
        @if($staff->users->isNotEmpty())
          <div class="alert alert-success-subtle border border-success-subtle py-2 px-3 fs-11 mb-3 text-start rounded-3" role="alert">
            <div class="fw-bold mb-1 text-success"><span class="fas fa-key me-1"></span>CMS Login Profiles:</div>
            @foreach($staff->users as $u)
              <div class="border-bottom border-200 py-1" style="border-bottom-style: dashed !important;">
                <strong>Email:</strong> {{ $u->email }}<br>
                <strong>Username:</strong> {{ $u->username }}<br>
                <strong>Branch:</strong> {{ $u->branch->name ?? 'All Branches' }}<br>
                <strong>Role:</strong> {{ ucwords(str_replace('_', ' ', $u->role->name ?? '—')) }}
              </div>
            @endforeach
          </div>
        @else
          <div class="alert alert-light border py-2 px-3 fs-11 mb-3 rounded-3" role="alert">
            <span class="fas fa-lock me-1 text-muted"></span>
            No CMS login access
          </div>
        @endif

        {{-- Action Buttons --}}
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-falcon-primary btn-sm fw-bold">
            <span class="fas fa-edit me-1"></span> Edit
          </a>
          <a href="{{ route('payroll.advances') }}" class="btn btn-falcon-default btn-sm fw-bold">
            <span class="fas fa-hand-holding-usd me-1 text-warning"></span> Advances
          </a>
          <a href="{{ route('staff.index') }}" class="btn btn-falcon-default btn-sm">
            <span class="fas fa-list me-1"></span> All Staff
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Right Column: 360° Employee Profile Tabs --}}
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-body-tertiary p-0 border-bottom">
        <ul class="nav nav-tabs border-0 px-3 pt-2" id="employeeProfileTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold fs-11 py-2 px-3" id="details-tab" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab" aria-controls="tab-details" aria-selected="true">
              <span class="fas fa-id-card me-1 text-primary"></span> Details &amp; Contact
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold fs-11 py-2 px-3" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button" role="tab" aria-controls="tab-attendance" aria-selected="false">
              <span class="fas fa-calendar-check me-1 text-info"></span> Attendance History
              <span class="badge bg-info-subtle text-info rounded-pill ms-1">{{ count($attendances) }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold fs-11 py-2 px-3" id="advances-tab" data-bs-toggle="tab" data-bs-target="#tab-advances" type="button" role="tab" aria-controls="tab-advances" aria-selected="false">
              <span class="fas fa-hand-holding-usd me-1 text-warning"></span> Salary Advances
              <span class="badge bg-warning-subtle text-warning rounded-pill ms-1">{{ $staff->salaryAdvances->count() }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold fs-11 py-2 px-3" id="payslips-tab" data-bs-toggle="tab" data-bs-target="#tab-payslips" type="button" role="tab" aria-controls="tab-payslips" aria-selected="false">
              <span class="fas fa-file-invoice-dollar me-1 text-success"></span> Payslips &amp; Payroll
              <span class="badge bg-success-subtle text-success rounded-pill ms-1">{{ $staff->payrollItems->count() }}</span>
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body p-3">
        <div class="tab-content" id="employeeProfileTabContent">

          {{-- Tab 1: Personal & Employment Details --}}
          <div class="tab-pane fade show active" id="tab-details" role="tabpanel" aria-labelledby="details-tab">
            <!-- Contact Information -->
            <div class="row navbar-vertical-label-wrapper mb-2 mt-1">
              <div class="col-auto navbar-vertical-label text-primary fw-bold fs-11">Contact Information</div>
              <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
            </div>
            <dl class="row fs-11 mb-3">
              <dt class="col-sm-4 text-muted">Mobile Number</dt>
              <dd class="col-sm-8 fw-semibold">{{ $staff->mobile_number }}</dd>

              <dt class="col-sm-4 text-muted">CNIC</dt>
              <dd class="col-sm-8 font-monospace fw-semibold">{{ $staff->cnic }}</dd>
            </dl>

            <!-- Job Information -->
            <div class="row navbar-vertical-label-wrapper mb-2 mt-3">
              <div class="col-auto navbar-vertical-label text-primary fw-bold fs-11">Job &amp; Branch Information</div>
              <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
            </div>
            <dl class="row fs-11 mb-3">
              <dt class="col-sm-4 text-muted">Assigned Branch</dt>
              <dd class="col-sm-8">{{ $staff->branch->name ?? 'All / Main Branch' }}</dd>

              <dt class="col-sm-4 text-muted">Designation</dt>
              <dd class="col-sm-8 fw-semibold">{{ $staff->designation }}</dd>

              <dt class="col-sm-4 text-muted">Employment Type</dt>
              <dd class="col-sm-8">
                @php
                  $typeColors = ['Permanent' => 'success', 'Contract' => 'info', 'Daily Wages' => 'warning', 'Part-Time' => 'secondary'];
                  $tc = $typeColors[$staff->employment_type] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }}">{{ $staff->employment_type }}</span>
              </dd>

              <dt class="col-sm-4 text-muted">Joining Date</dt>
              <dd class="col-sm-8">{{ \Carbon\Carbon::parse($staff->joining_date)->format('d M, Y') }}</dd>

              <dt class="col-sm-4 text-muted">Current Status</dt>
              <dd class="col-sm-8">
                <span class="badge bg-{{ $sc[$staff->status] ?? 'secondary' }}-subtle text-{{ $sc[$staff->status] ?? 'secondary' }} rounded-pill">
                  {{ ucfirst($staff->status) }}
                </span>
              </dd>
            </dl>

            <!-- Salary Information -->
            <div class="row navbar-vertical-label-wrapper mb-2 mt-3">
              <div class="col-auto navbar-vertical-label text-primary fw-bold fs-11">Salary Structure</div>
              <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
            </div>
            <dl class="row fs-11 mb-3">
              <dt class="col-sm-4 text-muted">Monthly Basic Salary</dt>
              <dd class="col-sm-8 fw-bolder text-success fs-10">PKR {{ number_format($staff->salary, 2) }}</dd>

              <dt class="col-sm-4 text-muted">Daily Wage Rate (30 Days)</dt>
              <dd class="col-sm-8 text-700">PKR {{ number_format($staff->salary / 30, 2) }} / day</dd>

              <dt class="col-sm-4 text-muted">Hourly Rate (8 Hours/Day)</dt>
              <dd class="col-sm-8 text-700">PKR {{ number_format(($staff->salary / 30) / 8, 2) }} / hour</dd>
            </dl>

            <!-- System Information -->
            <div class="row navbar-vertical-label-wrapper mb-2 mt-3">
              <div class="col-auto navbar-vertical-label text-muted fw-bold fs-11">System Audit Information</div>
              <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
            </div>
            <dl class="row fs-11 mb-0">
              <dt class="col-sm-4 text-muted">Employee Code</dt>
              <dd class="col-sm-8 font-monospace">{{ $staff->employee_id }}</dd>

              <dt class="col-sm-4 text-muted">Record Created</dt>
              <dd class="col-sm-8">{{ $staff->created_at->format('d M, Y H:i') }}</dd>

              <dt class="col-sm-4 text-muted">Last Updated</dt>
              <dd class="col-sm-8">{{ $staff->updated_at->format('d M, Y H:i') }}</dd>
            </dl>
          </div>

          {{-- Tab 2: Attendance History --}}
          <div class="tab-pane fade" id="tab-attendance" role="tabpanel" aria-labelledby="attendance-tab">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <h6 class="mb-0 fw-bold">Recent Attendance Records</h6>
                <span class="fs-11 text-muted">Showing the latest attendance entries marked for {{ $staff->name }}.</span>
              </div>
              <a href="{{ route('attendance.monthly') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-calendar-alt me-1 text-primary"></span> Monthly Attendance Sheet
              </a>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0 fs-11">
                <thead class="bg-body-secondary text-700 text-uppercase">
                  <tr>
                    <th class="ps-2 py-2">Date</th>
                    <th class="py-2 text-center">Status</th>
                    <th class="py-2 text-center">Overtime Hours</th>
                    <th class="py-2">Remarks / Notes</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($attendances as $att)
                    <tr>
                      <td class="ps-2 fw-semibold">
                        {{ \Carbon\Carbon::parse($att->date)->format('D, d M Y') }}
                      </td>
                      <td class="text-center">
                        @if($att->status === 'Present')
                          <span class="badge bg-success-subtle text-success">Present</span>
                        @elseif($att->status === 'Absent')
                          <span class="badge bg-danger-subtle text-danger">Absent</span>
                        @elseif($att->status === 'Late')
                          <span class="badge bg-warning-subtle text-warning">Late</span>
                        @elseif($att->status === 'Half Day')
                          <span class="badge bg-info-subtle text-info">Half Day</span>
                        @else
                          <span class="badge bg-secondary-subtle text-secondary">{{ $att->status }}</span>
                        @endif
                      </td>
                      <td class="text-center">
                        @if($att->overtime_hours > 0)
                          <span class="badge bg-primary-subtle text-primary">{{ $att->overtime_hours }} hrs</span>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      <td class="text-muted">
                        {{ $att->notes ?: '—' }}
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted">
                        No attendance records logged yet for this employee.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 3: Salary Advances & Loans --}}
          <div class="tab-pane fade" id="tab-advances" role="tabpanel" aria-labelledby="advances-tab">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <h6 class="mb-0 fw-bold">Salary Advances &amp; Loans History</h6>
                <span class="fs-11 text-muted">Mid-month advances disbursed and auto-deducted in monthly payroll.</span>
              </div>
              <a href="{{ route('payroll.advances') }}" class="btn btn-warning btn-sm fw-bold">
                <span class="fas fa-plus me-1"></span> Disburse Advance
              </a>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0 fs-11">
                <thead class="bg-body-secondary text-700 text-uppercase">
                  <tr>
                    <th class="ps-2 py-2">Date</th>
                    <th class="py-2 text-end">Amount</th>
                    <th class="py-2 text-center">Deduction Month</th>
                    <th class="py-2 text-center">Method</th>
                    <th class="py-2 text-center">Status</th>
                    <th class="py-2">Reason / Notes</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($staff->salaryAdvances as $adv)
                    <tr>
                      <td class="ps-2 fw-semibold">
                        {{ \Carbon\Carbon::parse($adv->advance_date)->format('d M Y') }}
                      </td>
                      <td class="text-end fw-bold text-900">
                        Rs. {{ number_format($adv->amount, 2) }}
                      </td>
                      <td class="text-center">
                        <span class="badge bg-light text-dark border">
                          {{ \Carbon\Carbon::parse($adv->deduction_month . '-01')->format('M Y') }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($adv->payment_method) }}</span>
                      </td>
                      <td class="text-center">
                        @if($adv->status === 'deducted')
                          <span class="badge bg-success-subtle text-success">
                            <span class="fas fa-check-circle me-1"></span> Settled
                          </span>
                        @elseif($adv->status === 'waived')
                          <span class="badge bg-secondary-subtle text-secondary">Waived</span>
                        @else
                          <span class="badge bg-warning-subtle text-warning">
                            <span class="fas fa-hourglass-half me-1"></span> Pending
                          </span>
                        @endif
                      </td>
                      <td class="text-muted">
                        {{ $adv->reason ?: '—' }}
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center py-4 text-muted">
                        No advance records found for this employee.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 4: Payslips & Salary History --}}
          <div class="tab-pane fade" id="tab-payslips" role="tabpanel" aria-labelledby="payslips-tab">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <h6 class="mb-0 fw-bold">Issued Salary Payslips</h6>
                <span class="fs-11 text-muted">Complete breakdown of generated monthly salary slips.</span>
              </div>
              <a href="{{ route('payroll.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-money-check-alt me-1 text-success"></span> Payroll Manager
              </a>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0 fs-11">
                <thead class="bg-body-secondary text-700 text-uppercase">
                  <tr>
                    <th class="ps-2 py-2">Slip #</th>
                    <th class="py-2">Month</th>
                    <th class="py-2 text-end">Basic</th>
                    <th class="py-2 text-end">OT + Allowances</th>
                    <th class="py-2 text-end">Deductions</th>
                    <th class="py-2 text-end">Net Salary</th>
                    <th class="py-2 text-center">Status</th>
                    <th class="pe-2 py-2 text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($staff->payrollItems as $slip)
                    <tr>
                      <td class="ps-2 fw-bold text-primary font-monospace">
                        {{ $slip->salary_slip_number }}
                      </td>
                      <td class="fw-semibold">
                        {{ $slip->payroll ? \Carbon\Carbon::parse($slip->payroll->payroll_month . '-01')->format('F Y') : '—' }}
                      </td>
                      <td class="text-end">
                        Rs. {{ number_format($slip->basic_salary, 2) }}
                      </td>
                      <td class="text-end text-success">
                        +Rs. {{ number_format($slip->overtime_amount + $slip->total_allowances, 2) }}
                      </td>
                      <td class="text-end text-danger">
                        -Rs. {{ number_format($slip->total_deductions, 2) }}
                      </td>
                      <td class="text-end fw-bolder text-primary">
                        Rs. {{ number_format($slip->net_salary, 2) }}
                      </td>
                      <td class="text-center">
                        @if($slip->payment_status === 'paid')
                          <span class="badge bg-success-subtle text-success">Paid</span>
                        @else
                          <span class="badge bg-warning-subtle text-warning">Unpaid</span>
                        @endif
                      </td>
                      <td class="pe-2 text-end">
                        <a href="{{ route('payroll.slip', $slip->id) }}" target="_blank" class="btn btn-falcon-default btn-sm py-0 px-2" title="Print Slip">
                          <span class="fas fa-print me-1"></span> Slip
                        </a>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center py-4 text-muted">
                        No payslips have been generated for this employee yet.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
