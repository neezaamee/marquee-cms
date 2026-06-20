<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Package;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PackagePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $unauthorizedRole;
    protected $marqueeA;
    protected $marqueeB;
    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create subscription plan
        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'price' => 10000,
            'billing_interval' => 'month',
        ]);

        // Create role and assign menu and package permissions
        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        
        $permissions = [
            Permission::create(['name' => 'view_menus', 'label' => 'View Menus']),
            Permission::create(['name' => 'create_menus', 'label' => 'Create Menus']),
            Permission::create(['name' => 'edit_menus', 'label' => 'Edit Menus']),
            Permission::create(['name' => 'delete_menus', 'label' => 'Delete Menus']),
            Permission::create(['name' => 'view_packages', 'label' => 'View Packages']),
            Permission::create(['name' => 'create_packages', 'label' => 'Create Packages']),
            Permission::create(['name' => 'edit_packages', 'label' => 'Edit Packages']),
            Permission::create(['name' => 'delete_packages', 'label' => 'Delete Packages']),
        ];

        $this->ownerRole->permissions()->sync(collect($permissions)->pluck('id'));

        $this->unauthorizedRole = Role::create(['name' => 'accountant', 'label' => 'Accountant']);

        // Create marquees
        $this->marqueeA = Marquee::create([
            'name' => 'Lahore Hall A',
            'address' => '123 Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+923001111111',
            'email' => 'a@hall.com',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->marqueeB = Marquee::create([
            'name' => 'Karachi Hall B',
            'address' => '456 Clifton',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '+923002222222',
            'email' => 'b@hall.com',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        // Create users
        $this->userA = User::create([
            'name' => 'Owner A',
            'email' => 'owner.a@hall.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->userB = User::create([
            'name' => 'Owner B',
            'email' => 'owner.b@hall.com',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeB->id,
        ]);
    }

    public function test_authorized_users_can_access_indexes()
    {
        $this->actingAs($this->userA);

        $this->get(route('menu-categories.index'))->assertStatus(200)->assertSeeLivewire('menu-category-list');
        $this->get(route('menu-items.index'))->assertStatus(200)->assertSeeLivewire('menu-item-list');
        $this->get(route('packages.index'))->assertStatus(200)->assertSeeLivewire('package-list');
    }

    public function test_unauthorized_users_cannot_access_indexes()
    {
        $unauthorizedUser = User::create([
            'name' => 'Accountant Staff',
            'email' => 'accountant@hall.com',
            'password' => bcrypt('password'),
            'role_id' => $this->unauthorizedRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->actingAs($unauthorizedUser);

        $this->get(route('menu-categories.index'))->assertStatus(403);
        $this->get(route('menu-items.index'))->assertStatus(403);
        $this->get(route('packages.index'))->assertStatus(403);
    }

    public function test_can_create_menu_category_with_validation()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('menu-category-form')
            ->set('category_name', 'Beverages')
            ->set('category_code', 'BEV')
            ->set('sort_order', 3)
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('menu-categories.index'));

        $this->assertDatabaseHas('menu_categories', [
            'category_name' => 'Beverages',
            'category_code' => 'BEV',
            'sort_order' => 3,
            'marquee_id' => $this->marqueeA->id,
        ]);
    }

    public function test_category_code_must_be_unique_within_same_marquee()
    {
        MenuCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'category_name' => 'Desserts',
            'category_code' => 'DES',
        ]);

        Livewire::actingAs($this->userA);

        Livewire::test('menu-category-form')
            ->set('category_name', 'Sweet Desserts')
            ->set('category_code', 'DES') // duplicate
            ->call('save')
            ->assertHasErrors(['category_code' => 'unique']);
    }

    public function test_can_create_menu_item_with_validation_and_image()
    {
        Storage::fake('public');

        $category = MenuCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'category_name' => 'Rice Dishes',
            'category_code' => 'RICE',
        ]);

        Livewire::actingAs($this->userA);

        $fakeFile = UploadedFile::fake()->image('biryani.jpg');

        Livewire::test('menu-item-form')
            ->set('category_id', $category->id)
            ->set('item_name', 'Mutton Biryani')
            ->set('urdu_name', 'مٹن بریانی')
            ->set('item_code' , 'BIRY-MT')
            ->set('unit', 'Per Plate')
            ->set('base_cost', 250)
            ->set('selling_price', 450)
            ->set('image', $fakeFile)
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('menu-items.index'));

        $this->assertDatabaseHas('menu_items', [
            'item_name' => 'Mutton Biryani',
            'urdu_name' => 'مٹن بریانی',
            'item_code' => 'BIRY-MT',
            'base_cost' => 250.00,
            'selling_price' => 450.00,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $item = MenuItem::where('item_code', 'BIRY-MT')->first();
        $this->assertNotNull($item->image);
        Storage::disk('public')->assertExists($item->image);
    }

    public function test_can_create_package_with_seasonal_dates_validation()
    {
        Livewire::actingAs($this->userA);

        // Fail validation when seasonal dates are missing for a seasonal package
        Livewire::test('package-form')
            ->set('package_name', 'Winter Deal')
            ->set('package_code', 'PKG-WIN')
            ->set('per_plate_price', 1500)
            ->set('seasonal_package', true)
            ->set('season_start_date', '') // missing
            ->set('season_end_date', '') // missing
            ->call('save')
            ->assertHasErrors(['season_start_date', 'season_end_date']);

        // Success when dates are valid
        Livewire::test('package-form')
            ->set('package_name', 'Winter Deal')
            ->set('package_code', 'PKG-WIN')
            ->set('per_plate_price', 1500)
            ->set('seasonal_package', true)
            ->set('season_start_date', '2026-11-01')
            ->set('season_end_date', '2027-02-28')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('packages', [
            'package_name' => 'Winter Deal',
            'package_code' => 'PKG-WIN',
            'seasonal_package' => true,
            'season_start_date' => '2026-11-01 00:00:00',
            'season_end_date' => '2027-02-28 00:00:00',
            'marquee_id' => $this->marqueeA->id,
        ]);
    }

    public function test_package_builder_can_assign_modify_and_remove_items()
    {
        $category = MenuCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'category_name' => 'BBQ',
            'category_code' => 'BBQ',
        ]);

        $item1 = MenuItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $category->id,
            'item_name' => 'Chicken Tikka',
            'item_code' => 'TKA-CH',
            'selling_price' => 300,
        ]);

        $item2 = MenuItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $category->id,
            'item_name' => 'Seekh Kabab',
            'item_code' => 'KBB-SK',
            'selling_price' => 250,
        ]);

        $package = Package::create([
            'marquee_id' => $this->marqueeA->id,
            'package_name' => 'Silver Package',
            'package_code' => 'PKG-SLV',
            'per_plate_price' => 1200,
            'status' => 'Draft',
        ]);

        Livewire::actingAs($this->userA);

        // Load builder and add item 1
        $builder = Livewire::test('package-builder', ['package' => $package])
            ->call('addItem', $item1->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('package_menu_items', [
            'package_id' => $package->id,
            'menu_item_id' => $item1->id,
            'quantity' => 1.00,
            'display_order' => 1,
        ]);

        // Add item 2, change quantity, and swap positions
        $builder->call('addItem', $item2->id)
            ->call('updateQuantity', $item1->id, 2.5)
            ->call('moveDown', $item1->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('package_menu_items', [
            'package_id' => $package->id,
            'menu_item_id' => $item1->id,
            'quantity' => 2.50,
            'display_order' => 2,
        ]);

        $this->assertDatabaseHas('package_menu_items', [
            'package_id' => $package->id,
            'menu_item_id' => $item2->id,
            'quantity' => 1.00,
            'display_order' => 1,
        ]);

        // Remove item 1
        $builder->call('removeItem', $item1->id);

        $this->assertDatabaseMissing('package_menu_items', [
            'package_id' => $package->id,
            'menu_item_id' => $item1->id,
        ]);
    }

    public function test_pricing_service_estimates_correct_margins()
    {
        $category = MenuCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'category_name' => 'Main',
            'category_code' => 'MAIN',
        ]);

        $item1 = MenuItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $category->id,
            'item_name' => 'Chicken Qorma',
            'item_code' => 'QOR-CH',
            'base_cost' => 150.00,
            'selling_price' => 250,
        ]);

        $package = Package::create([
            'marquee_id' => $this->marqueeA->id,
            'package_name' => 'Standard Pack',
            'package_code' => 'PKG-STD',
            'base_price' => 10000.00, // setup flat cost
            'per_plate_price' => 1000.00,
            'status' => 'Active',
        ]);

        $package->menuItems()->attach($item1->id, [
            'quantity' => 2.00, // 2 plates quantity/portion per person
            'display_order' => 1,
        ]);

        $service = new PackagePricingService();
        $quote = $service->calculateQuote($package->id, 200); // 200 guests

        // Calculations:
        // total selling price: 10000 + (1000 * 200) = 210000
        // total item base cost: (150 * 2) * 200 = 60000
        // estimated profit: 210000 - 60000 = 150000
        // profit margin: (150000 / 210000) * 100 = 71.43%

        $this->assertEquals(210000.00, $quote['total_selling_price']);
        $this->assertEquals(60000.00, $quote['estimated_total_base_cost']);
        $this->assertEquals(150000.00, $quote['estimated_profit']);
        $this->assertEquals(71.43, $quote['profit_margin_percent']);
    }

    public function test_can_clone_package_with_items()
    {
        $category = MenuCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'category_name' => 'BBQ',
            'category_code' => 'BBQ',
        ]);

        $item = MenuItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $category->id,
            'item_name' => 'Seekh Kabab',
            'item_code' => 'KBB-SK',
            'selling_price' => 250,
        ]);

        $package = Package::create([
            'marquee_id' => $this->marqueeA->id,
            'package_name' => 'Original Package',
            'package_code' => 'PKG-ORIG',
            'per_plate_price' => 1200,
            'status' => 'Active',
        ]);

        $package->menuItems()->attach($item->id, [
            'quantity' => 3.00,
            'display_order' => 1,
        ]);

        $service = new PackagePricingService();
        $cloned = $service->clonePackage($package->id, 'Cloned Package', 'PKG-CLON');

        $this->assertDatabaseHas('packages', [
            'package_name' => 'Cloned Package',
            'package_code' => 'PKG-CLON',
            'status' => 'Draft', // Cloned is Draft
            'per_plate_price' => 1200.00,
        ]);

        $this->assertDatabaseHas('package_menu_items', [
            'package_id' => $cloned->id,
            'menu_item_id' => $item->id,
            'quantity' => 3.00,
        ]);
    }

    public function test_tenant_isolation_prevents_viewing_other_tenant_items()
    {
        // Category and Item in Tenant B
        $categoryB = MenuCategory::create([
            'marquee_id' => $this->marqueeB->id,
            'category_name' => 'Desserts B',
            'category_code' => 'DES-B',
        ]);

        $itemB = MenuItem::create([
            'marquee_id' => $this->marqueeB->id,
            'category_id' => $categoryB->id,
            'item_name' => 'Ice Cream B',
            'item_code' => 'ICE-B',
            'selling_price' => 100,
        ]);

        $this->actingAs($this->userA);

        // Accessing Tenant B menu item show page must return 403 or 404 (due to global scope)
        $response = $this->get(route('menu-items.show', $itemB->id));
        $response->assertStatus(404);
    }
}
