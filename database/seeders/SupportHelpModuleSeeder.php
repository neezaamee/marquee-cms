<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\KbArticle;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SupportCategory;
use App\Models\SupportSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupportHelpModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Support Permissions
        $permissions = [
            ['name' => 'support.view', 'label' => 'Access Support & Help Center'],
            ['name' => 'support.ticket.create', 'label' => 'Create Support Tickets'],
            ['name' => 'support.ticket.view_own', 'label' => 'View Own Support Tickets'],
            ['name' => 'support.ticket.reply', 'label' => 'Reply to Permitted Tickets'],
            ['name' => 'support.ticket.manage_all', 'label' => 'Manage All Support Tickets'],
            ['name' => 'support.ticket.assign', 'label' => 'Assign Tickets to Support Agents'],
            ['name' => 'support.ticket.internal_notes', 'label' => 'View & Write Private Internal Notes'],
            ['name' => 'support.chat.use', 'label' => 'Use Live Support Chat'],
            ['name' => 'support.chat.manage', 'label' => 'Manage Live Support Chat Inbox'],
            ['name' => 'support.faq.manage', 'label' => 'Manage Frequently Asked Questions'],
            ['name' => 'support.kb.manage', 'label' => 'Manage Knowledge Base Documentation'],
            ['name' => 'support.feedback.submit', 'label' => 'Submit Product Feedback'],
            ['name' => 'support.feedback.manage', 'label' => 'Manage Feedback & Feature Requests'],
            ['name' => 'support.changelog.manage', 'label' => 'Manage Product Changelog'],
            ['name' => 'support.announcement.manage', 'label' => 'Manage System Announcements'],
            ['name' => 'support.contact_request.manage', 'label' => 'Manage Contact Requests'],
            ['name' => 'support.analytics.view', 'label' => 'View Support Analytics & SLA'],
            ['name' => 'support.settings.manage', 'label' => 'Manage Support Configurations & SLA'],
        ];

        $permMap = [];
        foreach ($permissions as $p) {
            $perm = Permission::updateOrCreate(
                ['name' => $p['name']],
                ['label' => $p['label']]
            );
            $permMap[$p['name']] = $perm->id;
        }

        // 2. Map permissions to existing roles
        $rolesWithSupport = [
            'super_admin' => array_keys($permMap),
            'business_owner' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.chat.use', 'support.feedback.submit'],
            'owner' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.chat.use', 'support.feedback.submit'],
            'area_manager' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.chat.use', 'support.feedback.submit'],
            'branch_manager' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.chat.use', 'support.feedback.submit'],
            'accountant' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.chat.use', 'support.feedback.submit'],
            'booking_manager' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.chat.use', 'support.feedback.submit'],
            'booking_officer' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.feedback.submit'],
            'store_keeper' => ['support.view', 'support.ticket.create', 'support.ticket.view_own', 'support.ticket.reply', 'support.feedback.submit'],
            'staff' => ['support.view'],
        ];

        foreach ($rolesWithSupport as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $ids = array_map(fn($name) => $permMap[$name], $perms);
                $role->permissions()->syncWithoutDetaching($ids);
            }
        }

        // 3. Seed Default Support Categories
        $categories = [
            [
                'name' => 'Getting Started',
                'slug' => 'getting-started',
                'type' => 'general',
                'description' => 'Essential introduction, first-time setup, and onboarding guide.',
                'icon' => 'fas fa-rocket',
                'display_order' => 1,
            ],
            [
                'name' => 'Booking Management',
                'slug' => 'booking-management',
                'type' => 'general',
                'description' => 'Event registration, calendar scheduling, customer contracts, and final bills.',
                'icon' => 'fas fa-calendar-check',
                'display_order' => 2,
            ],
            [
                'name' => 'Finance & Accounting',
                'slug' => 'finance-accounting',
                'type' => 'general',
                'description' => 'Payments, double-entry vouchers, supplier FIFO settlements, and ledgers.',
                'icon' => 'fas fa-file-invoice-dollar',
                'display_order' => 3,
            ],
            [
                'name' => 'Marquee & Hall Setup',
                'slug' => 'marquee-hall-setup',
                'type' => 'general',
                'description' => 'Multi-branch venues, hall capacities, time slots, and business details.',
                'icon' => 'fas fa-hotel',
                'display_order' => 4,
            ],
            [
                'name' => 'Procurement & Inventory',
                'slug' => 'procurement-inventory',
                'type' => 'general',
                'description' => 'Stock tracking, purchase invoices, recipes, and warehouse audits.',
                'icon' => 'fas fa-boxes',
                'display_order' => 5,
            ],
            [
                'name' => 'Users & Permissions',
                'slug' => 'users-permissions',
                'type' => 'general',
                'description' => 'Staff accounts, role management, branch isolation, and login security.',
                'icon' => 'fas fa-users-cog',
                'display_order' => 6,
            ],
            [
                'name' => 'Troubleshooting & Support',
                'slug' => 'troubleshooting-support',
                'type' => 'general',
                'description' => 'Common operational queries, printer setup, and troubleshooting errors.',
                'icon' => 'fas fa-tools',
                'display_order' => 7,
            ],
            [
                'name' => 'Subscription & Billing',
                'slug' => 'subscription-billing',
                'type' => 'general',
                'description' => 'SaaS subscription plans, branch upgrades, invoice history, and payments.',
                'icon' => 'fas fa-credit-card',
                'display_order' => 8,
            ],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $catRecord = SupportCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
            $categoryMap[$cat['slug']] = $catRecord->id;
        }

        // 4. Seed Essential FAQs
        $faqs = [
            [
                'category_id' => $categoryMap['getting-started'],
                'question' => 'How do I complete the initial setup for my Marquee franchise?',
                'answer' => '<p>Upon your first login as a Business Owner, the <strong>Initial Setup Wizard</strong> guides you through four essential stages: 1) Registering your Marquee details and logo, 2) Adding your initial branches and tax configurations, 3) Defining halls and capacity slots, and 4) Creating initial staff logins. You can update any of these details at any time from the <em>Configuration</em> menu.</p>',
                'keywords' => 'setup, wizard, getting started, onboarding',
                'tags' => 'setup,onboarding',
                'is_featured' => true,
                'is_published' => true,
                'display_order' => 1,
            ],
            [
                'category_id' => $categoryMap['booking-management'],
                'question' => 'How can I lock and confirm an event slot to prevent double-booking?',
                'answer' => '<p>Navigate to <strong>Sales & Bookings &rarr; Register Booking</strong>. Select the target Branch, Hall, Event Date, and Slot (e.g., Lunch or Dinner). If the slot is already held, the system automatically alerts you with real-time collision detection. Once an advance payment is entered or confirmed, the slot transitions to <em>Confirmed</em> and is strictly reserved.</p>',
                'keywords' => 'booking, double-booking, lock slot, reservation',
                'tags' => 'booking,calendar',
                'is_featured' => true,
                'is_published' => true,
                'display_order' => 2,
            ],
            [
                'category_id' => $categoryMap['finance-accounting'],
                'question' => 'How does the automated FIFO Accounts Payable expense settlement work?',
                'answer' => '<p>When a payment is disbursed to a vendor via <em>Supplier Directory &rarr; Record Vendor Payment</em> or <em>Payment Vouchers</em>, the system automatically applies the payment chronologically (First-In-First-Out) across all outstanding Accounts Payable credit expenses for that vendor. The paid amount updates in real-time and transitions settled expenses to <em>Paid</em> status.</p>',
                'keywords' => 'fifo, expenses, accounts payable, supplier payment',
                'tags' => 'accounting,expenses',
                'is_featured' => true,
                'is_published' => true,
                'display_order' => 3,
            ],
            [
                'category_id' => $categoryMap['troubleshooting-support'],
                'question' => 'Why does my PDF or printout include the sidebar or look clipped?',
                'answer' => '<p>Ensure you are using the dedicated <strong>Print</strong> or <strong>Export PDF</strong> button on each screen rather than generic browser printing. MarqueeCMS includes custom <code>@media print</code> rules that automatically suppress the sidebar, header, and navigation, formatting the document cleanly for A4 print and formal export.</p>',
                'keywords' => 'pdf, print, sidebar, clipping, export',
                'tags' => 'print,pdf',
                'is_featured' => false,
                'is_published' => true,
                'display_order' => 4,
            ],
            [
                'category_id' => $categoryMap['marquee-hall-setup'],
                'question' => 'Can a single Business Owner manage multiple separate marquees?',
                'answer' => '<p>Yes! MarqueeCMS is designed with native multi-business architecture. Business Owners can switch between their active businesses instantly via the <strong>Active Business Switcher</strong> dropdown located on the top navigation bar. Every transaction, report, booking, and staff account is strictly isolated to the active business.</p>',
                'keywords' => 'multi-tenant, business switcher, multiple marquees',
                'tags' => 'multi-business,setup',
                'is_featured' => true,
                'is_published' => true,
                'display_order' => 5,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }

        // 5. Seed Knowledge Base Articles
        $articles = [
            [
                'category_id' => $categoryMap['getting-started'],
                'title' => 'Complete Platform Overview: Operating Your Marquee SaaS',
                'slug' => 'complete-platform-overview',
                'summary' => 'A comprehensive walkthrough of MarqueeCMS modules from booking lifecycle to double-entry accounting.',
                'content' => '<h3>Welcome to MarqueeCMS</h3><p>MarqueeCMS is an enterprise-grade ERP designed specifically for Marquees, Marriage Halls, Banquet Facilities, and Event Franchises. This guide explains the core operations of the platform:</p><h4>1. Multi-Tenant Business Switching</h4><p>Use the top navbar business switcher to toggle between marquee branches. Data scoping ensures staff and managers only see information permitted for their assigned branch.</p><h4>2. Booking Flow</h4><p>From initial inquiry to finalized settlement, every booking is tracked through automated stages: Tentative Enquiry &rarr; Confirmed Booking &rarr; Menu Selection &rarr; Final Bill Posting &rarr; Event Settlement.</p><h4>3. Accounting Integration</h4><p>Every advance payment and expense automatically posts balancing journal entries to your General Ledger, ensuring accounts receivable and accounts payable are constantly reconciled.</p>',
                'tags' => 'overview,onboarding,guide',
                'is_internal_only' => false,
                'status' => 'published',
                'reading_time_minutes' => 3,
                'views_count' => 12,
                'helpful_count' => 8,
                'unhelpful_count' => 0,
            ],
            [
                'category_id' => $categoryMap['booking-management'],
                'title' => 'Step-by-Step Guide: Creating and Posting Final Bills with PRA/FBR Tax',
                'slug' => 'guide-creating-final-bills-with-tax',
                'summary' => 'How to disaggregate stage decor, menu items, add-on services, and tax deductions on final guest billing.',
                'content' => '<h3>Final Billing Workflow</h3><p>Before an event takes place, finalize the bill to generate the formal customer invoice:</p><ol><li>Open <strong>Sales & Bookings &rarr; Booking List</strong> and locate the booking.</li><li>Click <strong>Final Bill</strong> from the action menu.</li><li>Review the guest count, menu packages, and extra services.</li><li>The system automatically computes the branch tax rate (e.g. PRA 5% or 16%) on applicable catering services.</li><li>Click <strong>Post Final Bill</strong> to generate the signed tax slip and lock the accounting balances.</li></ol>',
                'tags' => 'billing,pra,fbr,tax,invoicing',
                'is_internal_only' => false,
                'status' => 'published',
                'reading_time_minutes' => 4,
                'views_count' => 25,
                'helpful_count' => 14,
                'unhelpful_count' => 1,
            ],
            [
                'category_id' => $categoryMap['users-permissions'],
                'title' => 'Managing Staff Accounts and Role-Based Permissions',
                'slug' => 'managing-staff-accounts-and-permissions',
                'summary' => 'Best practices for provisioning cashier, booking officer, and branch manager accounts.',
                'content' => '<h3>Staff Security Best Practices</h3><p>MarqueeCMS uses strict role-based access control:</p><ul><li><strong>Business Owner:</strong> Master access to all owned marquees, financial reports, and settings.</li><li><strong>Branch Manager:</strong> Full operational access restricted to their assigned branch.</li><li><strong>Accountant / Cashier:</strong> Recording payments, vouchers, and viewing financial statements.</li><li><strong>Booking Officer:</strong> Registering inquiries, customer details, and booking contracts.</li></ul><p>Always assign staff to their specific physical branch to maintain data isolation.</p>',
                'tags' => 'roles,security,staff,permissions',
                'is_internal_only' => false,
                'status' => 'published',
                'reading_time_minutes' => 2,
                'views_count' => 18,
                'helpful_count' => 10,
                'unhelpful_count' => 0,
            ],
        ];

        foreach ($articles as $art) {
            KbArticle::updateOrCreate(
                ['slug' => $art['slug']],
                $art
            );
        }

        // 6. Seed Support Settings
        SupportSetting::set('ticket_prefix', 'TKT', 'Prefix for support ticket numbers');
        SupportSetting::set('support_email', 'support@marquee-cms.com', 'Primary support team contact email');
        SupportSetting::set('business_hours', [
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'start' => '09:00',
            'end' => '18:00',
            'timezone' => 'Asia/Karachi',
        ], 'Support desk operating hours');
        SupportSetting::set('sla_targets_hours', [
            'urgent' => ['response' => 1, 'resolution' => 4],
            'high' => ['response' => 2, 'resolution' => 8],
            'normal' => ['response' => 4, 'resolution' => 24],
            'low' => ['response' => 8, 'resolution' => 48],
        ], 'SLA targets by priority in hours');
    }
}
