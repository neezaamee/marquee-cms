<?php

namespace Tests\Feature\Security;

use App\Models\ActivityLog;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class Phase2SessionAndAuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $role;
    protected $marquee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@grandpalace.test',
            'status' => 'active',
        ]);

        $this->role = Role::create([
            'name' => 'manager',
            'label' => 'Manager',
        ]);

        $this->user = User::create([
            'name' => 'Phase2 Test User',
            'email' => 'phase2user@example.com',
            'username' => 'phase2user',
            'password' => Hash::make('CurrentPassword123!'),
            'role_id' => $this->role->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
            'remember_token' => 'initial_remember_token_value_123',
        ]);
    }

    public function test_forgot_password_form_is_accessible()
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Reset Password');
        $response->assertSee('Back to login');
    }

    public function test_forgot_password_anti_enumeration_returns_identical_status_for_existing_and_non_existing_users()
    {
        Notification::fake();

        // 1. Non-existent email
        $responseNonExistent = $this->post('/forgot-password', [
            'email' => 'nobody_exists@example.com',
        ]);
        $responseNonExistent->assertRedirect();
        $responseNonExistent->assertSessionHas('status', trans('passwords.sent'));

        // 2. Existing active email
        $responseExisting = $this->post('/forgot-password', [
            'email' => 'phase2user@example.com',
        ]);
        $responseExisting->assertRedirect();
        $responseExisting->assertSessionHas('status', trans('passwords.sent'));

        // Both responses yield the exact same user feedback
        $this->assertSame(
            session('status'),
            trans('passwords.sent')
        );
    }

    public function test_forgot_password_does_not_send_link_to_inactive_user()
    {
        Notification::fake();

        $this->user->update(['status' => 'inactive']);

        $response = $this->post('/forgot-password', [
            'email' => 'phase2user@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', trans('passwords.sent'));

        Notification::assertNothingSent();
    }

    public function test_forgot_password_does_not_send_link_to_user_with_deactivated_marquee()
    {
        Notification::fake();

        $this->marquee->update(['status' => 'inactive']);

        $response = $this->post('/forgot-password', [
            'email' => 'phase2user@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', trans('passwords.sent'));

        Notification::assertNothingSent();
    }

    public function test_forgot_password_sends_notification_and_logs_activity_for_active_user()
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => '  PHASE2USER@example.com  ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', trans('passwords.sent'));

        Notification::assertSentTo($this->user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'password_reset_requested',
        ]);
    }

    public function test_password_reset_screen_is_accessible_with_token()
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($this->user->email));

        $response->assertStatus(200);
        $response->assertSee('Set New Password');
        $response->assertSee($this->user->email);
    }

    public function test_password_reset_successfully_updates_password_and_clears_token()
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'NewStrongPassword789!',
            'password_confirmation' => 'NewStrongPassword789!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', trans('passwords.reset'));

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewStrongPassword789!', $this->user->password));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'password_reset_completed',
        ]);

        // Attempting to reuse the same token must fail
        $secondAttempt = $this->post('/reset-password', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'AnotherPassword999!',
            'password_confirmation' => 'AnotherPassword999!',
        ]);

        $secondAttempt->assertSessionHasErrors('email');
    }

    public function test_profile_password_change_invalidates_other_sessions_and_rotates_remember_token()
    {
        $oldToken = $this->user->remember_token;

        $response = $this->actingAs($this->user)->post('/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'ChangedPassword555!',
            'password_confirmation' => 'ChangedPassword555!',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertTrue(Hash::check('ChangedPassword555!', $this->user->password));
        $this->assertNotEquals($oldToken, $this->user->remember_token);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'password_changed',
        ]);
    }

    public function test_concurrent_session_with_old_password_hash_is_logged_out_by_authenticate_session()
    {
        // 1. Log in the user in session 1
        $this->actingAs($this->user);
        $this->get('/dashboard')->assertStatus(200);

        // 2. Simulate user password change externally (e.g. from another device or reset)
        $this->user->update([
            'password' => Hash::make('BrandNewSecret999!'),
        ]);

        // 3. User attempts to make next request with old session hash
        // AuthenticateSession middleware detects hash mismatch and logs them out
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_page_renders_forgot_password_link_pointing_to_password_request()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('password.request'), false);
    }
}
