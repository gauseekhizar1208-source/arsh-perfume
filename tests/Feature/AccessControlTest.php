<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\FeatureTestCase;

class AccessControlTest extends FeatureTestCase
{
    public function test_guest_is_redirected_away_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_customer_gets_403_on_admin_routes(): void
    {
        $this->actingAs($this->customer())->get('/admin')->assertForbidden();
        $this->actingAs($this->customer())->get('/admin/products')->assertForbidden();
        $this->actingAs($this->customer())->post('/admin/products', [])->assertForbidden();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_registration_cannot_assign_admin_role(): void
    {
        $adminRoleId = Role::idFor(Role::ADMIN);

        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role_id' => $adminRoleId,
            'is_active' => 1,
        ]);

        $user = User::query()->where('email', 'sneaky@example.com')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertSame(Role::idFor(Role::CUSTOMER), $user->role_id);
    }

    public function test_disabled_account_is_signed_out_on_next_request(): void
    {
        $user = User::factory()->disabled()->create();

        $this->actingAs($user)->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
