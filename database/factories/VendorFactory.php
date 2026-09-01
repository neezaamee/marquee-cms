<?php

namespace Database\Factories;

use App\Models\Marquee;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        $vendors = [
            ['name' => 'Royal Signature Photography & Cinema', 'type' => 'Photography'],
            ['name' => 'Dream Wedding Floral Decor & Stage Designers', 'type' => 'Decoration'],
            ['name' => 'Intelligent DJ Sound & Lighting Productions', 'type' => 'DJ'],
            ['name' => 'Glamour Bridal Studio & Makeup Artists', 'type' => 'Makeup Artist'],
            ['name' => 'Executive Valet Parking & Protocol Services', 'type' => 'Security'],
            ['name' => 'Heavy Generator Power Rentals', 'type' => 'Generator'],
            ['name' => 'Vintage & Luxury Bridal Car Rentals', 'type' => 'Transport'],
        ];

        $vendor = fake()->randomElement($vendors);

        return [
            'marquee_id' => Marquee::factory(),
            'name' => $vendor['name'] . ' ' . fake()->numerify('###'),
            'vendor_type' => $vendor['type'],
            'contact_person' => fake()->name(),
            'phone' => '+923' . fake()->numerify('00#######'),
            'alternate_phone' => '+923' . fake()->numerify('11#######'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress() . ', Main Market',
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad', 'Faisalabad']),
            'branch_id' => null,
            'tax_ntn' => fake()->numerify('#######-#'),
            'bank_name' => 'Meezan Bank',
            'account_title' => $vendor['name'],
            'account_number_iban' => 'PK' . fake()->numerify('##MEZN0000000102######'),
            'payment_terms' => 'Net 15 days after event completion',
            'notes' => 'Authorized external vendor partner.',
            'opening_balance' => 0.00,
            'status' => 'active',
        ];
    }
}
