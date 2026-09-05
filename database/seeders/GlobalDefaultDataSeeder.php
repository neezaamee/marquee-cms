<?php

namespace Database\Seeders;

use App\Models\GlobalDefaultMaster;
use Illuminate\Database\Seeder;

class GlobalDefaultDataSeeder extends Seeder
{
    /**
     * Run the database seeds for global default masters.
     */
    public function run(): void
    {
        // 1. Event Types Defaults
        $eventTypes = [
            ['name' => 'Wedding (Baraat)', 'code' => 'ET-BARAAT', 'desc' => 'Traditional wedding day event', 'extra' => ['color' => '#d9534f', 'sort_order' => 1]],
            ['name' => 'Walima Reception', 'code' => 'ET-WALIMA', 'desc' => 'Post-wedding reception banquet', 'extra' => ['color' => '#0275d8', 'sort_order' => 2]],
            ['name' => 'Mehndi Night', 'code' => 'ET-MEHNDI', 'desc' => 'Henna and music celebration night', 'extra' => ['color' => '#f0ad4e', 'sort_order' => 3]],
            ['name' => 'Engagement (Nikkah)', 'code' => 'ET-NIKKAH', 'desc' => 'Engagement and Nikkah ceremony', 'extra' => ['color' => '#5bc0de', 'sort_order' => 4]],
            ['name' => 'Birthday Party', 'code' => 'ET-BIRTHDAY', 'desc' => 'Private birthday celebration', 'extra' => ['color' => '#5cb85c', 'sort_order' => 5]],
            ['name' => 'Corporate Gala Dinner', 'code' => 'ET-CORP', 'desc' => 'Official corporate dinner or award ceremony', 'extra' => ['color' => '#6f42c1', 'sort_order' => 6]],
            ['name' => 'Conference & Seminar', 'code' => 'ET-CONF', 'desc' => 'Business conference or educational seminar', 'extra' => ['color' => '#20c997', 'sort_order' => 7]],
            ['name' => 'Qawwali / Music Evening', 'code' => 'ET-MUSIC', 'desc' => 'Musical performance or Qawwali night', 'extra' => ['color' => '#fd7e14', 'sort_order' => 8]],
            ['name' => 'Private Party & Family Gathering', 'code' => 'ET-PARTY', 'desc' => 'Private family or social gathering', 'extra' => ['color' => '#6c757d', 'sort_order' => 9]],
        ];

        foreach ($eventTypes as $et) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'event_type', 'name' => $et['name']],
                ['code' => $et['code'], 'description' => $et['desc'], 'extra_attributes' => $et['extra'], 'is_active' => true]
            );
        }

        // 2. Menu Categories Defaults
        $menuCategories = [
            ['name' => 'Pakistani Main Course', 'code' => 'CAT-PAK', 'desc' => 'Gravies, Karahis, and Biryani dishes', 'extra' => ['sort_order' => 1]],
            ['name' => 'BBQ & Grill Delicacies', 'code' => 'CAT-BBQ', 'desc' => 'Seekh Kabab, Tikka, and Malai Boti', 'extra' => ['sort_order' => 2]],
            ['name' => 'Chinese & Asian Fusion', 'code' => 'CAT-CHIN', 'desc' => 'Manchurian, Fried Rice, Chowmein', 'extra' => ['sort_order' => 3]],
            ['name' => 'Continental Specialties', 'code' => 'CAT-CONT', 'desc' => 'Steaks, Alfredo Pasta, Grilled Fish', 'extra' => ['sort_order' => 4]],
            ['name' => 'Tandoori Breads & Naans', 'code' => 'CAT-BREAD', 'desc' => 'Roghni Naan, Garlic Naan, Tandoori Roti', 'extra' => ['sort_order' => 5]],
            ['name' => 'Traditional Sweets & Desserts', 'code' => 'CAT-DESSERT', 'desc' => 'Kheer, Gulab Jamun, Gajar Halwa, Ice Cream', 'extra' => ['sort_order' => 6]],
            ['name' => 'Beverages & Soft Drinks', 'code' => 'CAT-BEV', 'desc' => 'Chai, Green Tea, Soft Drinks, Fresh Juices', 'extra' => ['sort_order' => 7]],
            ['name' => 'Salad Bar & Appetizers', 'code' => 'CAT-SALAD', 'desc' => 'Russian Salad, Fresh Green Salad, Raita', 'extra' => ['sort_order' => 8]],
        ];

        foreach ($menuCategories as $mc) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'menu_category', 'name' => $mc['name']],
                ['code' => $mc['code'], 'description' => $mc['desc'], 'extra_attributes' => $mc['extra'], 'is_active' => true]
            );
        }

        // 3. Inventory Categories Defaults
        $inventoryCategories = [
            ['name' => 'Catering Raw Meat & Poultry', 'code' => 'INV-MEAT', 'desc' => 'Mutton, Beef, Chicken raw stock'],
            ['name' => 'Fresh Produce & Vegetables', 'code' => 'INV-VEG', 'desc' => 'Potatoes, Tomatoes, Onions, Green Chillies'],
            ['name' => 'Rice, Flour & Dry Grains', 'code' => 'INV-GRAIN', 'desc' => 'Basmati Rice, Fine Wheat Flour, Lentils'],
            ['name' => 'Cooking Oil & Spices', 'code' => 'INV-SPICE', 'desc' => 'Banaspati Ghee, Cooking Oil, Whole & Ground Spices'],
            ['name' => 'Dairy & Bakery Staples', 'code' => 'INV-DAIRY', 'desc' => 'Milk, Yogurt, Cream, Butter, Khoya'],
            ['name' => 'Beverages & Cold Bottling', 'code' => 'INV-BEV', 'desc' => 'Bottled Soft Drinks, Mineral Water Curls'],
            ['name' => 'Cutlery, Crockery & Utensils', 'code' => 'INV-CROCK', 'desc' => 'Plates, Spoons, Serving Dishes, Degs'],
            ['name' => 'Housekeeping & Chemical Supplies', 'code' => 'INV-CLEAN', 'desc' => 'Detergents, Dishwashing Liquid, Disinfectants'],
            ['name' => 'Decoration, Fabric & Linen', 'code' => 'INV-DECOR', 'desc' => 'Tablecloths, Chair Covers, Stage Linen'],
            ['name' => 'Fuel & LPG Gas Cylinders', 'code' => 'INV-GAS', 'desc' => 'Commercial LPG Gas & Diesel for Generators'],
        ];

        foreach ($inventoryCategories as $ic) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'inventory_category', 'name' => $ic['name']],
                ['code' => $ic['code'], 'description' => $ic['desc'], 'is_active' => true]
            );
        }

        // 4. Units of Measurement Defaults
        $units = [
            ['name' => 'Kilogram', 'code' => 'KG', 'desc' => 'Weight unit (1000 grams)', 'extra' => ['short_code' => 'Kg']],
            ['name' => 'Gram', 'code' => 'G', 'desc' => 'Precision weight unit', 'extra' => ['short_code' => 'g']],
            ['name' => 'Piece', 'code' => 'PCS', 'desc' => 'Single item unit', 'extra' => ['short_code' => 'Pcs']],
            ['name' => 'Liter', 'code' => 'LTR', 'desc' => 'Volume unit', 'extra' => ['short_code' => 'Ltr']],
            ['name' => 'Milliliter', 'code' => 'ML', 'desc' => 'Liquid precision unit', 'extra' => ['short_code' => 'ml']],
            ['name' => 'Box', 'code' => 'BOX', 'desc' => 'Standard boxed packaging', 'extra' => ['short_code' => 'Box']],
            ['name' => 'Pack', 'code' => 'PK', 'desc' => 'Standard packet', 'extra' => ['short_code' => 'Pack']],
            ['name' => 'Dozen', 'code' => 'DZN', 'desc' => 'Group of 12 items', 'extra' => ['short_code' => 'Dzn']],
            ['name' => 'Bottle', 'code' => 'BTL', 'desc' => 'Beverage bottle unit', 'extra' => ['short_code' => 'Btl']],
            ['name' => 'Carton', 'code' => 'CTN', 'desc' => 'Bulk outer carton box', 'extra' => ['short_code' => 'Ctn']],
        ];

        foreach ($units as $u) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'inventory_unit', 'name' => $u['name']],
                ['code' => $u['code'], 'description' => $u['desc'], 'extra_attributes' => $u['extra'], 'is_active' => true]
            );
        }

        // 5. Expense Categories Defaults
        $expenseCategories = [
            ['name' => 'Electricity Utility Bill', 'code' => 'EXP-ELEC', 'desc' => 'Monthly electricity grid utility charges'],
            ['name' => 'Sui Gas Utility Bill', 'code' => 'EXP-GAS', 'desc' => 'Commercial Sui gas bill expenses'],
            ['name' => 'Water & Sewerage Utility', 'code' => 'EXP-WATER', 'desc' => 'Water supply & tanker expenses'],
            ['name' => 'Internet & Telecommunications', 'code' => 'EXP-TEL', 'desc' => 'Broadband, WiFi, landline & phone charges'],
            ['name' => 'Facility Maintenance & Repairs', 'code' => 'EXP-MAINT', 'desc' => 'Building, AC, plumbing & electrical repairs'],
            ['name' => 'Generator Fuel & Oil Maintenance', 'code' => 'EXP-FUEL', 'desc' => 'Diesel fuel and generator servicing costs'],
            ['name' => 'Staff Salaries & Wages', 'code' => 'EXP-SALARY', 'desc' => 'Monthly employee salaries & staff allowances'],
            ['name' => 'Staff Food, Tea & Welfare', 'code' => 'EXP-WELFARE', 'desc' => 'Daily staff meals, tea, and medical welfare'],
            ['name' => 'Cleaning & Hygiene Supplies', 'code' => 'EXP-CLEAN', 'desc' => 'Washing detergents, soaps & janitorial items'],
            ['name' => 'Marketing & Advertising', 'code' => 'EXP-MKT', 'desc' => 'Social media ads, flex banners & promotion'],
            ['name' => 'Office Printing & Stationery', 'code' => 'EXP-OFFICE', 'desc' => 'Paper, receipt slips, ink cartridges & pens'],
            ['name' => 'Decoration & Lighting Sub-contracting', 'code' => 'EXP-DECOR', 'desc' => 'External floral, stage & lighting sub-contractors'],
            ['name' => 'Miscellaneous Administrative Expense', 'code' => 'EXP-MISC', 'desc' => 'Uncategorized daily petty expenses'],
        ];

        foreach ($expenseCategories as $ec) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'expense_category', 'name' => $ec['name']],
                ['code' => $ec['code'], 'description' => $ec['desc'], 'is_active' => true]
            );
        }

        // 6. Department Types Defaults
        $departmentTypes = [
            ['name' => 'Administration & Management', 'code' => 'DEP-ADMIN', 'desc' => 'General executive management & admin'],
            ['name' => 'Finance, Accounts & Audit', 'code' => 'DEP-FINANCE', 'desc' => 'Billing, cash handling, and accounts'],
            ['name' => 'Human Resources (HR)', 'code' => 'DEP-HR', 'desc' => 'Staff recruitment, attendance, and payroll'],
            ['name' => 'Procurement & Purchasing', 'code' => 'DEP-PURCHASE', 'desc' => 'Vendor sourcing and purchase orders'],
            ['name' => 'Store & Inventory Warehouse', 'code' => 'DEP-STORE', 'desc' => 'Raw material stock management & issuance'],
            ['name' => 'Pakistani Kitchen & Deg Section', 'code' => 'DEP-KIT-PAK', 'desc' => 'Traditional Pakistani cuisine deg kitchen'],
            ['name' => 'BBQ Station & Live Cooking', 'code' => 'DEP-KIT-BBQ', 'desc' => 'Live BBQ grill and kebabs section'],
            ['name' => 'Chinese & Continental Kitchen', 'code' => 'DEP-KIT-CHIN', 'desc' => 'Fast food, Chinese, and continental kitchen'],
            ['name' => 'Tandoor & Bread Station', 'code' => 'DEP-TANDOOR', 'desc' => 'Tandoor ovens for naans and rotis'],
            ['name' => 'Bakery & Sweets Section', 'code' => 'DEP-SWEETS', 'desc' => 'Halwa, Kheer, and bakery items preparation'],
            ['name' => 'Housekeeping & Hall Janitorial', 'code' => 'DEP-HOUSEKEEPING', 'desc' => 'Hall cleaning, washing, and dish sanitization'],
            ['name' => 'Facility Maintenance & Sound/AC', 'code' => 'DEP-MAINT', 'desc' => 'Generators, AC plant, sound & electricals'],
            ['name' => 'Security & Parking Management', 'code' => 'DEP-SECURITY', 'desc' => 'Valet parking and hall security guards'],
            ['name' => 'Stage & Floral Decoration', 'code' => 'DEP-DECOR', 'desc' => 'Stage setup, floral arrangements & lighting'],
            ['name' => 'Front Desk & Customer Reception', 'code' => 'DEP-RECEPTION', 'desc' => 'Inquiry handling, bookings, and customer reception'],
        ];

        foreach ($departmentTypes as $dt) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'department_type', 'name' => $dt['name']],
                ['code' => $dt['code'], 'description' => $dt['desc'], 'is_active' => true]
            );
        }

        // 7. Vendor Types Defaults
        $vendorTypes = [
            ['name' => 'Food & Grocery Wholesaler', 'code' => 'VEND-FOOD', 'desc' => 'Rice, flour, ghee, and dry spices supplier'],
            ['name' => 'Meat & Poultry Supplier', 'code' => 'VEND-MEAT', 'desc' => 'Fresh chicken, mutton, and beef vendor'],
            ['name' => 'Fresh Fruit & Vegetable Vendor', 'code' => 'VEND-VEG', 'desc' => 'Daily fresh mandi vegetable vendor'],
            ['name' => 'Dairy & Cold Bottling Dealer', 'code' => 'VEND-DAIRY', 'desc' => 'Milk, ice cream, soft drink & water supplier'],
            ['name' => 'Decorator & Stage Setup Services', 'code' => 'VEND-DECOR', 'desc' => 'Stage decoration, flowers & carpet vendor'],
            ['name' => 'Sound, DJ & Audio Contractor', 'code' => 'VEND-SOUND', 'desc' => 'PA systems, sound rigs, and mic operator'],
            ['name' => 'Lighting & Electrical Contractor', 'code' => 'VEND-LIGHT', 'desc' => 'Fancy lighting, SMD screens & power cables'],
            ['name' => 'Security Personnel Agency', 'code' => 'VEND-SEC', 'desc' => 'External uniformed guards & bouncers agency'],
            ['name' => 'LPG Gas Cylinder Supplier', 'code' => 'VEND-GAS', 'desc' => 'Commercial gas cylinders & refill contractor'],
        ];

        foreach ($vendorTypes as $vt) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'vendor_type', 'name' => $vt['name']],
                ['code' => $vt['code'], 'description' => $vt['desc'], 'is_active' => true]
            );
        }

        // 8. Customer Types Defaults
        $customerTypes = [
            ['name' => 'Individual (Family / Personal)', 'code' => 'CUST-IND', 'desc' => 'Personal family event customer'],
            ['name' => 'Corporate Company / Client', 'code' => 'CUST-CORP', 'desc' => 'Registered business or corporate client'],
            ['name' => 'VIP / High Executive', 'code' => 'CUST-VIP', 'desc' => 'VIP client requiring priority management'],
            ['name' => 'Event Planner / Agency', 'code' => 'CUST-AGENCY', 'desc' => 'Professional event organizer or wedding planner'],
            ['name' => 'Repeat / Regular Customer', 'code' => 'CUST-REPEAT', 'desc' => 'Returning customer with loyalty benefits'],
        ];

        foreach ($customerTypes as $ct) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'customer_type', 'name' => $ct['name']],
                ['code' => $ct['code'], 'description' => $ct['desc'], 'is_active' => true]
            );
        }

        // 9. Payment Methods Defaults
        $paymentMethods = [
            ['name' => 'Cash in Hand', 'code' => 'PAY-CASH', 'desc' => 'Direct cash payment at counter', 'extra' => ['icon' => 'fa-money-bill-wave']],
            ['name' => 'Bank Transfer (IBFT)', 'code' => 'PAY-BANK', 'desc' => 'Online bank transfer to company account', 'extra' => ['icon' => 'fa-university']],
            ['name' => 'Bank Cheque', 'code' => 'PAY-CHEQUE', 'desc' => 'Crossed or bearer bank cheque payment', 'extra' => ['icon' => 'fa-money-check-alt']],
            ['name' => 'Credit / Debit Card (POS)', 'code' => 'PAY-CARD', 'desc' => 'Card swipe on POS terminal', 'extra' => ['icon' => 'fa-credit-card']],
            ['name' => 'JazzCash Mobile Wallet', 'code' => 'PAY-JAZZCASH', 'desc' => 'JazzCash digital wallet transfer', 'extra' => ['icon' => 'fa-mobile-alt']],
            ['name' => 'EasyPaisa Mobile Wallet', 'code' => 'PAY-EASYPAISA', 'desc' => 'EasyPaisa digital wallet transfer', 'extra' => ['icon' => 'fa-wallet']],
        ];

        foreach ($paymentMethods as $pm) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'payment_method', 'name' => $pm['name']],
                ['code' => $pm['code'], 'description' => $pm['desc'], 'extra_attributes' => $pm['extra'], 'is_active' => true]
            );
        }

        // 10. Supplier Categories Defaults
        $supplierCategories = [
            ['name' => 'Meat & Poultry', 'code' => 'SC-MEAT', 'desc' => 'Fresh and frozen poultry, beef, mutton, and meats', 'extra' => ['sort_order' => 1]],
            ['name' => 'Grocery', 'code' => 'SC-GROC', 'desc' => 'Grains, pulses, rice, flour, and dry pantry essentials', 'extra' => ['sort_order' => 2]],
            ['name' => 'Dairy', 'code' => 'SC-DAIRY', 'desc' => 'Fresh milk, yogurt, butter, cream, cheese, and dairy staples', 'extra' => ['sort_order' => 3]],
            ['name' => 'Fruits & Vegetables', 'code' => 'SC-VEG', 'desc' => 'Fresh seasonal vegetables, fruits, herbs, and salad produce', 'extra' => ['sort_order' => 4]],
            ['name' => 'Beverages', 'code' => 'SC-BEV', 'desc' => 'Soft drinks, mineral water, juices, tea, and bottling', 'extra' => ['sort_order' => 5]],
            ['name' => 'Bakery', 'code' => 'SC-BAKERY', 'desc' => 'Fresh breads, naans, buns, cakes, and confectionery', 'extra' => ['sort_order' => 6]],
            ['name' => 'Spices', 'code' => 'SC-SPICE', 'desc' => 'Whole and ground spices, seasonings, cooking oils, and condiments', 'extra' => ['sort_order' => 7]],
            ['name' => 'Disposable / Packaging', 'code' => 'SC-PKG', 'desc' => 'Disposable containers, tableware, and packaging materials', 'extra' => ['sort_order' => 8]],
            ['name' => 'Cleaning & Chemicals', 'code' => 'SC-CHEM', 'desc' => 'Detergents, dishwashing liquids, sanitizers, and cleaning supplies', 'extra' => ['sort_order' => 9]],
            ['name' => 'Equipment', 'code' => 'SC-EQUIP', 'desc' => 'Catering equipment, utensils, chafing dishes, and kitchenware', 'extra' => ['sort_order' => 10]],
            ['name' => 'Other', 'code' => 'SC-OTHER', 'desc' => 'Miscellaneous general suppliers and third-party provisioners', 'extra' => ['sort_order' => 11]],
        ];

        foreach ($supplierCategories as $sc) {
            GlobalDefaultMaster::updateOrCreate(
                ['category_type' => 'supplier_category', 'name' => $sc['name']],
                ['code' => $sc['code'], 'description' => $sc['desc'], 'extra_attributes' => $sc['extra'], 'is_active' => true]
            );
        }
    }
}
