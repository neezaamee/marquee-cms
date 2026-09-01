<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Marquee;
use App\Models\Branch;
use App\Models\User;
use App\Services\SyntheticDataGeneratorService;
use Carbon\Carbon;
use Livewire\Component;

class DemoDataGenerator extends Component
{
    // Tenant Selector
    public ?int $selectedMarqueeId = null;

    // Active Preset
    public string $selectedPreset = 'full'; // 'quick', 'full', 'stress', 'custom'

    // Granular Generation Quantities & Toggles
    public int $newBranchesCount = 0; // Number of extra branches to generate
    public ?int $selectedBranchId = null; // null = all branches
    public int $customerCount = 25;
    public bool $includeCommunicationLogs = true;
    public int $staffCount = 10;
    public int $attendanceDays = 10;
    public int $supplierCount = 6;
    public int $inventoryCount = 18;
    public int $vendorCount = 5;
    public int $servicesPerVendor = 2;
    public int $expenseCount = 12;
    public int $bookingCount = 25;

    // Financial Toggles
    public bool $includeAdvancePayments = true;
    public bool $includeRevenueRecognition = true;
    public bool $includeSettlements = true;

    // Purge Modal State
    public bool $showPurgeModal = false;
    public string $purgeScope = 'data_only'; // 'data_only', 'delete_marquee', 'purge_all_demo_marquees'
    public bool $keepMasterCatalogs = true;

    // New Marquee Modal State
    public bool $showNewMarqueeModal = false;
    public string $newMarqueeName = '';
    public string $newMarqueeCity = 'Lahore';
    public int $newMarqueeBranchesCount = 1;

    // Logs & Feedback
    public array $executionLogs = [];
    public ?array $lastExecutionSummary = null;
    public ?string $feedbackMessage = null;
    public ?string $feedbackType = null; // 'success', 'danger', 'info'

    public function mount(SyntheticDataGeneratorService $service)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Unauthorized access.');

        // Select the first available marquee or create one if none exists
        $firstMarquee = Marquee::first();
        if ($firstMarquee) {
            $this->selectedMarqueeId = $firstMarquee->id;
        }

        $this->applyPreset('full');
    }

    public function updatedSelectedMarqueeId()
    {
        $this->executionLogs = [];
        $this->lastExecutionSummary = null;
        $this->feedbackMessage = null;
        $this->selectedBranchId = null;
    }

    public function updatedCustomerCount() { $this->selectedPreset = 'custom'; }
    public function updatedBookingCount() { $this->selectedPreset = 'custom'; }
    public function updatedStaffCount() { $this->selectedPreset = 'custom'; }
    public function updatedExpenseCount() { $this->selectedPreset = 'custom'; }
    public function updatedSupplierCount() { $this->selectedPreset = 'custom'; }
    public function updatedInventoryCount() { $this->selectedPreset = 'custom'; }
    public function updatedVendorCount() { $this->selectedPreset = 'custom'; }
    public function updatedNewBranchesCount() { $this->selectedPreset = 'custom'; }

    public function applyPreset(string $preset)
    {
        $this->selectedPreset = $preset;

        switch ($preset) {
            case 'quick':
                $this->newBranchesCount = 0;
                $this->customerCount = 10;
                $this->includeCommunicationLogs = true;
                $this->staffCount = 5;
                $this->attendanceDays = 5;
                $this->supplierCount = 3;
                $this->inventoryCount = 8;
                $this->vendorCount = 3;
                $this->servicesPerVendor = 2;
                $this->expenseCount = 6;
                $this->bookingCount = 10;
                break;

            case 'full':
                $this->newBranchesCount = 0;
                $this->customerCount = 25;
                $this->includeCommunicationLogs = true;
                $this->staffCount = 12;
                $this->attendanceDays = 10;
                $this->supplierCount = 6;
                $this->inventoryCount = 20;
                $this->vendorCount = 5;
                $this->servicesPerVendor = 2;
                $this->expenseCount = 15;
                $this->bookingCount = 25;
                break;

            case 'stress':
                $this->newBranchesCount = 1;
                $this->customerCount = 60;
                $this->includeCommunicationLogs = true;
                $this->staffCount = 25;
                $this->attendanceDays = 15;
                $this->supplierCount = 12;
                $this->inventoryCount = 40;
                $this->vendorCount = 10;
                $this->servicesPerVendor = 3;
                $this->expenseCount = 35;
                $this->bookingCount = 60;
                break;

            case 'custom':
                // Retain current values
                break;
        }
    }

    public function runGenerator(SyntheticDataGeneratorService $service)
    {
        if (!$this->selectedMarqueeId) {
            $this->feedbackMessage = 'Please select or create a marquee tenant first.';
            $this->feedbackType = 'danger';
            return;
        }

        $marquee = Marquee::find($this->selectedMarqueeId);
        if (!$marquee) {
            $this->feedbackMessage = 'Selected Marquee not found.';
            $this->feedbackType = 'danger';
            return;
        }

        $startTime = microtime(true);
        $this->executionLogs = [];
        $this->log("Initializing Synthetic Data Studio for tenant: [{$marquee->name}]");

        try {
            // 1. Ensure master structure
            $this->log("Verifying tenant master charts, branches, halls, shifts and catalogs...");
            $service->ensureMasterConfigurations($marquee);

            $summary = [];

            // 1.1 Generate Extra Branches if requested
            if ($this->newBranchesCount > 0) {
                $this->log("Creating {$this->newBranchesCount} new branches with halls...");
                $brRes = $service->generateBranches($this->selectedMarqueeId, $this->newBranchesCount);
                $summary['branches'] = $brRes['branches_created'];
                $summary['halls'] = $brRes['halls_created'];
                $this->log("✓ Created {$brRes['branches_created']} new branches and {$brRes['halls_created']} halls.");
            }

            // 2. Customers & CRM
            if ($this->customerCount > 0) {
                $this->log("Generating {$this->customerCount} Pakistani customers and CRM communication history...");
                $custRes = $service->generateCustomers($this->selectedMarqueeId, $this->customerCount, $this->includeCommunicationLogs);
                $summary['customers'] = $custRes['customers_created'];
                $summary['crm_logs'] = $custRes['crm_logs_created'];
                $this->log("✓ Created {$custRes['customers_created']} Customers & {$custRes['crm_logs_created']} CRM communication logs.");
            }

            // 3. Staff & Attendance
            if ($this->staffCount > 0) {
                $this->log("Generating {$this->staffCount} operational staff members & {$this->attendanceDays}-day attendance logs...");
                $staffRes = $service->generateStaffAndAttendance($this->selectedMarqueeId, $this->staffCount, $this->attendanceDays, $this->selectedBranchId);
                $summary['staff'] = $staffRes['staff_created'];
                $summary['attendance'] = $staffRes['attendance_records_created'];
                $this->log("✓ Created {$staffRes['staff_created']} Staff members with {$staffRes['attendance_records_created']} attendance records.");
            }

            // 4. Supply Chain & Kitchen Inventory
            if ($this->supplierCount > 0 || $this->inventoryCount > 0) {
                $this->log("Generating {$this->supplierCount} Suppliers & {$this->inventoryCount} Kitchen Inventory Items...");
                $invRes = $service->generateSupplyChain($this->selectedMarqueeId, $this->supplierCount, $this->inventoryCount);
                $summary['suppliers'] = $invRes['suppliers_created'];
                $summary['inventory_items'] = $invRes['inventory_items_created'];
                $this->log("✓ Created {$invRes['suppliers_created']} Suppliers & {$invRes['inventory_items_created']} Inventory Items.");
            }

            // 5. Vendor Partnerships
            if ($this->vendorCount > 0) {
                $this->log("Generating {$this->vendorCount} Partner Vendors & Service Catalogs...");
                $venRes = $service->generateVendorPartners($this->selectedMarqueeId, $this->vendorCount, $this->servicesPerVendor);
                $summary['vendors'] = $venRes['vendors_created'];
                $summary['vendor_services'] = $venRes['vendor_services_created'];
                $this->log("✓ Created {$venRes['vendors_created']} Vendors with {$venRes['vendor_services_created']} Services.");
            }

            // 6. Operating Expenses
            if ($this->expenseCount > 0) {
                $this->log("Generating {$this->expenseCount} Operating Expenses...");
                $expRes = $service->generateExpenses($this->selectedMarqueeId, $this->expenseCount, $this->selectedBranchId);
                $summary['expenses'] = $expRes['expenses_created'];
                $summary['expenses_amount'] = $expRes['total_amount'];
                $this->log("✓ Created {$expRes['expenses_created']} Operating Expenses totaling PKR " . number_format($expRes['total_amount']));
            }

            // 7. Bookings & Financial Accounting Transactions
            if ($this->bookingCount > 0) {
                $this->log("Generating {$this->bookingCount} Bookings with Advance Deposits, Revenue Recognition & Settlements...");
                $bookRes = $service->generateBookings($this->selectedMarqueeId, $this->bookingCount);
                $summary['bookings'] = $bookRes['bookings_created'];
                $summary['payments'] = $bookRes['payments_recorded'];
                $summary['revenue_recognized'] = $bookRes['revenue_recognized_count'];
                $summary['final_settlements'] = $bookRes['final_settlements_count'];
                $this->log("✓ Generated {$bookRes['bookings_created']} Bookings, recorded {$bookRes['payments_recorded']} Payment receipts, recognized {$bookRes['revenue_recognized_count']} event revenues, and posted {$bookRes['final_settlements_count']} post-event settlements.");
            }

            $duration = round(microtime(true) - $startTime, 2);
            $summary['duration'] = "{$duration}s";
            $this->lastExecutionSummary = $summary;

            $this->log("🎉 Synthetic data generation completed successfully in {$duration}s!");
            $this->feedbackMessage = "Successfully generated synthetic test data for {$marquee->name} in {$duration}s!";
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->log("❌ Error during generation: " . $e->getMessage());
            $this->feedbackMessage = "Generation failed: " . $e->getMessage();
            $this->feedbackType = 'danger';
        }
    }

    public function confirmPurge()
    {
        $this->purgeScope = 'data_only';
        $this->showPurgeModal = true;
    }

    public function purgeData(SyntheticDataGeneratorService $service)
    {
        if (!$this->selectedMarqueeId) return;

        $marquee = Marquee::find($this->selectedMarqueeId);
        if (!$marquee) return;

        try {
            if ($this->purgeScope === 'delete_marquee') {
                if ($this->selectedMarqueeId === 1) {
                    $this->feedbackMessage = "Cannot delete primary default marquee (ID: 1). Use 'Clean Data Only' instead.";
                    $this->feedbackType = 'danger';
                    $this->showPurgeModal = false;
                    return;
                }

                $this->log("Deleting entire demo marquee: [{$marquee->name}] and all associated branches...");
                $res = $service->deleteMarquee($this->selectedMarqueeId);
                $this->showPurgeModal = false;
                $this->selectedMarqueeId = Marquee::first()?->id;
                $this->log("✓ Successfully deleted demo marquee '{$res['marquee_name']}' and {$res['branches_deleted']} branches.");
                $this->feedbackMessage = "Successfully deleted demo marquee '{$res['marquee_name']}'.";
                $this->feedbackType = 'info';
                return;
            }

            if ($this->purgeScope === 'purge_all_demo_marquees') {
                $this->log("Purging all demo marquees (preserving primary tenant)...");
                $res = $service->purgeAllDemoMarquees(1);
                $this->showPurgeModal = false;
                $this->selectedMarqueeId = 1;
                $this->log("✓ Successfully purged {$res['marquees_deleted']} demo marquees.");
                $this->feedbackMessage = "Successfully purged {$res['marquees_deleted']} demo marquees.";
                $this->feedbackType = 'info';
                return;
            }

            // Default: 'data_only'
            $this->log("Purging transactional and synthetic data for: [{$marquee->name}]");
            $res = $service->purgeSyntheticData($this->selectedMarqueeId, $this->keepMasterCatalogs);

            $this->showPurgeModal = false;
            $totalPurged = $res['bookings_deleted'] + $res['payments_deleted'] + $res['journal_vouchers_deleted'] + $res['expenses_deleted'] + $res['customers_deleted'] + $res['staff_deleted'] + $res['suppliers_deleted'] + $res['vendors_deleted'];

            if ($totalPurged === 0) {
                $this->log("ℹ️ No synthetic data was found to delete. Tenant is already clean (0 records).");
                $this->feedbackMessage = "Tenant [{$marquee->name}] was already clean. No synthetic data found to purge.";
                $this->feedbackType = 'info';
            } else {
                $this->log("✓ Purged {$res['bookings_deleted']} Bookings, {$res['payments_deleted']} Payments, {$res['journal_vouchers_deleted']} Journal Vouchers, {$res['expenses_deleted']} Expenses, {$res['staff_deleted']} Staff, and {$res['customers_deleted']} Customers.");
                $this->feedbackMessage = "Successfully purged {$totalPurged} synthetic records for {$marquee->name}.";
                $this->feedbackType = 'info';
            }
        } catch (\Throwable $e) {
            $this->log("❌ Error during purge: " . $e->getMessage());
            $this->feedbackMessage = "Purge failed: " . $e->getMessage();
            $this->feedbackType = 'danger';
            $this->showPurgeModal = false;
        }
    }

    public function createNewDemoMarquee(SyntheticDataGeneratorService $service)
    {
        $this->validate([
            'newMarqueeName' => 'required|string|min:3|max:100',
            'newMarqueeCity' => 'required|string|max:50',
            'newMarqueeBranchesCount' => 'required|integer|min:1|max:5',
        ]);

        $marquee = Marquee::factory()->create([
            'name' => $this->newMarqueeName,
            'city' => $this->newMarqueeCity,
        ]);

        $service->ensureMasterConfigurations($marquee);

        if ($this->newMarqueeBranchesCount > 1) {
            $service->generateBranches($marquee->id, $this->newMarqueeBranchesCount - 1);
        }

        $this->selectedMarqueeId = $marquee->id;
        $this->showNewMarqueeModal = false;
        $this->newMarqueeName = '';
        $this->feedbackMessage = "New Demo Marquee '{$marquee->name}' created with {$this->newMarqueeBranchesCount} branches!";
        $this->feedbackType = 'success';
    }

    protected function log(string $message)
    {
        $timestamp = Carbon::now()->format('H:i:s');
        $this->executionLogs[] = "[{$timestamp}] {$message}";
    }

    public function render(SyntheticDataGeneratorService $service)
    {
        $marquees = Marquee::orderBy('name')->get();
        $stats = $this->selectedMarqueeId ? $service->getTenantStats($this->selectedMarqueeId) : null;
        $selectedMarquee = $this->selectedMarqueeId ? Marquee::find($this->selectedMarqueeId) : null;

        return view('livewire.super-admin.demo-data-generator', [
            'marquees' => $marquees,
            'stats' => $stats,
            'selectedMarquee' => $selectedMarquee,
        ])->layout('layouts.admin');
    }
}
