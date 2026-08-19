<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MultiBusinessStructureSeeder extends Seeder
{
    /**
     * Run the multi-business structure seeder.
     */
    public function run(): void
    {
        // 1. Fetch Roles & Plan
        $businessOwnerRole = Role::whereIn('name', ['business_owner', 'owner'])->first();
        $areaManagerRole = Role::where('name', 'area_manager')->first();
        $branchManagerRole = Role::where('name', 'branch_manager')->first();
        $accountantRole = Role::where('name', 'accountant')->first();
        $bookingOfficerRole = Role::where('name', 'booking_officer')->first();
        $standardPlan = SubscriptionPlan::first() ?? SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'code' => 'ENT_PLAN',
            'price' => 5000,
        ]);

        // 2. Create Business Owner (Ali Khan - ABC Group)
        // Created with contact info, without immediate single marquee binding
        $businessOwner = User::updateOrCreate(
            ['email' => 'ali.owner@abcgroup.com'],
            [
                'name' => 'Ali Khan (ABC Group)',
                'username' => 'ali_abcgroup',
                'password' => Hash::make('Password123!'),
                'role_id' => $businessOwnerRole->id,
                'phone' => '+923001234567',
                'status' => 'active',
                'marquee_id' => null,
                'branch_id' => null,
                'subscription_plan_id' => $standardPlan->id,
                'subscription_ends_at' => now()->addYear(),
            ]
        );

        // 3. Create First Business: "The Sheraton Marquee" (2 Branches)
        $sheratonMarquee = Marquee::updateOrCreate(
            ['email' => 'contact@sheratonmarquee.com'],
            [
                'name' => 'The Sheraton Marquee',
                'business_type' => 'Banquet Group',
                'address' => 'Canal Bank Road',
                'city' => 'Lahore',
                'province' => 'Punjab',
                'country' => 'Pakistan',
                'timezone' => 'Asia/Karachi',
                'currency' => 'PKR',
                'phone' => '+924231112233',
                'tax_authority' => 'PRA',
                'status' => 'active',
                'is_setup_completed' => true,
            ]
        );

        $sheratonBranchLahore = Branch::updateOrCreate(
            ['marquee_id' => $sheratonMarquee->id, 'name' => 'Lahore Canal Branch'],
            [
                'address' => 'Canal Bank Road near Mall Road',
                'city' => 'Lahore',
                'province' => 'Punjab',
                'phone' => '+924231112244',
                'status' => 'active',
                'is_head_office' => true,
            ]
        );

        $sheratonBranchFaisalabad = Branch::updateOrCreate(
            ['marquee_id' => $sheratonMarquee->id, 'name' => 'Faisalabad Main Branch'],
            [
                'address' => 'Canal Road near Toyota Motors',
                'city' => 'Faisalabad',
                'province' => 'Punjab',
                'phone' => '+924181112255',
                'status' => 'active',
                'is_head_office' => false,
            ]
        );

        // 4. Create Second Business: "The Star Marquee" (5 Branches)
        $starMarquee = Marquee::updateOrCreate(
            ['email' => 'contact@starmarquee.com'],
            [
                'name' => 'The Star Marquee',
                'business_type' => 'Luxury Banquet Chain',
                'address' => 'Main Shahrah-e-Faisal',
                'city' => 'Karachi',
                'province' => 'Sindh',
                'country' => 'Pakistan',
                'timezone' => 'Asia/Karachi',
                'currency' => 'PKR',
                'phone' => '+922134445566',
                'tax_authority' => 'SRB',
                'status' => 'active',
                'is_setup_completed' => true,
            ]
        );

        // Link owners using pivot
        $businessOwner->ownedMarquees()->syncWithoutDetaching([$sheratonMarquee->id, $starMarquee->id]);

        $starBranchKarachi = Branch::updateOrCreate(
            ['marquee_id' => $starMarquee->id, 'name' => 'Karachi DHA Branch'],
            [
                'address' => 'Phase VI, DHA Main Khayaban-e-Ittehad',
                'city' => 'Karachi',
                'province' => 'Sindh',
                'phone' => '+922134445577',
                'status' => 'active',
                'is_head_office' => true,
            ]
        );

        $starBranchIslamabad = Branch::updateOrCreate(
            ['marquee_id' => $starMarquee->id, 'name' => 'Islamabad Blue Area Branch'],
            [
                'address' => 'Blue Area Jinnah Avenue',
                'city' => 'Islamabad',
                'province' => 'ICT',
                'phone' => '+92512333444',
                'status' => 'active',
                'is_head_office' => false,
            ]
        );

        $starBranchRawalpindi = Branch::updateOrCreate(
            ['marquee_id' => $starMarquee->id, 'name' => 'Rawalpindi Saddar Branch'],
            [
                'address' => 'Saddar Cantt Main Road',
                'city' => 'Rawalpindi',
                'province' => 'Punjab',
                'phone' => '+92515444333',
                'status' => 'active',
                'is_head_office' => false,
            ]
        );

        $starBranchMultan = Branch::updateOrCreate(
            ['marquee_id' => $starMarquee->id, 'name' => 'Multan Bosan Road Branch'],
            [
                'address' => 'Bosan Road Opp BZU',
                'city' => 'Multan',
                'province' => 'Punjab',
                'phone' => '+92616555444',
                'status' => 'active',
            ]
        );

        $starBranchPeshawar = Branch::updateOrCreate(
            ['marquee_id' => $starMarquee->id, 'name' => 'Peshawar University Road Branch'],
            [
                'address' => 'University Road Town',
                'city' => 'Peshawar',
                'province' => 'KPK',
                'phone' => '+92915888777',
                'status' => 'active',
            ]
        );

        // 5. Create Admin / Area Manager / Branches Head (Read-Only Statistics/Analytics)
        $areaManager = User::updateOrCreate(
            ['email' => 'usman.areamgr@abcgroup.com'],
            [
                'name' => 'Usman Raza (Area Manager)',
                'username' => 'usman_areamgr',
                'password' => Hash::make('Password123!'),
                'role_id' => $areaManagerRole->id,
                'phone' => '+923009998877',
                'status' => 'active',
                'marquee_id' => $sheratonMarquee->id,
                'branch_id' => null,
            ]
        );

        // 6. Create Branch-Level Staff Accounts
        // A) Sheraton Lahore Branch Staff
        User::updateOrCreate(
            ['email' => 'tariq.bm@sheraton.com'],
            [
                'name' => 'Tariq Mahmood (Branch Manager)',
                'username' => 'tariq_bm',
                'password' => Hash::make('Password123!'),
                'role_id' => $branchManagerRole->id,
                'marquee_id' => $sheratonMarquee->id,
                'branch_id' => $sheratonBranchLahore->id,
                'phone' => '+923001112233',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bilal.acc@sheraton.com'],
            [
                'name' => 'Bilal Ahmed (Accountant)',
                'username' => 'bilal_acc',
                'password' => Hash::make('Password123!'),
                'role_id' => $accountantRole->id,
                'marquee_id' => $sheratonMarquee->id,
                'branch_id' => $sheratonBranchLahore->id,
                'phone' => '+923001112244',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'hamza.bo@sheraton.com'],
            [
                'name' => 'Hamza Farooq (Booking Officer)',
                'username' => 'hamza_bo',
                'password' => Hash::make('Password123!'),
                'role_id' => $bookingOfficerRole->id,
                'marquee_id' => $sheratonMarquee->id,
                'branch_id' => $sheratonBranchLahore->id,
                'phone' => '+923001112255',
                'status' => 'active',
            ]
        );

        // B) Star Karachi Branch Staff
        User::updateOrCreate(
            ['email' => 'salman.bm@starmarquee.com'],
            [
                'name' => 'Salman Sheikh (Branch Manager)',
                'username' => 'salman_bm',
                'password' => Hash::make('Password123!'),
                'role_id' => $branchManagerRole->id,
                'marquee_id' => $starMarquee->id,
                'branch_id' => $starBranchKarachi->id,
                'phone' => '+923214445566',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'zain.bo@starmarquee.com'],
            [
                'name' => 'Zain Ali (Booking Officer)',
                'username' => 'zain_bo',
                'password' => Hash::make('Password123!'),
                'role_id' => $bookingOfficerRole->id,
                'marquee_id' => $starMarquee->id,
                'branch_id' => $starBranchKarachi->id,
                'phone' => '+923214445577',
                'status' => 'active',
            ]
        );
    }
}
