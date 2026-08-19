<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InventoryCategory;
use App\Models\InventoryUnit;
use App\Models\InventoryBrand;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Marquee;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryModuleSeeder extends Seeder
{
    protected $inventoryService;
    protected $purchaseService;

    public function __construct(InventoryService $inventoryService, PurchaseService $purchaseService)
    {
        $this->inventoryService = $inventoryService;
        $this->purchaseService = $purchaseService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marquees = Marquee::all();
        $admin = User::where('email', 'superadmin@marquee.cms')->first();
        $adminId = $admin ? $admin->id : null;

        foreach ($marquees as $marquee) {
            $marqueeId = $marquee->id;
            $branches = Branch::where('marquee_id', $marqueeId)->get();

            if ($branches->isEmpty()) {
                continue;
            }

            $branch = $branches->first();

            DB::transaction(function () use ($marqueeId, $branch, $adminId) {
                // 1. Initialize settings
                $this->inventoryService->getOrCreateSettings($marqueeId);

                // 2. Seed Units
                $unitsData = [
                    ['name' => 'Piece', 'short_code' => 'Pcs', 'description' => 'Single item unit'],
                    ['name' => 'Kilogram', 'short_code' => 'Kg', 'description' => 'Weight unit'],
                    ['name' => 'Litre', 'short_code' => 'Ltr', 'description' => 'Volume unit'],
                    ['name' => 'Box', 'short_code' => 'Box', 'description' => 'Pack of multiple items'],
                    ['name' => 'Packet', 'short_code' => 'Pkt', 'description' => 'Packet unit'],
                ];

                $units = [];
                foreach ($unitsData as $unit) {
                    $units[$unit['short_code']] = InventoryUnit::updateOrCreate(
                        ['marquee_id' => $marqueeId, 'short_code' => $unit['short_code']],
                        [
                            'name' => $unit['name'],
                            'description' => $unit['description'],
                            'status' => 'Active',
                            'created_by' => $adminId,
                        ]
                    );
                }

                // 3. Seed Categories
                $catFood = InventoryCategory::updateOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => 'Food Items'],
                    ['description' => 'Catering raw materials & foods', 'status' => 'Active', 'created_by' => $adminId]
                );

                $catRaw = InventoryCategory::updateOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => 'Raw Ingredients', 'parent_id' => $catFood->id],
                    ['description' => 'Spices, grains, oil', 'status' => 'Active', 'created_by' => $adminId]
                );

                $catDeco = InventoryCategory::updateOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => 'Decoration Items'],
                    ['description' => 'Flowers, drapes, stages', 'status' => 'Active', 'created_by' => $adminId]
                );

                $catCrock = InventoryCategory::updateOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => 'Crockery'],
                    ['description' => 'Plates, cups, cutlery', 'status' => 'Active', 'created_by' => $adminId]
                );

                $catFurn = InventoryCategory::updateOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => 'Furniture'],
                    ['description' => 'Chairs, tables, sofas', 'status' => 'Active', 'created_by' => $adminId]
                );

                // 4. Seed Brands
                $brandsData = ['Shan', 'National', 'Local Vendor', 'Habib Oil'];
                $brands = [];
                foreach ($brandsData as $brandName) {
                    $brands[$brandName] = InventoryBrand::updateOrCreate(
                        ['marquee_id' => $marqueeId, 'name' => $brandName],
                        ['description' => "{$brandName} brand items", 'status' => 'Active', 'created_by' => $adminId]
                    );
                }

                // 5. Seed Items
                $itemsData = [
                    [
                        'name' => 'Basmati Rice',
                        'category_id' => $catRaw->id,
                        'unit_id' => $units['Kg']->id,
                        'brand_id' => $brands['National']->id,
                        'min' => 100, 'reorder' => 150, 'rate' => 320.00
                    ],
                    [
                        'name' => 'Cooking Oil',
                        'category_id' => $catRaw->id,
                        'unit_id' => $units['Ltr']->id,
                        'brand_id' => $brands['Habib Oil']->id,
                        'min' => 50, 'reorder' => 80, 'rate' => 450.00
                    ],
                    [
                        'name' => 'Dinner Plate (Ceramic)',
                        'category_id' => $catCrock->id,
                        'unit_id' => $units['Pcs']->id,
                        'brand_id' => $brands['Local Vendor']->id,
                        'min' => 500, 'reorder' => 600, 'rate' => 180.00
                    ],
                    [
                        'name' => 'Banquet Chair (Golden)',
                        'category_id' => $catFurn->id,
                        'unit_id' => $units['Pcs']->id,
                        'brand_id' => $brands['Local Vendor']->id,
                        'min' => 200, 'reorder' => 250, 'rate' => 1200.00
                    ],
                    [
                        'name' => 'Stage LED Spotlight',
                        'category_id' => $catDeco->id,
                        'unit_id' => $units['Pcs']->id,
                        'brand_id' => $brands['Local Vendor']->id,
                        'min' => 10, 'reorder' => 15, 'rate' => 3500.00
                    ],
                ];

                $items = [];
                foreach ($itemsData as $item) {
                    $existingItem = InventoryItem::where('marquee_id', $marqueeId)->where('name', $item['name'])->first();
                    $code = $existingItem ? $existingItem->item_code : $this->inventoryService->generateNextItemCode($marqueeId);

                    $items[$item['name']] = InventoryItem::updateOrCreate(
                        ['marquee_id' => $marqueeId, 'name' => $item['name']],
                        [
                            'item_code' => $code,
                            'category_id' => $item['category_id'],
                            'unit_id' => $item['unit_id'],
                            'brand_id' => $item['brand_id'],
                            'minimum_stock_level' => $item['min'],
                            'reorder_level' => $item['reorder'],
                            'default_purchase_rate' => $item['rate'],
                            'status' => 'Active',
                            'created_by' => $adminId,
                        ]
                    );
                }

                // 6. Seed Suppliers
                $suppliersData = [
                    [
                        'name' => 'Al-Makkah Foods & Grains',
                        'contact_person' => 'Haji Muhammad Makkah',
                        'mobile' => '03001234511',
                        'city' => 'Lahore',
                        'opening' => 50000.00
                    ],
                    [
                        'name' => 'Decora Event Suppliers',
                        'contact_person' => 'Sufyan Ali',
                        'mobile' => '03001234522',
                        'city' => 'Lahore',
                        'opening' => 0.00
                    ],
                    [
                        'name' => 'Shalimar Furniture Mart',
                        'contact_person' => 'Mian Shalimar',
                        'mobile' => '03001234533',
                        'city' => 'Karachi',
                        'opening' => 120000.00
                    ],
                ];

                $suppliers = [];
                foreach ($suppliersData as $supp) {
                    $existingSupp = Supplier::where('marquee_id', $marqueeId)->where('name', $supp['name'])->first();
                    $code = $existingSupp ? $existingSupp->supplier_code : $this->inventoryService->generateNextSupplierCode($marqueeId);

                    $suppliers[$supp['name']] = Supplier::updateOrCreate(
                        ['marquee_id' => $marqueeId, 'name' => $supp['name']],
                        [
                            'supplier_code' => $code,
                            'contact_person' => $supp['contact_person'],
                            'mobile_number' => $supp['mobile'],
                            'city' => $supp['city'],
                            'opening_balance' => $supp['opening'],
                            'status' => 'Active',
                            'created_by' => $adminId,
                        ]
                    );

                    // Add opening balance transaction entry in ledger if not already recorded
                    if ($supp['opening'] > 0 && !$existingSupp) {
                        $this->inventoryService->recordSupplierTransaction(
                            $marqueeId,
                            $suppliers[$supp['name']]->id,
                            Carbon::now()->subMonths(2)->format('Y-m-d'),
                            0.00, // Debit
                            $supp['opening'], // Credit
                            'OpeningBalance',
                            $suppliers[$supp['name']]->id,
                            'OP-BAL',
                            'Opening balance payable'
                        );
                    }
                }

                // 7. Seed a Draft Purchase Order
                $poNumberDraft = 'PO-' . date('Y') . '-0001';
                $poDraft = PurchaseOrder::firstOrCreate(
                    [
                        'marquee_id' => $marqueeId,
                        'po_number' => $poNumberDraft,
                    ],
                    [
                        'branch_id' => $branch->id,
                        'supplier_id' => $suppliers['Al-Makkah Foods & Grains']->id,
                        'order_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                        'status' => 'Draft',
                        'created_by' => $adminId,
                    ]
                );

                if ($poDraft->wasRecentlyCreated) {
                    PurchaseOrderDetail::create([
                        'purchase_order_id' => $poDraft->id,
                        'item_id' => $items['Basmati Rice']->id,
                        'quantity' => 10,
                        'unit_price' => 320.00,
                        'amount' => 3200.00,
                    ]);
                }

                // 8. Seed an Approved PO
                $poNumberApp = 'PO-' . date('Y') . '-0002';
                $poApproved = PurchaseOrder::firstOrCreate(
                    [
                        'marquee_id' => $marqueeId,
                        'po_number' => $poNumberApp,
                    ],
                    [
                        'branch_id' => $branch->id,
                        'supplier_id' => $suppliers['Al-Makkah Foods & Grains']->id,
                        'order_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
                        'status' => 'Approved',
                        'created_by' => $adminId,
                    ]
                );

                if ($poApproved->wasRecentlyCreated) {
                    PurchaseOrderDetail::create([
                        'purchase_order_id' => $poApproved->id,
                        'item_id' => $items['Basmati Rice']->id,
                        'quantity' => 50,
                        'unit_price' => 310.00,
                        'amount' => 15500.00,
                    ]);
                }

                // 9. Seed a posted Purchase Invoice to check accounting double entry
                $invoiceNum = 'INV-PUR-0001';
                $invoice = PurchaseInvoice::firstOrCreate(
                    [
                        'marquee_id' => $marqueeId,
                        'invoice_number' => $invoiceNum,
                    ],
                    [
                        'branch_id' => $branch->id,
                        'supplier_id' => $suppliers['Shalimar Furniture Mart']->id,
                        'purchase_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
                        'gross_amount' => 12000.00,
                        'discount' => 500.00,
                        'tax' => 1840.00,
                        'net_amount' => 13340.00,
                        'status' => 'Draft',
                        'created_by' => $adminId,
                    ]
                );

                if ($invoice->wasRecentlyCreated) {
                    PurchaseInvoiceDetail::create([
                        'purchase_invoice_id' => $invoice->id,
                        'item_id' => $items['Banquet Chair (Golden)']->id,
                        'quantity' => 10,
                        'unit_cost' => 1200.00,
                        'amount' => 12000.00,
                    ]);

                    // Post the invoice to ledger via purchase service
                    $this->purchaseService->postPurchaseInvoice($invoice->id);
                }
            });
        }
    }
}
