<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountEmailChangedNotification;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_is_generic_and_sends_a_reset_notification_for_known_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));

        $this->get(route('password.reset', ['token' => 'example-token', 'email' => $user->email]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', $user->email));

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'If an administrator account uses that email, a password reset link has been sent.');

        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'If an administrator account uses that email, a password reset link has been sent.');
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_password_reset_token_updates_password_and_sends_security_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewStrongPassword2026!',
            'password_confirmation' => 'NewStrongPassword2026!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewStrongPassword2026!', $user->fresh()->password));
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_change_password_requires_the_current_password_and_sends_security_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('OldPassword123!')]);
        $newPassword = 'NewStrongPassword2026!';

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password.update'), [
                'current_password' => 'incorrect',
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
        Notification::assertNothingSent();

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'OldPassword123!',
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_email_change_requires_current_password_and_verification_is_available_without_blocking_login(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);
        $profile = [
            'name' => $user->name,
            'email' => 'new@example.com',
            'phone' => '',
            'avatar' => null,
        ];

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.update'), $profile)
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame('old@example.com', $user->fresh()->email);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                ...$profile,
                'current_password' => 'OldPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated. Check your new email address for a verification link.');

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        Notification::assertSentOnDemand(
            AccountEmailChangedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'old@example.com'
        );

        $this->get(route('profile'))->assertOk();
        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/VerifyEmail'));

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );
        $invalidVerificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1('different@example.com')],
        );

        $this->get($invalidVerificationUrl)->assertForbidden();

        $this->get($verificationUrl)
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success', 'Your email address has been verified.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
