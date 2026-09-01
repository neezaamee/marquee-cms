<?php

namespace App\Console\Commands;

use App\Models\Marquee;
use App\Services\SyntheticDataGeneratorService;
use Illuminate\Console\Command;

class GenerateSyntheticDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:generate 
                            {--marquee= : Specific Marquee ID to seed}
                            {--preset=full : Generation preset (quick, full, stress)}
                            {--branches= : Number of extra branches to generate}
                            {--customers= : Number of customers to generate}
                            {--bookings= : Number of bookings to generate}
                            {--staff= : Number of staff members to generate}
                            {--inventory= : Number of inventory items to generate}
                            {--expenses= : Number of expenses to generate}
                            {--purge : Purge existing synthetic data before generating}
                            {--purge-all-demos : Purge all demo marquees (keeps default tenant)}
                            {--delete-marquee : Delete the specified demo marquee}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate rich Pakistani synthetic demo & test data using model factories';

    /**
     * Execute the console command.
     */
    public function handle(SyntheticDataGeneratorService $service): int
    {
        if ($this->option('purge-all-demos')) {
            $this->warn("Purging all demo marquees (preserving primary tenant ID 1)...");
            $res = $service->purgeAllDemoMarquees(1);
            $this->info("✓ Purged {$res['marquees_deleted']} demo marquees.");
            return Command::SUCCESS;
        }

        $marqueeId = $this->option('marquee');

        if ($this->option('delete-marquee')) {
            if (!$marqueeId) {
                $this->error("Please specify --marquee=ID to delete.");
                return Command::FAILURE;
            }
            $res = $service->deleteMarquee((int) $marqueeId);
            $this->info("✓ Deleted demo marquee '{$res['marquee_name']}' and {$res['branches_deleted']} branches.");
            return Command::SUCCESS;
        }

        $marquees = $marqueeId 
            ? Marquee::where('id', $marqueeId)->get()
            : Marquee::all();

        if ($marquees->isEmpty()) {
            $this->error("No marquees found to generate synthetic data for.");
            return Command::FAILURE;
        }

        $preset = $this->option('preset') ?? 'full';

        foreach ($marquees as $marquee) {
            $this->info("=== Processing Tenant: {$marquee->name} (ID: {$marquee->id}) ===");

            if ($this->option('purge')) {
                $this->warn("Purging synthetic data for {$marquee->name}...");
                $service->purgeSyntheticData($marquee->id);
                $this->info("✓ Purged successfully.");
            }

            $service->ensureMasterConfigurations($marquee);

            // Generate Branches if requested
            $branchesCount = (int) ($this->option('branches') ?? 0);
            if ($branchesCount > 0) {
                $this->line("→ Generating {$branchesCount} Branches...");
                $bRes = $service->generateBranches($marquee->id, $branchesCount);
                $this->info("✓ Created {$bRes['branches_created']} Branches & {$bRes['halls_created']} Halls.");
            }

            // Determine quantities
            $customerCount = (int) ($this->option('customers') ?? match($preset) {
                'quick' => 10,
                'stress' => 60,
                default => 25,
            });

            $bookingCount = (int) ($this->option('bookings') ?? match($preset) {
                'quick' => 10,
                'stress' => 60,
                default => 25,
            });

            $staffCount = (int) ($this->option('staff') ?? match($preset) {
                'quick' => 5,
                'stress' => 25,
                default => 12,
            });

            $inventoryCount = (int) ($this->option('inventory') ?? match($preset) {
                'quick' => 8,
                'stress' => 40,
                default => 20,
            });

            $expenseCount = (int) ($this->option('expenses') ?? match($preset) {
                'quick' => 6,
                'stress' => 35,
                default => 15,
            });

            $this->line("→ Generating {$customerCount} Customers...");
            $cRes = $service->generateCustomers($marquee->id, $customerCount);
            $this->info("✓ Created {$cRes['customers_created']} Customers & {$cRes['crm_logs_created']} CRM logs.");

            $this->line("→ Generating {$staffCount} Staff & Attendance...");
            $sRes = $service->generateStaffAndAttendance($marquee->id, $staffCount, 10);
            $this->info("✓ Created {$sRes['staff_created']} Staff & {$sRes['attendance_records_created']} Attendance logs.");

            $this->line("→ Generating Supply Chain & {$inventoryCount} Inventory items...");
            $iRes = $service->generateSupplyChain($marquee->id, 5, $inventoryCount);
            $this->info("✓ Created {$iRes['suppliers_created']} Suppliers & {$iRes['inventory_items_created']} Inventory items.");

            $this->line("→ Generating Operating Expenses...");
            $eRes = $service->generateExpenses($marquee->id, $expenseCount);
            $this->info("✓ Created {$eRes['expenses_created']} Expenses totaling PKR " . number_format($eRes['total_amount']));

            $this->line("→ Generating {$bookingCount} Bookings with financial accounting...");
            $bRes = $service->generateBookings($marquee->id, $bookingCount);
            $this->info("✓ Generated {$bRes['bookings_created']} Bookings, {$bRes['payments_recorded']} Payments, {$bRes['revenue_recognized_count']} Revenue recognitions, {$bRes['final_settlements_count']} Settlements.");
        }

        $this->info("🎉 All synthetic factory data generated successfully!");
        return Command::SUCCESS;
    }
}
