<?php

namespace App\Services;

use App\Exceptions\GoogleSignInException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as GoogleUser;

class GoogleSignInService
{
    public function isAvailable(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function resolveUser(GoogleUser $profile, ?User $connectTo = null): User
    {
        $subject = (string) $profile->getId();
        $email = strtolower(trim((string) $profile->getEmail()));
        $verified = filter_var($profile->user['email_verified'] ?? $profile->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($subject === '' || strlen($subject) > 255 || ! $verified || strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new GoogleSignInException('Google did not return a verified email address. Please choose another Google account or sign in with email.');
        }

        try {
            $user = DB::transaction(function () use ($subject, $email, $profile, $connectTo) {
                $connected = User::query()->where('google_id', $subject)->lockForUpdate()->first();
                if ($connectTo) {
                    $local = User::query()->whereKey($connectTo->id)->lockForUpdate()->firstOrFail();
                    $this->assertActive($local);
                    if (strtolower($local->email) !== $email) {
                        throw new GoogleSignInException('Choose the Google account with the same email address as your Arsh account.');
                    }
                    if (($connected && $connected->id !== $local->id) || ($local->google_id && $local->google_id !== $subject)) {
                        throw new GoogleSignInException('This Google account is already connected to a different account.');
                    }
                    $local->forceFill(['google_id' => $subject, 'email_verified_at' => $local->email_verified_at ?? now()])->save();

                    return $local;
                }
                if ($connected) {
                    $this->assertActive($connected);

                    return $connected;
                }
                // A verified Google email may connect an existing account on first sign-in.
                // Subsequent sign-ins use the stable Google subject above.
                $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
                if ($existing) {
                    $this->assertActive($existing);
                    if ($existing->google_id && $existing->google_id !== $subject) {
                        throw new GoogleSignInException('This account is already connected to a different Google account. Please use that Google account or sign in with your password.');
                    }
                    $existing->forceFill([
                        'google_id' => $subject,
                        'email_verified_at' => $existing->email_verified_at ?? now(),
                    ])->save();

                    return $existing;
                }

                $new = new User;
                $new->forceFill([
                    'name' => Str::limit(trim((string) $profile->getName()) ?: Str::before($email, '@'), 120, ''),
                    'email' => $email, 'email_verified_at' => now(), 'google_id' => $subject,
                    'password' => Str::random(64), 'password_login_enabled' => false,
                    'role_id' => Role::idFor(Role::CUSTOMER), 'is_active' => true,
                ])->save();

                return $new;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new GoogleSignInException('This Google account or email is already registered. Please try signing in again.');
        }
        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        return $user;
    }

    private function assertActive(User $user): void
    {
        if (! $user->is_active) {
            throw new GoogleSignInException('Your account has been disabled. Please contact us for help.');
        }
    }
}
