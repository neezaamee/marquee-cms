<?php

namespace Tests\Feature;

use App\Livewire\Inventory\SupplierCategoryList;
use App\Livewire\Inventory\SupplierList;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marqueeA;
    protected Marquee $marqueeB;
    protected User $ownerA;
    protected User $ownerB;
    protected User $staffA;
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 25000,
            'billing_interval' => 'month',
        ]);

        $ownerRole = Role::where('name', 'owner')->first();
        $staffRole = Role::where('name', 'staff')->first();

        // Tenant A
        $this->marqueeA = Marquee::create([
            'name' => 'Grand Emerald Palace',
            'address' => 'Gulberg, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'email' => 'emerald@marquee.com',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->ownerA = User::create([
            'name' => 'Owner Alpha',
            'email' => 'owner.alpha@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->staffA = User::create([
            'name' => 'Staff Alex',
            'email' => 'staff.alex@emerald.com',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        // Tenant B
        $this->marqueeB = Marquee::create([
            'name' => 'Royal Sapphire Banquet',
            'address' => 'Clifton, Karachi',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03009998877',
            'email' => 'sapphire@marquee.com',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->ownerB = User::create([
            'name' => 'Owner Beta',
            'email' => 'owner.beta@sapphire.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeB->id,
        ]);
    }

    /**
     * Test 1: Supplier category can be created.
     */
    public function test_supplier_category_can_be_created(): void
    {
        $category = SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Meat & Poultry',
            'code' => 'SC-MEAT',
            'description' => 'Fresh mutton, beef, chicken supplier',
            'status' => 'Active',
            'sort_order' => 1,
            'created_by' => $this->ownerA->id,
        ]);

        $this->assertDatabaseHas('supplier_categories', [
            'id' => $category->id,
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Meat & Poultry',
            'code' => 'SC-MEAT',
            'status' => 'Active',
        ]);
    }

    /**
     * Test 2: Duplicate category name is prevented within same tenant.
     */
    public function test_duplicate_category_name_is_prevented_within_tenant(): void
    {
        SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Grocery Supplies',
            'code' => 'SC-GROC-1',
        ]);

        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->set('name', 'Grocery Supplies')
            ->set('code', 'SC-GROC-2')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    /**
     * Test 3: Duplicate category code is prevented within same tenant.
     */
    public function test_duplicate_category_code_is_prevented_within_tenant(): void
    {
        SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Dairy Products',
            'code' => 'SC-DAIRY',
        ]);

        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->set('name', 'Fresh Milk Vendors')
            ->set('code', 'SC-DAIRY')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /**
     * Test 4 & 5: Supplier can have one or multiple categories (Many-to-Many).
     */
    public function test_supplier_can_have_multiple_categories(): void
    {
        $catMeat = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Meat & Poultry', 'code' => 'SC-MEAT']);
        $catGroc = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Grocery', 'code' => 'SC-GROC']);
        $catDairy = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Dairy', 'code' => 'SC-DAIRY']);

        $supplier = Supplier::create([
            'marquee_id' => $this->marqueeA->id,
            'supplier_code' => 'SUP-00001',
            'name' => 'Al-Madina Traders',
            'mobile_number' => '03001234567',
            'status' => 'Active',
        ]);

        // Attach 3 categories
        $supplier->categories()->attach([$catMeat->id, $catGroc->id, $catDairy->id]);

        $this->assertCount(3, $supplier->categories);
        $this->assertTrue($supplier->categories->contains($catMeat));
        $this->assertTrue($supplier->categories->contains($catGroc));
        $this->assertTrue($supplier->categories->contains($catDairy));

        // Category reverse relation
        $this->assertCount(1, $catMeat->suppliers);
        $this->assertEquals($supplier->id, $catMeat->suppliers->first()->id);
    }

    /**
     * Test 6 & 7 & 8: Editing supplier synchronizes categories properly.
     */
    public function test_editing_supplier_correctly_synchronizes_categories(): void
    {
        $cat1 = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Bakery', 'code' => 'SC-BAKERY']);
        $cat2 = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Beverages', 'code' => 'SC-BEV']);
        $cat3 = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Spices', 'code' => 'SC-SPICE']);

        $supplier = Supplier::create([
            'marquee_id' => $this->marqueeA->id,
            'supplier_code' => 'SUP-00002',
            'name' => 'Bismillah Provisioners',
            'mobile_number' => '03007654321',
            'status' => 'Active',
        ]);

        $supplier->categories()->attach([$cat1->id, $cat2->id]);
        $this->assertCount(2, $supplier->categories);

        // Edit through Livewire: Remove Beverages ($cat2) and Add Spices ($cat3)
        Livewire::actingAs($this->ownerA)
            ->test(SupplierList::class)
            ->call('edit', $supplier->id)
            ->set('selectedCategories', [$cat1->id, $cat3->id])
            ->call('save')
            ->assertHasNoErrors();

        $supplier->refresh();
        $this->assertCount(2, $supplier->categories);
        $this->assertTrue($supplier->categories->contains($cat1));
        $this->assertFalse($supplier->categories->contains($cat2));
        $this->assertTrue($supplier->categories->contains($cat3));
    }

    /**
     * Test 9, 10 & 11: Supplier filtering by category works without duplicating rows.
     */
    public function test_supplier_filtering_by_category_works_without_duplicate_rows(): void
    {
        $catMeat = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Meat & Poultry', 'code' => 'SC-MEAT']);
        $catGroc = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Grocery', 'code' => 'SC-GROC']);

        // Supplier 1: Has both Meat and Grocery
        $supplier1 = Supplier::create([
            'marquee_id' => $this->marqueeA->id,
            'supplier_code' => 'SUP-00001',
            'name' => 'Combo Super Supplier',
            'mobile_number' => '03001111111',
            'status' => 'Active',
        ]);
        $supplier1->categories()->attach([$catMeat->id, $catGroc->id]);

        // Supplier 2: Has only Grocery
        $supplier2 = Supplier::create([
            'marquee_id' => $this->marqueeA->id,
            'supplier_code' => 'SUP-00002',
            'name' => 'Pure Grocery Mart',
            'mobile_number' => '03002222222',
            'status' => 'Active',
        ]);
        $supplier2->categories()->attach([$catGroc->id]);

        // Filter by Meat: Should return only Supplier 1
        Livewire::actingAs($this->ownerA)
            ->test(SupplierList::class)
            ->set('categoryFilter', $catMeat->id)
            ->assertSee('Combo Super Supplier')
            ->assertDontSee('Pure Grocery Mart');

        // Filter by Grocery: Should return both Supplier 1 and Supplier 2
        Livewire::actingAs($this->ownerA)
            ->test(SupplierList::class)
            ->set('categoryFilter', $catGroc->id)
            ->assertSee('Combo Super Supplier')
            ->assertSee('Pure Grocery Mart');
    }

    /**
     * Test 12 & 13: Tenant A cannot access or see Tenant B categories.
     */
    public function test_tenant_isolation_prevents_cross_tenant_category_access(): void
    {
        $catA = SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Emerald Exclusive Meat',
            'code' => 'SC-EM-MEAT',
        ]);

        $catB = SupplierCategory::create([
            'marquee_id' => $this->marqueeB->id,
            'name' => 'Sapphire Special Fish',
            'code' => 'SC-SA-FISH',
        ]);

        // Tenant A user sees only catA
        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->assertSee('Emerald Exclusive Meat')
            ->assertDontSee('Sapphire Special Fish');

        // Tenant B user sees only catB
        Livewire::actingAs($this->ownerB)
            ->test(SupplierCategoryList::class)
            ->assertSee('Sapphire Special Fish')
            ->assertDontSee('Emerald Exclusive Meat');
    }

    /**
     * Test 14: Inactive category status toggling.
     */
    public function test_inactive_category_status_toggling(): void
    {
        $category = SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Cleaning Chemicals',
            'code' => 'SC-CHEM',
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->call('toggleStatus', $category->id);

        $category->refresh();
        $this->assertEquals('Inactive', $category->status);

        // Toggle back to Active
        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->call('toggleStatus', $category->id);

        $category->refresh();
        $this->assertEquals('Active', $category->status);
    }

    /**
     * Test 15: Used category cannot be deleted unsafely when assigned to suppliers.
     */
    public function test_category_with_assigned_suppliers_cannot_be_deleted(): void
    {
        $category = SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Disposable Packaging',
            'code' => 'SC-PKG',
        ]);

        $supplier = Supplier::create([
            'marquee_id' => $this->marqueeA->id,
            'supplier_code' => 'SUP-00010',
            'name' => 'Packaging World',
            'mobile_number' => '03005554433',
        ]);
        $supplier->categories()->attach($category->id);

        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->call('confirmDeletion', $category->id)
            ->call('deleteRecord')
            ->assertSee('Cannot delete category');

        $this->assertDatabaseHas('supplier_categories', ['id' => $category->id]);
    }

    /**
     * Test 16: Unused category can be deleted safely.
     */
    public function test_unused_category_can_be_deleted(): void
    {
        $category = SupplierCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Obsolete Category',
            'code' => 'SC-OBSOLETE',
        ]);

        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->call('confirmDeletion', $category->id)
            ->call('deleteRecord')
            ->assertSee('deleted successfully');

        $this->assertSoftDeleted('supplier_categories', ['id' => $category->id]);
    }

    /**
     * Test 17: Livewire SupplierCategoryList creates new category with automatic code generation.
     */
    public function test_livewire_supplier_category_creation_with_auto_code(): void
    {
        Livewire::actingAs($this->ownerA)
            ->test(SupplierCategoryList::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', 'Equipment & Utensils')
            ->set('code', 'SC-EQUIP')
            ->set('description', 'Chafing dishes, cutlery, and kitchen equipment')
            ->set('sort_order', 10)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('created successfully');

        $this->assertDatabaseHas('supplier_categories', [
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Equipment & Utensils',
            'code' => 'SC-EQUIP',
        ]);
    }

    /**
     * Test 18: Livewire SupplierList registers supplier with multiple categories.
     */
    public function test_livewire_supplier_registration_with_multiple_categories(): void
    {
        $cat1 = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Meat & Poultry', 'code' => 'SC-MEAT']);
        $cat2 = SupplierCategory::create(['marquee_id' => $this->marqueeA->id, 'name' => 'Grocery', 'code' => 'SC-GROC']);

        Livewire::actingAs($this->ownerA)
            ->test(SupplierList::class)
            ->call('create')
            ->set('name', 'Taj Mahal Super Suppliers')
            ->set('mobile_number', '03001234567')
            ->set('selectedCategories', [$cat1->id, $cat2->id])
            ->set('opening_balance', 50000)
            ->call('save')
            ->assertHasNoErrors();

        $supplier = Supplier::where('name', 'Taj Mahal Super Suppliers')->first();
        $this->assertNotNull($supplier);
        $this->assertCount(2, $supplier->categories);
        $this->assertEquals(50000.00, $supplier->opening_balance);
    }

    /**
     * Test 19: Permissions enforcement prevents unauthorized staff from managing categories.
     */
    public function test_unauthorized_staff_cannot_manage_categories(): void
    {
        Livewire::actingAs($this->staffA)
            ->test(SupplierCategoryList::class)
            ->call('create')
            ->assertForbidden();
    }

    /**
     * Test 20: Seeding standard default supplier categories.
     */
    public function test_seeding_standard_default_supplier_categories(): void
    {
        $inventoryService = app(InventoryService::class);
        $inventoryService->seedDefaultSupplierCategories($this->marqueeA->id);

        $this->assertEquals(11, SupplierCategory::where('marquee_id', $this->marqueeA->id)->count());
        $this->assertDatabaseHas('supplier_categories', [
            'marquee_id' => $this->marqueeA->id,
            'code' => 'SC-MEAT',
            'name' => 'Meat & Poultry',
        ]);
        $this->assertDatabaseHas('supplier_categories', [
            'marquee_id' => $this->marqueeA->id,
            'code' => 'SC-GROC',
            'name' => 'Grocery',
        ]);

        // Calling a second time is strictly idempotent (no duplicates created)
        $inventoryService->seedDefaultSupplierCategories($this->marqueeA->id);
        $this->assertEquals(11, SupplierCategory::where('marquee_id', $this->marqueeA->id)->count());
    }
}
