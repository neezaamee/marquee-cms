<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\InventoryUnitConversion;
use App\Models\Marquee;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\RecipeVersionDetail;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\UomConversionService;
use App\Exceptions\UomConversionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UomAndRecipeVersionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $marquee;
    protected $branch;
    protected $uomService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uomService = new UomConversionService();

        // Seed roles & permissions and subscription plans
        $this->artisan('db:seed', ['--class' => 'SubscriptionPlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $plan = SubscriptionPlan::first();

        // Create tenant Marquee
        $this->marquee = Marquee::create([
            'name' => 'Test UOM Marquee',
            'email' => 'uom@marquee.com',
            'phone' => '12345678',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'active',
        ]);

        // Create branch
        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'address' => 'Branch Address',
            'city' => 'Branch City',
            'province' => 'Branch Province',
            'phone' => '123456789',
            'status' => 'active',
        ]);

        // Create Owner User
        $ownerRole = Role::where('name', 'owner')->first();
        $this->user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@test.com',
            'username' => 'owner_uom',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_uom_conversion_resolves_standard_metric_units()
    {
        $kg = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Kilogram', 'short_code' => 'KG', 'status' => 'Active']);
        $gram = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Gram', 'short_code' => 'G', 'status' => 'Active']);

        // Define global conversion: 1 KG = 1000 Grams
        InventoryUnitConversion::create([
            'marquee_id' => $this->marquee->id,
            'inventory_item_id' => null,
            'from_unit_id' => $kg->id,
            'to_unit_id' => $gram->id,
            'factor' => 1000.0000,
        ]);

        // Direct conversion: 2.5 KG to Grams
        $grams = $this->uomService->convert(2.5, $kg->id, $gram->id, $this->marquee->id);
        $this->assertEquals(2500.0, $grams);

        // Inverse conversion: 500 Grams to KG
        $kgs = $this->uomService->convert(500.0, $gram->id, $kg->id, $this->marquee->id);
        $this->assertEquals(0.5, $kgs);
    }

    /** @test */
    public function test_uom_conversion_resolves_item_specific_overrides()
    {
        $tray = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Tray', 'short_code' => 'Tray', 'status' => 'Active']);
        $piece = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Piece', 'short_code' => 'Pc', 'status' => 'Active']);

        $category = \App\Models\InventoryCategory::create([
            'marquee_id' => $this->marquee->id,
            'name'       => 'Test Category',
            'status'     => 'Active',
        ]);

        // Create inventory item
        $egg = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'item_code' => 'EGG001',
            'name' => 'Egg',
            'category_id' => $category->id,
            'unit_id' => $piece->id,
            'status' => 'Active',
        ]);

        // Define item-specific override: 1 Tray of Eggs = 30 Pieces
        InventoryUnitConversion::create([
            'marquee_id' => $this->marquee->id,
            'inventory_item_id' => $egg->id,
            'from_unit_id' => $tray->id,
            'to_unit_id' => $piece->id,
            'factor' => 30.0000,
        ]);

        // Direct: 2 Trays of Eggs to Pieces
        $pieces = $this->uomService->convert(2.0, $tray->id, $piece->id, $this->marquee->id, $egg->id);
        $this->assertEquals(60.0, $pieces);

        // Inverse: 90 Pieces of Eggs to Trays
        $trays = $this->uomService->convert(90.0, $piece->id, $tray->id, $this->marquee->id, $egg->id);
        $this->assertEquals(3.0, $trays);
    }

    /** @test */
    public function test_uom_conversion_throws_exception_on_undefined_conversions()
    {
        $unitA = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Unit A', 'short_code' => 'UA', 'status' => 'Active']);
        $unitB = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Unit B', 'short_code' => 'UB', 'status' => 'Active']);

        $this->expectException(UomConversionException::class);

        // Conversion not defined
        $this->uomService->convert(10.0, $unitA->id, $unitB->id, $this->marquee->id);
    }

    /** @test */
    public function test_recipe_version_concurrency_lock()
    {
        $this->actingAs($this->user);

        // Create Menu Item
        $menuCategory = MenuCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_name' => 'Pakistani',
            'category_code' => 'PAK-001',
            'status' => 'Active',
        ]);
        $menuItem = MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $menuCategory->id,
            'item_name' => 'Biryani',
            'item_code' => 'MENU-BIR-01',
            'selling_price' => 500,
            'status' => 'Active',
        ]);

        // Create recipe header
        $recipe = Recipe::create([
            'marquee_id' => $this->marquee->id,
            'menu_item_id' => $menuItem->id,
            'description' => 'Test recipe',
        ]);

        // Create version 1 (Active)
        $version1 = RecipeVersion::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'recipe_id' => $recipe->id,
            'version_number' => 1,
            'is_active' => true,
        ]);

        // Create version 2 (Inactive)
        $version2 = RecipeVersion::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'recipe_id' => $recipe->id,
            'version_number' => 2,
            'is_active' => false,
        ]);

        // Verify version 1 is active
        $this->assertTrue($version1->fresh()->is_active);
        $this->assertFalse($version2->fresh()->is_active);

        // Set version 2 active (concurrency locked method)
        RecipeVersion::makeVersionActive($this->marquee->id, $recipe->id, $this->branch->id, $version2->id);

        // Verify version 1 is now inactive, and version 2 is active
        $this->assertFalse($version1->fresh()->is_active);
        $this->assertTrue($version2->fresh()->is_active);
    }

    /** @test */
    public function test_tenant_isolation_on_conversions()
    {
        // Tenant 2 setup
        $plan = SubscriptionPlan::first();
        $marquee2 = Marquee::create([
            'name' => 'Second Tenant Marquee',
            'email' => 'tenant2@marquee.com',
            'phone' => '87654321',
            'address' => 'T2 Address',
            'city' => 'T2 City',
            'province' => 'T2 Province',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'active',
        ]);

        $kg = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Kilogram', 'short_code' => 'KG', 'status' => 'Active']);
        $gram = InventoryUnit::create(['marquee_id' => $this->marquee->id, 'name' => 'Gram', 'short_code' => 'G', 'status' => 'Active']);

        // Conversion defined only for Tenant 1
        InventoryUnitConversion::create([
            'marquee_id' => $this->marquee->id,
            'inventory_item_id' => null,
            'from_unit_id' => $kg->id,
            'to_unit_id' => $gram->id,
            'factor' => 1000.0000,
        ]);

        // Tenant 1 should convert successfully
        $converted = $this->uomService->convert(1.5, $kg->id, $gram->id, $this->marquee->id);
        $this->assertEquals(1500.0, $converted);

        // Tenant 2 attempting the same conversion should throw an exception due to isolation
        $this->expectException(UomConversionException::class);
        $this->uomService->convert(1.5, $kg->id, $gram->id, $marquee2->id);
    }
}
