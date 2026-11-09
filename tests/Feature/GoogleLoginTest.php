<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Tests\FeatureTestCase;

class GoogleLoginTest extends FeatureTestCase
{
    private MockHandler $googleResponses;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google' => [
            'client_id' => 'test-client.apps.googleusercontent.com',
            'client_secret' => 'test-secret-not-real',
            'redirect' => 'http://localhost/auth/google/callback',
        ]]);
    }

    /** Exercise Socialite's real state verification and token/userinfo mapping with a fake HTTP transport. */
    private function google(array $claims = []): void
    {
        $this->googleResponses = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'fixture-access-token', 'token_type' => 'Bearer'])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(array_merge([
                'sub' => '109876543210123456789', 'email' => 'google.customer@example.com', 'email_verified' => true, 'name' => 'Google Customer',
            ], $claims))),
        ]);
        $client = new Client(['handler' => HandlerStack::create($this->googleResponses)]);
        Socialite::shouldReceive('driver')->with('google')->andReturnUsing(fn () =>
            (new GoogleProvider(app('request'), config('services.google.client_id'), config('services.google.client_secret'), config('services.google.redirect')))->setHttpClient($client)
        );
    }

    private function begin(): string
    {
        $this->get(route('auth.google'))->assertRedirect()->assertSessionHas('state')->assertSessionHas('google_oauth_context');

        return session('state');
    }

    private function completeGoogle(string $state)
    {
        return $this->get(route('auth.google.callback', ['code' => 'fixture-code', 'state' => $state]));
    }

    public function test_missing_credentials_disable_google_without_breaking_email_login(): void
    {
        config(['services.google.client_secret' => null]);
        $this->get('/login')->assertOk()->assertSee('Continue with Google')->assertSee('disabled aria-describedby="google-unavailable"', false);
        $this->get('/register')->assertOk()->assertSee('Continue with Google');
        $this->get(route('auth.google'))->assertRedirect(route('login'))->assertSessionHas('error');
        $user = $this->customer();
        $this->post('/login', ['email' => $user->email, 'password' => 'password-123'])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_redirect_uses_google_and_session_state_without_exposing_the_secret(): void
    {
        $this->google();
        $response = $this->get(route('auth.google'));
        $url = $response->headers->get('Location');
        $this->assertSame('accounts.google.com', parse_url($url, PHP_URL_HOST));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(session('state'), $query['state']);
        $this->assertSame(config('services.google.redirect'), $query['redirect_uri']);
        $this->assertSame('select_account', $query['prompt']);
        $this->assertStringNotContainsString(config('services.google.client_secret'), $url);
    }

    public function test_new_google_customer_is_signed_in_and_guest_cart_and_checkout_intent_are_preserved(): void
    {
        $this->google(['role' => 'admin', 'role_id' => 1]);
        $variant = $this->sellableVariant();
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->withSession(['url.intended' => route('checkout.show')]);
        $state = $this->begin();
        $sessionId = session()->getId();
        $this->completeGoogle($state)->assertRedirect(route('checkout.show'));
        $user = User::query()->where('email', 'google.customer@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->password_login_enabled);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertSame(2, $user->cart->items->first()->quantity);
        $this->assertSame('109876543210123456789', $user->google_id);
    }

    public function test_connected_account_uses_google_subject_instead_of_reassigning_email(): void
    {
        $user = $this->customer();
        $user->forceFill(['google_id' => '109876543210123456789'])->save();
        $this->google(['email' => 'changed-at-google@example.com']);
        $this->completeGoogle($this->begin())->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_verified_matching_email_signs_into_the_existing_customer_and_preserves_checkout(): void
    {
        $user = User::factory()->create(['email' => 'existing.customer@example.com', 'email_verified_at' => null]);
        $passwordHash = $user->password;
        $variant = $this->sellableVariant();
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->withSession(['url.intended' => route('checkout.show')]);
        $this->google(['email' => strtoupper($user->email), 'role' => 'admin', 'role_id' => 1]);
        $state = $this->begin();
        $sessionId = session()->getId();

        $this->completeGoogle($state)->assertRedirect(route('checkout.show'))->assertSessionMissing('error');

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame('109876543210123456789', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);
        $this->assertSame($passwordHash, $user->password);
        $this->assertTrue($user->password_login_enabled);
        $this->assertFalse($user->isAdmin());
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertSame(2, $user->cart->items->first()->quantity);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_verified_matching_email_signs_into_an_existing_admin_without_changing_the_role(): void
    {
        $admin = $this->admin();
        $this->google(['email' => $admin->email]);
        $this->completeGoogle($this->begin())->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('109876543210123456789', $admin->fresh()->google_id);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_matching_email_cannot_link_or_sign_into_a_disabled_account(): void
    {
        $user = User::factory()->disabled()->create();
        $this->google(['email' => $user->email]);

        $this->completeGoogle($this->begin())->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account has been disabled. Please contact us for help.');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_unverified_matching_email_cannot_link_or_sign_into_an_existing_account(): void
    {
        $user = $this->customer();
        $this->google(['email' => $user->email, 'email_verified' => false]);

        $this->completeGoogle($this->begin())->assertRedirect(route('login'))->assertSessionHas('error');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_matching_email_cannot_replace_a_different_connected_google_identity(): void
    {
        $user = $this->customer();
        $user->forceFill(['google_id' => 'original-google-subject'])->save();
        $this->google(['email' => $user->email]);

        $this->completeGoogle($this->begin())->assertRedirect(route('login'))->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame('original-google-subject', $user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_disabled_google_account_cannot_sign_in(): void
    {
        $user = User::factory()->disabled()->create();
        $user->forceFill(['google_id' => '109876543210123456789'])->save();
        $this->google();
        $this->completeGoogle($this->begin())->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_unverified_google_email_cannot_create_an_account(): void
    {
        $this->google(['email_verified' => false]);
        $this->completeGoogle($this->begin())->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_wrong_state_is_rejected_before_contacting_google(): void
    {
        $this->google();
        $this->begin();
        $this->completeGoogle('incorrect-state')->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertSame(2, $this->googleResponses->count());
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cancelled_or_unsolicited_callbacks_do_not_create_users(): void
    {
        $this->google();
        $this->get(route('auth.google.callback', ['code' => 'fixture-code', 'state' => 'no-session']))->assertSessionHas('error');
        $state = $this->begin();
        $this->get(route('auth.google.callback', ['error' => 'access_denied', 'state' => $state]))->assertSessionHas('error');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_existing_user_must_confirm_password_before_connecting_google(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->post(route('account.google.connect'), ['google_password' => 'wrong'])->assertSessionHasErrors('google_password');
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_existing_user_can_explicitly_connect_a_verified_matching_google_account(): void
    {
        $user = $this->customer();
        $this->google(['email' => $user->email]);
        $this->actingAs($user)->post(route('account.google.connect'), ['google_password' => 'password-123'])->assertRedirect();
        $this->completeGoogle(session('state'))->assertRedirect(route('account'))->assertSessionHas('status');
        $this->assertSame('109876543210123456789', $user->fresh()->google_id);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->password_login_enabled);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_connecting_a_different_email_or_someone_elses_identity_is_rejected(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        $other->forceFill(['google_id' => '109876543210123456789'])->save();
        $this->google(['email' => $user->email]);
        $this->actingAs($user)->post(route('account.google.connect'), ['google_password' => 'password-123']);
        $this->completeGoogle(session('state'))->assertSessionHas('error');
        $this->assertNull($user->fresh()->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_only_customer_can_add_an_email_password_from_their_account(): void
    {
        $user = $this->customer();
        $user->forceFill(['google_id' => '109876543210123456789', 'password_login_enabled' => false])->save();
        $this->actingAs($user)->get('/account')->assertOk()->assertSee('Add email sign-in');
        $this->put(route('account.password.update'), ['password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHas('status');
        $this->assertTrue($user->fresh()->password_login_enabled);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'NewPassword123'])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_password_account_still_requires_its_current_password_to_change_it(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->put(route('account.password.update'), ['password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('current_password');
    }

    public function test_resetting_a_google_only_accounts_password_enables_email_login(): void
    {
        $user = $this->customer();
        $user->forceFill(['google_id' => '109876543210123456789', 'password_login_enabled' => false])->save();
        $token = Password::createToken($user);
        $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123'])->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->password_login_enabled);
    }

    public function test_previously_connected_admin_keeps_the_admin_role_and_dashboard_redirect(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['google_id' => '109876543210123456789'])->save();
        $this->google(['email' => $admin->email]);
        $this->completeGoogle($this->begin())->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_expired_google_flow_is_rejected_before_token_exchange(): void
    {
        $this->google();
        $state = $this->begin();
        session()->put('google_oauth_context', ['mode' => 'login', 'started_at' => time() - 601]);
        $this->completeGoogle($state)->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertSame(2, $this->googleResponses->count());
        $this->assertGuest();
    }

    public function test_register_cannot_set_google_identity_or_disable_password_checks(): void
    {
        $this->post('/register', [
            'name' => 'New Customer', 'email' => 'registration@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
            'google_id' => 'forged-google-id', 'password_login_enabled' => false, 'role_id' => 1,
        ])->assertRedirect(route('account'));
        $user = User::query()->where('email', 'registration@example.com')->firstOrFail();
        $this->assertNull($user->google_id);
        $this->assertTrue($user->password_login_enabled);
        $this->assertFalse($user->isAdmin());
    }

    public function test_connect_rejects_a_verified_google_email_that_does_not_match_local_account(): void
    {
        $user = $this->customer();
        $this->google(['email' => 'someone-else@example.com']);
        $this->actingAs($user)->post(route('account.google.connect'), ['google_password' => 'password-123']);
        $this->completeGoogle(session('state'))->assertSessionHas('error');
        $this->assertNull($user->fresh()->google_id);
    }
}
