<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Marquee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.admin')]
class ActivityLogManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';

    public string $selectedUser = '';

    public string $selectedAction = '';

    public string $selectedModel = '';

    public string $selectedMarquee = '';

    public string $datePreset = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 25;

    // View mode: 'timeline' or 'table'
    public string $viewMode = 'timeline';

    // Modal state
    public ?int $selectedLogId = null;

    public ?array $selectedLogDetails = null;

    public function mount()
    {
        $user = Auth::user();
        abort_unless(
            $user && ($user->isSuperAdmin() || $user->isBusinessOwner()),
            403,
            'Unauthorized access to audit and activity logs.'
        );

        // For business owner with single marquee, default to that marquee
        if ($user->isBusinessOwner() && $user->marquee_id) {
            $this->selectedMarquee = (string) $user->getActiveMarqueeId();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedUser()
    {
        $this->resetPage();
    }

    public function updatingSelectedAction()
    {
        $this->resetPage();
    }

    public function updatingSelectedModel()
    {
        $this->resetPage();
    }

    public function updatingSelectedMarquee()
    {
        $this->resetPage();
    }

    public function updatingDatePreset()
    {
        $this->resetPage();
        if ($this->datePreset === 'today') {
            $this->dateFrom = now()->startOfDay()->format('Y-m-d');
            $this->dateTo = now()->endOfDay()->format('Y-m-d');
        } elseif ($this->datePreset === 'yesterday') {
            $this->dateFrom = now()->subDay()->startOfDay()->format('Y-m-d');
            $this->dateTo = now()->subDay()->endOfDay()->format('Y-m-d');
        } elseif ($this->datePreset === '7days') {
            $this->dateFrom = now()->subDays(7)->startOfDay()->format('Y-m-d');
            $this->dateTo = now()->endOfDay()->format('Y-m-d');
        } elseif ($this->datePreset === '30days') {
            $this->dateFrom = now()->subDays(30)->startOfDay()->format('Y-m-d');
            $this->dateTo = now()->endOfDay()->format('Y-m-d');
        } elseif ($this->datePreset === 'all') {
            $this->dateFrom = null;
            $this->dateTo = null;
        }
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedUser = '';
        $this->selectedAction = '';
        $this->selectedModel = '';
        if (Auth::user()->isSuperAdmin()) {
            $this->selectedMarquee = '';
        }
        $this->datePreset = 'all';
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function showDetailModal(int $logId)
    {
        $this->selectedLogId = $logId;
        $log = ActivityLog::withoutGlobalScope('tenant')
            ->with(['user.role', 'marquee'])
            ->find($logId);

        if ($log) {
            $this->selectedLogDetails = [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'model_type' => class_basename($log->model_type),
                'model_full_type' => $log->model_type,
                'model_id' => $log->model_id,
                'user_name' => $log->user ? $log->user->name : 'System / Guest',
                'user_email' => $log->user ? $log->user->email : null,
                'user_role' => $log->user && $log->user->role ? ($log->user->role->label ?? $log->user->role->name) : 'User',
                'marquee_name' => $log->marquee ? $log->marquee->name : 'System Global',
                'ip_address' => $log->ip_address ?? '—',
                'user_agent' => $log->user_agent ?? '—',
                'created_at' => $log->created_at ? $log->created_at->format('d-M-Y h:i:s A') : '—',
                'relative_time' => $log->created_at ? $log->created_at->diffForHumans() : '—',
                'old_values' => $log->old_values ?? [],
                'new_values' => $log->new_values ?? [],
            ];
        }
    }

    public function closeDetailModal()
    {
        $this->selectedLogId = null;
        $this->selectedLogDetails = null;
    }

    public function exportCsv(): StreamedResponse
    {
        $query = $this->buildQuery();
        $logs = $query->latest('id')->limit(5000)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity_logs_'.date('Y-m-d_His').'.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Timestamp', 'User', 'Role', 'Marquee', 'Action', 'Target Model', 'Record ID', 'Description', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                    $log->user ? $log->user->name : 'System',
                    $log->user && $log->user->role ? ($log->user->role->label ?? $log->user->role->name) : '—',
                    $log->marquee ? $log->marquee->name : 'Global',
                    strtoupper($log->action),
                    class_basename($log->model_type),
                    $log->model_id ?? '',
                    $log->description ?? '',
                    $log->ip_address ?? '',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    protected function getAccessibleMarqueeIds(): array
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return [];
        }

        $owned = $user->ownedMarquees()->pluck('marquees.id')->toArray();
        if ($user->marquee_id) {
            $owned[] = (int) $user->marquee_id;
        }

        return array_unique(array_filter($owned));
    }

    protected function buildQuery()
    {
        $user = Auth::user();
        $query = ActivityLog::withoutGlobalScope('tenant')->with(['user.role', 'marquee']);

        // Multi-tenant scoping for Business Owners
        if (! $user->isSuperAdmin()) {
            $marqueeIds = $this->getAccessibleMarqueeIds();
            $query->where(function ($q) use ($marqueeIds, $user) {
                $q->whereIn('marquee_id', $marqueeIds)
                    ->orWhere('user_id', $user->id);
            });
        }

        // Marquee filter
        if (! empty($this->selectedMarquee)) {
            $query->where('marquee_id', $this->selectedMarquee);
        }

        // User filter
        if (! empty($this->selectedUser)) {
            $query->where('user_id', $this->selectedUser);
        }

        // Action filter
        if (! empty($this->selectedAction)) {
            $query->where('action', $this->selectedAction);
        }

        // Model / Module filter
        if (! empty($this->selectedModel)) {
            $query->where('model_type', 'like', '%'.$this->selectedModel.'%');
        }

        // Date range filter
        if (! empty($this->dateFrom)) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if (! empty($this->dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        // Search query
        if (! empty($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('model_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    public function render()
    {
        $user = Auth::user();
        $query = $this->buildQuery();

        // High performance KPI metrics computation (scoped)
        $scopedBase = ActivityLog::withoutGlobalScope('tenant');
        if (! $user->isSuperAdmin()) {
            $marqueeIds = $this->getAccessibleMarqueeIds();
            $scopedBase->where(function ($q) use ($marqueeIds, $user) {
                $q->whereIn('marquee_id', $marqueeIds)->orWhere('user_id', $user->id);
            });
        }
        if (! empty($this->selectedMarquee)) {
            $scopedBase->where('marquee_id', $this->selectedMarquee);
        }

        $totalCount = (clone $scopedBase)->count();
        $todayCount = (clone $scopedBase)->whereDate('created_at', now()->toDateString())->count();
        $activeStaffToday = (clone $scopedBase)
            ->whereDate('created_at', now()->toDateString())
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
        $modificationsCount = (clone $scopedBase)
            ->whereIn('action', ['created', 'updated', 'deleted'])
            ->count();

        // Paginated activities list
        $logs = $query->latest('id')->paginate($this->perPage);

        // Populate dropdown options
        $marqueesList = $user->isSuperAdmin()
            ? Marquee::orderBy('name')->get()
            : $user->getAccessibleMarquees();

        $usersQuery = User::withoutGlobalScope('tenant');
        if (! $user->isSuperAdmin()) {
            $marqueeIds = $this->getAccessibleMarqueeIds();
            $usersQuery->whereIn('marquee_id', $marqueeIds);
        }
        $usersList = $usersQuery->orderBy('name')->get();

        // Available models for filtering
        $modulesList = [
            'Booking' => 'Bookings',
            'BookingPayment' => 'Payments & Receipts',
            'BookingFinalBill' => 'Final Bill Invoices',
            'Customer' => 'Customers',
            'Lead' => 'Inquiries & Leads',
            'Expense' => 'Expenses & Vouchers',
            'JournalVoucher' => 'Journal Vouchers',
            'Account' => 'Chart of Accounts',
            'CashBankAccount' => 'Cash & Bank Accounts',
            'Supplier' => 'Suppliers',
            'PurchaseInvoice' => 'Purchase Invoices',
            'InventoryItem' => 'Inventory Items',
            'User' => 'User Accounts',
            'SystemBackup' => 'System Backups',
        ];

        return view('livewire.activity-log-manager', [
            'logs' => $logs,
            'totalCount' => $totalCount,
            'todayCount' => $todayCount,
            'activeStaffToday' => $activeStaffToday,
            'modificationsCount' => $modificationsCount,
            'marqueesList' => $marqueesList,
            'usersList' => $usersList,
            'modulesList' => $modulesList,
            'isSuperAdmin' => $user->isSuperAdmin(),
            'isBusinessOwner' => $user->isBusinessOwner(),
        ])->layout('layouts.admin');
    }
}
