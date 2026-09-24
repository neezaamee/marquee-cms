<?php

namespace Tests\Feature\Security;

use App\Livewire\ActivityLogManager;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class Phase5SystemSecurityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $ownerRole;
    protected $staffRole;

    protected $marqueeA;
    protected $marqueeB;

    protected $branchA1;
    protected $branchB1;

    protected $superAdmin;
    protected $ownerA;
    protected $staffA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->ownerRole = Role::create(['name' => 'business_owner', 'label' => 'Business Owner']);
        $this->staffRole = Role::create(['name' => 'staff', 'label' => 'Staff Member']);

        $this->marqueeA = Marquee::create([
            'name' => 'Verification Palace A',
            'address' => 'Mall Road A',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111111',
            'email' => 'marqueeA@verify.com',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $this->marqueeB = Marquee::create([
            'name' => 'Verification Banquet B',
            'address' => 'Clifton Road B',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222222',
            'email' => 'marqueeB@verify.com',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        $this->branchA1 = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Branch Lahore Central',
            'address' => 'Gulberg III',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111112',
            'status' => 'active',
        ]);

        $this->branchB1 = Branch::create([
            'marquee_id' => $this->marqueeB->id,
            'name' => 'Branch Karachi South',
            'address' => 'DHA Phase 5',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222223',
            'status' => 'active',
        ]);

        $this->superAdmin = User::create([
            'name' => 'Verification Super Admin',
            'email' => 'superadmin@verify.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->ownerA = User::create([
            'name' => 'Verification Owner A',
            'email' => 'ownerA@verify.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'status' => 'active',
        ]);
        $this->marqueeA->update(['owner_user_id' => $this->ownerA->id]);

        $this->staffA = User::create([
            'name' => 'Verification Staff A',
            'email' => 'staffA@verify.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
    }

    /**
     * 1. Test HTTP Security Headers are properly attached to responses.
     */
    public function test_http_security_headers_are_present()
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * 2. Test debug endpoint /debug-cache is completely removed (returns 404).
     */
    public function test_debug_endpoint_is_not_accessible()
    {
        $response = $this->get('/debug-cache');
        $response->assertStatus(404);
    }

    /**
     * 3. Test storage route prevents path traversal.
     */
    public function test_storage_route_path_traversal_is_blocked()
    {
        $traversalAttempts = [
            '../../.env',
            '..%2F..%2F.env',
            '....//....//.env',
            'foo/../../../etc/passwd',
        ];

        foreach ($traversalAttempts as $path) {
            $response = $this->get("/storage/{$path}");
            $this->assertTrue(in_array($response->status(), [403, 404]));
        }
    }

    /**
     * 4. Test sensitive attributes (password, remember_token) are never logged in ActivityLog.
     */
    public function test_password_and_token_are_redacted_from_activity_logs()
    {
        $testUser = User::create([
            'name' => 'Sensitive Test User',
            'email' => 'sensitive@verify.com',
            'password' => Hash::make('SecretPass123!#'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);

        $testUser->update([
            'password' => Hash::make('NewSecretPass123!#'),
            'remember_token' => 'secret_random_token_123',
        ]);

        // Query all activity logs related to this user
        $logs = ActivityLog::where('model_type', User::class)
            ->where('model_id', $testUser->id)
            ->get();

        foreach ($logs as $log) {
            $props = json_encode($log->properties);
            $this->assertStringNotContainsString('NewSecretPass123!#', $props);
            $this->assertStringNotContainsString('secret_random_token_123', $props);
            $this->assertStringNotContainsString('$2y$', $props); // bcrypt hash prefix
        }
    }

    /**
     * 5. Test ActivityLogManager enforces cross-tenant IDOR protection.
     */
    public function test_activity_log_manager_cross_tenant_idor_is_blocked()
    {
        // Create an activity log for Tenant B
        $logB = ActivityLog::create([
            'user_id' => $this->superAdmin->id,
            'marquee_id' => $this->marqueeB->id,
            'action' => 'sensitive_operation_b',
            'description' => 'Tenant B confidential action',
        ]);

        // Owner of Tenant A cannot view details of Tenant B log
        Livewire::actingAs($this->ownerA)
            ->test(ActivityLogManager::class)
            ->call('showDetailModal', $logB->id)
            ->assertSet('selectedLog', null);
    }

    /**
     * 6. Test password reset flow security.
     */
    public function test_password_reset_flow_prevents_user_enumeration()
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent_user@verify.com',
        ]);

        // Standard anti-enumeration response
        $response->assertSessionHas('status');
    }

    /**
     * 7. Test deactivating a user immediately locks them out via EnsureUserIsActive middleware.
     */
    public function test_deactivated_user_is_immediately_locked_out_mid_session()
    {
        $this->actingAs($this->staffA);

        // Active user can access authenticated route
        $this->get('/profile')->assertStatus(200);

        // Deactivate user account
        $this->staffA->update(['status' => 'disabled']);

        // Next request mid-session is immediately rejected and redirected to login with error
        $response = $this->get('/profile');
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
    }
}
