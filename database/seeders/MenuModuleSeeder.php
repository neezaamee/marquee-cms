<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Package;
use App\Models\Marquee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marquees = Marquee::all();
        if ($marquees->isEmpty()) {
            return;
        }

        $categoriesData = [
            [
                'name' => 'Main Course - Chicken',
                'code' => 'MC-CH',
                'description' => 'Premium chicken handis, karahis, and qormas.',
                'sort_order' => 1,
                'items' => [
                    ['name' => 'Chicken Mughlai Qorma', 'code' => 'CH-QOR', 'unit' => 'Per Plate', 'base_cost' => 250, 'selling_price' => 450],
                    ['name' => 'Chicken Butt Karahi (Desi Ghee)', 'code' => 'CH-KAR', 'unit' => 'Per Plate', 'base_cost' => 350, 'selling_price' => 650],
                    ['name' => 'Chicken White Handi', 'code' => 'CH-WHT', 'unit' => 'Per Plate', 'base_cost' => 300, 'selling_price' => 580],
                    ['name' => 'Chicken Achari Handi', 'code' => 'CH-ACH', 'unit' => 'Per Plate', 'base_cost' => 280, 'selling_price' => 520],
                    ['name' => 'Chicken Jalfrezi', 'code' => 'CH-JAL', 'unit' => 'Per Plate', 'base_cost' => 260, 'selling_price' => 490],
                ]
            ],
            [
                'name' => 'Main Course - Mutton',
                'code' => 'MC-MT',
                'description' => 'Traditional mutton specialities cooked to perfection.',
                'sort_order' => 2,
                'items' => [
                    ['name' => 'Mutton Shinwari Karahi', 'code' => 'MT-SHN', 'unit' => 'Per Plate', 'base_cost' => 600, 'selling_price' => 1100],
                    ['name' => 'Mutton Kunna (Chinioti)', 'code' => 'MT-KUN', 'unit' => 'Per Plate', 'base_cost' => 550, 'selling_price' => 1000],
                    ['name' => 'Mutton Korma (Shahi)', 'code' => 'MT-KOR', 'unit' => 'Per Plate', 'base_cost' => 500, 'selling_price' => 950],
                    ['name' => 'Mutton Stew', 'code' => 'MT-STE', 'unit' => 'Per Plate', 'base_cost' => 450, 'selling_price' => 850],
                    ['name' => 'Mutton White Handi', 'code' => 'MT-WHT', 'unit' => 'Per Plate', 'base_cost' => 580, 'selling_price' => 1050],
                ]
            ],
            [
                'name' => 'Main Course - Beef',
                'code' => 'MC-BF',
                'description' => 'Rich beef curries, nihari, and traditional local slow-cooked items.',
                'sort_order' => 3,
                'items' => [
                    ['name' => 'Shahi Beef Nihari (with Nali & Maghaz)', 'code' => 'BF-NIH', 'unit' => 'Per Plate', 'base_cost' => 350, 'selling_price' => 690],
                    ['name' => 'Beef Haleem (Special Deig)', 'code' => 'BF-HAL', 'unit' => 'Per Plate', 'base_cost' => 200, 'selling_price' => 390],
                    ['name' => 'Beef Pasanday (Pan Fried)', 'code' => 'BF-PAS', 'unit' => 'Per Plate', 'base_cost' => 300, 'selling_price' => 590],
                    ['name' => 'Beef Chilli Dry', 'code' => 'BF-CHL', 'unit' => 'Per Plate', 'base_cost' => 280, 'selling_price' => 550],
                    ['name' => 'Beef Kofta Curry', 'code' => 'BF-KOF', 'unit' => 'Per Plate', 'base_cost' => 240, 'selling_price' => 450],
                ]
            ],
            [
                'name' => 'Traditional BBQ',
                'code' => 'MC-BBQ',
                'description' => 'Charcoal-grilled kebabs, tikkas, and local skewers.',
                'sort_order' => 4,
                'items' => [
                    ['name' => 'Chicken Tikka Boti (Red)', 'code' => 'BQ-TKA', 'unit' => 'Per Plate', 'base_cost' => 180, 'selling_price' => 320],
                    ['name' => 'Chicken Malai Boti (White)', 'code' => 'BQ-MAL', 'unit' => 'Per Plate', 'base_cost' => 200, 'selling_price' => 350],
                    ['name' => 'Mutton Seekh Kabab', 'code' => 'BQ-MSK', 'unit' => 'Per Plate', 'base_cost' => 350, 'selling_price' => 600],
                    ['name' => 'Beef Reshmi Kabab', 'code' => 'BQ-BRK', 'unit' => 'Per Plate', 'base_cost' => 200, 'selling_price' => 380],
                    ['name' => 'Fish Tikka (Seasonal)', 'code' => 'BQ-FSH', 'unit' => 'Per Plate', 'base_cost' => 350, 'selling_price' => 650],
                ]
            ],
            [
                'name' => 'Rice Specialities',
                'code' => 'MC-RC',
                'description' => 'Traditional basmati rice biryanis and pulaos.',
                'sort_order' => 5,
                'items' => [
                    ['name' => 'Shahi Chicken Biryani (Basmati)', 'code' => 'RC-CBI', 'unit' => 'Per Plate', 'base_cost' => 160, 'selling_price' => 300],
                    ['name' => 'Mutton Yakhni Pulao', 'code' => 'RC-MPU', 'unit' => 'Per Plate', 'base_cost' => 300, 'selling_price' => 550],
                    ['name' => 'Beef Kabuli Pulao', 'code' => 'RC-KPU', 'unit' => 'Per Plate', 'base_cost' => 220, 'selling_price' => 420],
                    ['name' => 'Zafrani Egg Fried Rice', 'code' => 'RC-EFR', 'unit' => 'Per Plate', 'base_cost' => 120, 'selling_price' => 240],
                    ['name' => 'Plain Steamed Zeera Rice', 'code' => 'RC-ZRC', 'unit' => 'Per Plate', 'base_cost' => 80, 'selling_price' => 150],
                ]
            ],
            [
                'name' => 'Clay Oven Tandoor (Bread)',
                'code' => 'MC-BR',
                'description' => 'Freshly baked tandoori naans, rotis, and kulchas.',
                'sort_order' => 6,
                'items' => [
                    ['name' => 'Roghni Naan (Sesame Seeds)', 'code' => 'BR-ROG', 'unit' => 'Per Plate', 'base_cost' => 20, 'selling_price' => 45],
                    ['name' => 'Garlic Butter Naan', 'code' => 'BR-GAR', 'unit' => 'Per Plate', 'base_cost' => 25, 'selling_price' => 55],
                    ['name' => 'Khamiri Roti', 'code' => 'BR-KHM', 'unit' => 'Per Plate', 'base_cost' => 10, 'selling_price' => 25],
                    ['name' => 'Kalvanji Naan', 'code' => 'BR-KAL', 'unit' => 'Per Plate', 'base_cost' => 22, 'selling_price' => 50],
                    ['name' => 'Tandoori Roti', 'code' => 'BR-TAN', 'unit' => 'Per Plate', 'base_cost' => 8, 'selling_price' => 20],
                ]
            ],
            [
                'name' => 'Starters & Soups',
                'code' => 'MC-ST',
                'description' => 'Welcome appetizers and hot winter soups.',
                'sort_order' => 7,
                'items' => [
                    ['name' => 'Chicken Corn Soup', 'code' => 'ST-SOP', 'unit' => 'Per Plate', 'base_cost' => 100, 'selling_price' => 200],
                    ['name' => 'Potato Spring Rolls', 'code' => 'ST-ROL', 'unit' => 'Per Plate', 'base_cost' => 40, 'selling_price' => 90],
                    ['name' => 'Vegetable Samosa (Bite Size)', 'code' => 'ST-SAM', 'unit' => 'Per Plate', 'base_cost' => 30, 'selling_price' => 70],
                    ['name' => 'Chicken Tempura', 'code' => 'ST-TMP', 'unit' => 'Per Plate', 'base_cost' => 250, 'selling_price' => 480],
                    ['name' => 'Fish Crackers', 'code' => 'ST-CRA', 'unit' => 'Per Plate', 'base_cost' => 40, 'selling_price' => 80],
                ]
            ],
            [
                'name' => 'Salad Bar',
                'code' => 'MC-SL',
                'description' => 'Cold salads, fruit salads, and green spreads.',
                'sort_order' => 8,
                'items' => [
                    ['name' => 'Creamy Russian Salad', 'code' => 'SL-RUS', 'unit' => 'Per Plate', 'base_cost' => 70, 'selling_price' => 150],
                    ['name' => 'Fresh Green Salad (Local)', 'code' => 'SL-GRN', 'unit' => 'Per Plate', 'base_cost' => 30, 'selling_price' => 70],
                    ['name' => 'Apple Cabbage Coleslaw', 'code' => 'SL-COL', 'unit' => 'Per Plate', 'base_cost' => 50, 'selling_price' => 110],
                    ['name' => 'Kachumar Salad', 'code' => 'SL-KAC', 'unit' => 'Per Plate', 'base_cost' => 30, 'selling_price' => 60],
                    ['name' => 'Macaroni Pasta Salad', 'code' => 'SL-MAC', 'unit' => 'Per Plate', 'base_cost' => 80, 'selling_price' => 160],
                ]
            ],
            [
                'name' => 'Raita & Dips',
                'code' => 'MC-RT',
                'description' => 'Traditional raita dips and spicy mint chutneys.',
                'sort_order' => 9,
                'items' => [
                    ['name' => 'Zeera Raita (Yogurt)', 'code' => 'RT-ZEE', 'unit' => 'Per Plate', 'base_cost' => 20, 'selling_price' => 45],
                    ['name' => 'Mint & Podina Chutney', 'code' => 'RT-MNT', 'unit' => 'Per Plate', 'base_cost' => 15, 'selling_price' => 35],
                    ['name' => 'Sweet Plum Chutney (Aloo Bukharay)', 'code' => 'RT-PLM', 'unit' => 'Per Plate', 'base_cost' => 60, 'selling_price' => 130],
                    ['name' => 'Garlic Mayo Dip', 'code' => 'RT-MAY', 'unit' => 'Per Plate', 'base_cost' => 30, 'selling_price' => 60],
                ]
            ],
            [
                'name' => 'Desserts - Hot',
                'code' => 'MC-DH',
                'description' => 'Traditional hot sweet dishes for winter weddings.',
                'sort_order' => 10,
                'items' => [
                    ['name' => 'Shahi Gulab Jamun (Hot)', 'code' => 'DH-GJA', 'unit' => 'Per Plate', 'base_cost' => 50, 'selling_price' => 100],
                    ['name' => 'Gajar Halwa (with Khoya)', 'code' => 'DH-GHA', 'unit' => 'Per Plate', 'base_cost' => 100, 'selling_price' => 220],
                    ['name' => 'Jalebi Live Station', 'code' => 'DH-JAL', 'unit' => 'Per Plate', 'base_cost' => 60, 'selling_price' => 130],
                    ['name' => 'Sooji Halwa Zafrani', 'code' => 'DH-SHA', 'unit' => 'Per Plate', 'base_cost' => 40, 'selling_price' => 90],
                ]
            ],
            [
                'name' => 'Desserts - Cold',
                'code' => 'MC-DC',
                'description' => 'Traditional cold desserts, kheer, and ice cream.',
                'sort_order' => 11,
                'items' => [
                    ['name' => 'Shahi Kheer (Thoothi)', 'code' => 'DC-KHR', 'unit' => 'Per Plate', 'base_cost' => 60, 'selling_price' => 120],
                    ['name' => 'Kulfa Ice Cream (Special)', 'code' => 'DC-KUL', 'unit' => 'Per Plate', 'base_cost' => 50, 'selling_price' => 100],
                    ['name' => 'Fruit Trifle Custard', 'code' => 'DC-TRI', 'unit' => 'Per Plate', 'base_cost' => 60, 'selling_price' => 130],
                    ['name' => 'Shahi Tukray (Zafrani)', 'code' => 'DC-STU', 'unit' => 'Per Plate', 'base_cost' => 80, 'selling_price' => 160],
                    ['name' => 'Ras Malai (Pistachio)', 'code' => 'DC-RAS', 'unit' => 'Per Plate', 'base_cost' => 90, 'selling_price' => 180],
                ]
            ],
            [
                'name' => 'Beverages & Tea',
                'code' => 'MC-BV',
                'description' => 'Soft drinks, mineral water, green tea, and Kashmiri chai.',
                'sort_order' => 12,
                'items' => [
                    ['name' => 'Assorted Soft Drinks (250ml)', 'code' => 'BV-SFT', 'unit' => 'Per Plate', 'base_cost' => 30, 'selling_price' => 60],
                    ['name' => 'Mineral Water (Nestle 500ml)', 'code' => 'BV-WTR', 'unit' => 'Per Plate', 'base_cost' => 25, 'selling_price' => 50],
                    ['name' => 'Pink Kashmiri Chai (with Almonds)', 'code' => 'BV-KCH', 'unit' => 'Per Plate', 'base_cost' => 50, 'selling_price' => 110],
                    ['name' => 'Cardamom Milk Tea (Mix Chai)', 'code' => 'BV-MTE', 'unit' => 'Per Plate', 'base_cost' => 35, 'selling_price' => 80],
                    ['name' => 'Fresh Lemon Mint Margarita', 'code' => 'BV-MMG', 'unit' => 'Per Plate', 'base_cost' => 60, 'selling_price' => 140],
                ]
            ],
        ];

        foreach ($marquees as $marquee) {
            $createdCategories = [];
            $createdItems = [];

            // 1. Create Categories and Menu Items
            foreach ($categoriesData as $catIndex => $categoryDef) {
                $category = MenuCategory::updateOrCreate(
                    [
                        'marquee_id' => $marquee->id,
                        'category_code' => $categoryDef['code'],
                    ],
                    [
                        'category_name' => $categoryDef['name'],
                        'description' => $categoryDef['description'],
                        'sort_order' => $categoryDef['sort_order'],
                        'status' => 'Active',
                    ]
                );

                $createdCategories[$categoryDef['code']] = $category;

                foreach ($categoryDef['items'] as $itemDef) {
                    $item = MenuItem::updateOrCreate(
                        [
                            'marquee_id' => $marquee->id,
                            'item_code' => $itemDef['code'],
                        ],
                        [
                            'category_id' => $category->id,
                            'item_name' => $itemDef['name'],
                            'unit' => $itemDef['unit'],
                            'base_cost' => $itemDef['base_cost'],
                            'selling_price' => $itemDef['selling_price'],
                            'status' => 'Active',
                            'description' => $itemDef['name'] . ' prepared with fresh, premium ingredients.',
                        ]
                    );

                    $createdItems[$itemDef['code']] = $item;
                }
            }

            // 2. Create Packages
            $packagesDef = [
                [
                    'name' => 'Silver Wedding Package',
                    'code' => 'PKG-SLV',
                    'type' => 'Silver',
                    'min_guests' => 150,
                    'max_guests' => 400,
                    'base_price' => 30000,
                    'per_plate_price' => 1450,
                    'status' => 'Active',
                    'seasonal' => false,
                    'items' => [
                        'CH-QOR', 'RC-CBI', 'BR-ROG', 'SL-GRN', 'RT-ZEE', 'DC-KHR', 'BV-SFT', 'BV-WTR'
                    ]
                ],
                [
                    'name' => 'Gold Banqueting Package',
                    'code' => 'PKG-GLD',
                    'type' => 'Gold',
                    'min_guests' => 200,
                    'max_guests' => 800,
                    'base_price' => 50000,
                    'per_plate_price' => 1950,
                    'status' => 'Active',
                    'seasonal' => false,
                    'items' => [
                        'CH-WHT', 'BQ-TKA', 'RC-CBI', 'BR-ROG', 'BR-GAR', 'SL-RUS', 'SL-GRN', 'RT-ZEE', 'DH-GJA', 'DC-KUL', 'BV-SFT', 'BV-WTR'
                    ]
                ],
                [
                    'name' => 'Platinum Royal Package',
                    'code' => 'PKG-PLT',
                    'type' => 'Platinum',
                    'min_guests' => 250,
                    'max_guests' => 1200,
                    'base_price' => 75000,
                    'per_plate_price' => 2850,
                    'status' => 'Active',
                    'seasonal' => false,
                    'items' => [
                        'MT-SHN', 'CH-WHT', 'BQ-MAL', 'BQ-MSK', 'RC-MPU', 'BR-ROG', 'BR-GAR', 'SL-RUS', 'SL-MAC', 'RT-ZEE', 'RT-PLM', 'DH-GHA', 'DC-RAS', 'BV-SFT', 'BV-WTR', 'BV-KCH'
                    ]
                ],
                [
                    'name' => 'Winter Special Wedding Package',
                    'code' => 'PKG-WTR',
                    'type' => 'VIP',
                    'min_guests' => 100,
                    'max_guests' => 500,
                    'base_price' => 40000,
                    'per_plate_price' => 2200,
                    'status' => 'Active',
                    'seasonal' => true,
                    'season_start_date' => '2026-10-01',
                    'season_end_date' => '2027-03-31',
                    'items' => [
                        'ST-SOP', 'CH-QOR', 'BQ-MAL', 'RC-CBI', 'BR-ROG', 'SL-RUS', 'RT-ZEE', 'DH-GHA', 'DH-GJA', 'BV-SFT', 'BV-WTR', 'BV-KCH'
                    ]
                ]
            ];

            foreach ($packagesDef as $pkgDef) {
                $package = Package::updateOrCreate(
                    [
                        'marquee_id' => $marquee->id,
                        'package_code' => $pkgDef['code'],
                    ],
                    [
                        'package_name' => $pkgDef['name'],
                        'package_type' => $pkgDef['type'],
                        'minimum_guests' => $pkgDef['min_guests'],
                        'maximum_guests' => $pkgDef['max_guests'],
                        'base_price' => $pkgDef['base_price'],
                        'per_plate_price' => $pkgDef['per_plate_price'],
                        'seasonal_package' => $pkgDef['seasonal'],
                        'season_start_date' => $pkgDef['season_start_date'] ?? null,
                        'season_end_date' => $pkgDef['season_end_date'] ?? null,
                        'status' => $pkgDef['status'],
                        'description' => 'Premium ' . $pkgDef['type'] . ' level service for ' . $pkgDef['name'] . '.',
                    ]
                );

                // Attach items
                $pivotData = [];
                foreach ($pkgDef['items'] as $displayOrder => $itemCode) {
                    if (isset($createdItems[$itemCode])) {
                        $itemId = $createdItems[$itemCode]->id;
                        $pivotData[$itemId] = [
                            'quantity' => 1.00,
                            'display_order' => $displayOrder + 1,
                        ];
                    }
                }

                if (!empty($pivotData)) {
                    $package->menuItems()->sync($pivotData);
                }
            }
        }
    }
}
