<?php

namespace Tests\Feature\Security;

use App\Models\ActivityLog;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Phase1AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test@example.com|127.0.0.1');
        RateLimiter::clear('user@example.com|127.0.0.1');
        RateLimiter::clear('activeuser|127.0.0.1');

        $this->role = Role::create([
            'name' => 'staff',
            'label' => 'Staff',
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'username' => 'testuser',
            'password' => Hash::make('Password123!'),
            'role_id' => $this->role->id,
            'status' => 'active',
        ]);
    }

    public function test_login_input_is_trimmed_and_email_is_normalized()
    {
        $response = $this->post('/login', [
            'login' => '   USER@example.COM   ',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_login_rate_limiting_locks_out_after_five_failed_attempts()
    {
        $loginData = [
            'login' => 'user@example.com',
            'password' => 'WrongPassword999!',
        ];

        // First 5 attempts fail with standard authentication failure
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', $loginData);
            $response->assertSessionHasErrors('login');
        }

        // 6th attempt must be throttled
        $response = $this->post('/login', $loginData);
        $response->assertSessionHasErrors('login');
        $errors = session('errors')->get('login');
        $this->assertTrue(str_contains($errors[0], 'Too many login attempts'));
    }

    public function test_rate_limiter_resets_upon_successful_login()
    {
        // 2 failed attempts
        $this->post('/login', [
            'login' => 'user@example.com',
            'password' => 'WrongPassword!',
        ]);
        $this->post('/login', [
            'login' => 'user@example.com',
            'password' => 'WrongPassword!',
        ]);

        // 1 successful login
        $response = $this->post('/login', [
            'login' => 'user@example.com',
            'password' => 'Password123!',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);

        // Verify RateLimiter is cleared
        $throttleKey = 'user@example.com|127.0.0.1';
        $this->assertEquals(0, RateLimiter::attempts($throttleKey));
    }

    public function test_anti_enumeration_returns_uniform_error_for_invalid_password_and_unknown_account()
    {
        // Case 1: Unknown user
        $respUnknown = $this->post('/login', [
            'login' => 'nonexistent@example.com',
            'password' => 'SomePassword123!',
        ]);
        $respUnknown->assertSessionHasErrors(['login' => __('auth.failed')]);

        // Case 2: Known user with wrong password
        $respKnownWrong = $this->post('/login', [
            'login' => 'user@example.com',
            'password' => 'IncorrectPassword123!',
        ]);
        $respKnownWrong->assertSessionHasErrors(['login' => __('auth.failed')]);

        // Exact same error message must be returned
        $this->assertEquals(
            $respUnknown->getSession()->get('errors')->first('login'),
            $respKnownWrong->getSession()->get('errors')->first('login')
        );
    }

    public function test_deactivated_user_cannot_login_and_receives_generic_error()
    {
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('Password123!'),
            'role_id' => $this->role->id,
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'login' => 'inactive@example.com',
            'password' => 'Password123!',
        ]);

        // Must reject authentication and present generic error without leaking deactivation status
        $this->assertGuest();
        $response->assertSessionHasErrors(['login' => __('auth.failed')]);

        // Verify diagnostic reason is preserved in server-side ActivityLog
        $log = ActivityLog::where('action', 'failed_login')
            ->where('user_id', $inactiveUser->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('user_deactivated', $log->description);
    }

    public function test_user_of_inactive_tenant_cannot_login()
    {
        $inactiveMarquee = Marquee::create([
            'name' => 'Deactivated Marquee Tenant',
            'address' => 'Street 1',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'tenant@inactive.com',
            'status' => 'inactive',
        ]);

        $tenantUser = User::create([
            'name' => 'Tenant User',
            'email' => 'tenantuser@example.com',
            'password' => Hash::make('Password123!'),
            'marquee_id' => $inactiveMarquee->id,
            'role_id' => $this->role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'login' => 'tenantuser@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login' => __('auth.failed')]);

        $log = ActivityLog::where('action', 'failed_login')
            ->where('user_id', $tenantUser->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('tenant_deactivated', $log->description);
    }

    public function test_ensure_user_is_active_middleware_terminates_session_mid_request()
    {
        $this->actingAs($this->user);

        // Deactivate the user mid-session
        $this->user->status = 'inactive';
        $this->user->save();

        // Next authenticated request must trigger forced logout
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();

        // Verify session termination was logged
        $log = ActivityLog::where('action', 'session_terminated')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($log);
    }

    public function test_passwords_and_tokens_are_never_persisted_to_activity_logs()
    {
        // 1. Create a user
        $newUser = User::create([
            'name' => 'Secret User',
            'email' => 'secret@example.com',
            'password' => Hash::make('SuperSecretPassword123!'),
            'role_id' => $this->role->id,
            'status' => 'active',
        ]);

        // 2. Update password
        $newUser->password = Hash::make('NewSecretPassword999!');
        $newUser->save();

        // Fetch logs for this user model
        $logs = ActivityLog::where('model_type', User::class)
            ->where('model_id', $newUser->id)
            ->get();

        foreach ($logs as $log) {
            if (!empty($log->new_values)) {
                $this->assertArrayNotHasKey('password', $log->new_values, 'Password hash found in activity_logs new_values!');
                $this->assertArrayNotHasKey('remember_token', $log->new_values, 'Remember token found in activity_logs new_values!');
            }
            if (!empty($log->old_values)) {
                $this->assertArrayNotHasKey('password', $log->old_values, 'Password hash found in activity_logs old_values!');
                $this->assertArrayNotHasKey('remember_token', $log->old_values, 'Remember token found in activity_logs old_values!');
            }
        }
    }

    public function test_failed_login_creates_activity_log_without_password()
    {
        $this->post('/login', [
            'login' => 'user@example.com',
            'password' => 'PlaintextPasswordMustNeverBeLogged!',
        ]);

        $log = ActivityLog::where('action', 'failed_login')->latest()->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('user@example.com', $log->description);
        $this->assertStringNotContainsString('PlaintextPasswordMustNeverBeLogged!', json_encode($log));
    }
}
