<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Marquee;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SuperAdminDashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Unauthorized access.');

        // 1. Platform-wide KPI Metrics
        $totalMarquees = Marquee::count();
        $activeMarquees = Marquee::where('status', 'active')->count();
        $totalBranches = Branch::count();
        $totalPlatformBookings = Booking::withoutGlobalScopes()->count();
        $platformGMV = (float) Booking::withoutGlobalScopes()->sum('grand_total');
        $totalUsers = User::withoutGlobalScopes()->count();
        $totalStaff = Employee::withoutGlobalScopes()->count();

        // 2. SaaS Subscriptions & Estimated MRR
        $totalSubscribers = User::withoutGlobalScopes()->whereNotNull('subscription_plan_id')->count();
        $plans = SubscriptionPlan::withCount('users')->get();
        $estimatedMRR = (float) User::withoutGlobalScopes()
            ->whereNotNull('subscription_plan_id')
            ->join('subscription_plans', 'users.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        // 3. SaaS Invoices / Platform Revenue
        $totalSaaSPayments = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('saas_payments')) {
            $totalSaaSPayments = (float) DB::table('saas_payments')->sum('amount');
        }

        // 4. Tenant Health & Ecosystem Table with Search
        $tenantsQuery = Marquee::with(['branches', 'owners', 'users'])
            ->withCount(['branches', 'halls', 'bookings']);

        if (!empty($this->search)) {
            $tenantsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('city', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $tenantsQuery->where('status', $this->statusFilter);
        }

        $tenants = $tenantsQuery->orderBy('created_at', 'desc')->paginate(8);

        // 5. Recent Platform Activity
        $recentOwners = User::withoutGlobalScopes()
            ->whereHas('role', fn($q) => $q->whereIn('name', ['business_owner', 'owner']))
            ->with(['ownedMarquees', 'subscriptionPlan'])
            ->latest()
            ->take(5)
            ->get();

        $recentBookings = Booking::withoutGlobalScopes()
            ->with(['marquee', 'customer', 'hall'])
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.super-admin.super-admin-dashboard', [
            'totalMarquees' => $totalMarquees,
            'activeMarquees' => $activeMarquees,
            'totalBranches' => $totalBranches,
            'totalPlatformBookings' => $totalPlatformBookings,
            'platformGMV' => $platformGMV,
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalSubscribers' => $totalSubscribers,
            'plans' => $plans,
            'estimatedMRR' => $estimatedMRR,
            'totalSaaSPayments' => $totalSaaSPayments,
            'tenants' => $tenants,
            'recentOwners' => $recentOwners,
            'recentBookings' => $recentBookings,
        ]);
    }
}
