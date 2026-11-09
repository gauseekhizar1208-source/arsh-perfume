<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\FeatureTestCase;

class AdminCustomerTest extends FeatureTestCase
{
    public function test_admin_can_disable_a_customer_and_the_customer_is_signed_out(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $profileUrl = route('admin.customers.show', $customer);

        $this->actingAs($admin)->from($profileUrl)
            ->patch(route('admin.customers.toggle', $customer))
            ->assertRedirect($profileUrl)
            ->assertSessionHas('status', 'Account disabled. The customer is signed out on their next request.');

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'action' => 'customer.toggled',
            'subject_id' => $customer->id,
            'description' => "Disabled account {$customer->email}",
        ]);
        $this->get($profileUrl)->assertOk()->assertSee('Reactivate account');

        $this->actingAs($customer->fresh())->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_reactivate_a_disabled_customer(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->disabled()->create();
        $profileUrl = route('admin.customers.show', $customer);

        $this->actingAs($admin)->from($profileUrl)
            ->patch(route('admin.customers.toggle', $customer))
            ->assertRedirect($profileUrl)
            ->assertSessionHas('status', 'Account reactivated.');

        $this->assertTrue($customer->fresh()->is_active);
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'action' => 'customer.toggled',
            'subject_id' => $customer->id,
            'description' => "Reactivated account {$customer->email}",
        ]);
        $this->get($profileUrl)->assertOk()->assertSee('Disable account');
        $this->actingAs($customer->fresh())->get('/account')->assertOk();
    }

    public function test_customer_cannot_toggle_another_customers_account(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->customer())
            ->patch(route('admin.customers.toggle', $customer))->assertForbidden();

        $this->assertTrue($customer->fresh()->is_active);
        $this->assertDatabaseCount('admin_activity_logs', 0);
    }

    public function test_customer_toggle_cannot_disable_an_administrator(): void
    {
        $otherAdmin = $this->admin();

        $this->actingAs($this->admin())
            ->patch(route('admin.customers.toggle', $otherAdmin))->assertNotFound();

        $this->assertTrue($otherAdmin->fresh()->is_active);
        $this->assertDatabaseCount('admin_activity_logs', 0);
    }
}
