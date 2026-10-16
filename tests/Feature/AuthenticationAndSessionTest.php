<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndSessionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login assigns current_session_id and records audit log.
     */
    public function test_successful_login_assigns_session_and_records_audit_log(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@clothingpos.com',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'role' => UserRole::CASHIER,
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@clothingpos.com',
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->current_session_id);
        $this->assertNotNull($user->last_login_at);

        // Verify audit log exists and does NOT contain secret/password
        $audit = AuditLog::where('action', 'auth.login_success')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertStringNotContainsString('Secret123!', json_encode($audit->new_values));
    }

    /**
     * Test failed login records audit log without leaking password.
     */
    public function test_failed_login_records_audit_log_without_leaking_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@clothingpos.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $audit = AuditLog::where('action', 'auth.failed_login')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('WrongPassword123!', json_encode($audit->new_values));
    }

    /**
     * Test single active session enforcement: new login terminates older session.
     */
    public function test_single_active_session_enforcement(): void
    {
        $user = User::factory()->create([
            'email' => 'exclusive@clothingpos.com',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'role' => UserRole::STORE_MANAGER,
        ]);

        // 1. First session login
        $this->post('/login', [
            'email' => 'exclusive@clothingpos.com',
            'password' => 'Secret123!',
        ]);
        $firstSessionId = session()->getId();
        $user->refresh();
        $this->assertEquals($firstSessionId, $user->current_session_id);

        // 2. Simulate second device login overriding current_session_id
        $user->current_session_id = 'second_device_session_xyz';
        $user->save();

        // 3. User makes a request with their old session
        $response = $this->get('/dashboard');

        // Middleware EnsureSingleActiveSession should detect mismatched session, log out, and redirect to login
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test deactivated staff account is immediately locked out and session revoked.
     */
    public function test_deactivated_account_is_immediately_locked_out(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'email' => 'inactive@clothingpos.com',
            'password' => Hash::make('Secret123!'),
            'role' => UserRole::CASHIER,
        ]);

        // Attempting to log in with inactive account
        $response = $this->post('/login', [
            'email' => 'inactive@clothingpos.com',
            'password' => 'Secret123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // If an already logged-in user becomes deactivated
        $activeUser = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::CASHIER,
        ]);

        $this->actingAs($activeUser);

        // Super Admin deactivates them in database
        $activeUser->is_active = false;
        $activeUser->save();

        // Next request from that user should immediately log them out
        $check = $this->get('/dashboard');
        $check->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test password policy: minimum 8 characters with letters, numbers, and symbols.
     */
    public function test_password_policy_enforcement(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // Too short / missing symbols
        $response = $this->actingAs($admin)->post('/staff', [
            'name' => 'Weak Staff',
            'email' => 'weak@clothingpos.com',
            'role' => 'cashier',
            'password' => 'simple',
            'password_confirmation' => 'simple',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak@clothingpos.com']);
    }

    /**
     * Test public registration does not exist.
     */
    public function test_public_registration_is_not_available(): void
    {
        $response = $this->get('/register');
        $this->assertTrue(in_array($response->status(), [404, 405]));
    }
}
