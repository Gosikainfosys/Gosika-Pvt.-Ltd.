<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountEmailChangedNotification;
use App\Notifications\BrandedPasswordResetNotification;
use App\Notifications\PasswordChangedNotification;
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

        $statusMessage = 'If the address matches an administrator account, we’ll send a reset link. Check your inbox and spam folder. If you requested a link recently, wait a minute before trying again.';

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', $statusMessage);

        Notification::assertSentTo($user, BrandedPasswordResetNotification::class);
        Notification::assertSentTo($user, BrandedPasswordResetNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            $this->assertSame('Reset your password', $mail->subject);
            $this->assertSame('emails.auth.password-reset', $mail->view['html']);
            $this->assertSame('emails.auth.password-reset-text', $mail->view['text']);
            $this->assertStringContainsString('images/gosika-icon.png', $mail->viewData['logoUrl']);
            $this->assertStringContainsString('/reset-password/', $mail->viewData['resetUrl']);
            $this->view($mail->view['html'], $mail->viewData)
                ->assertSee('Reset password')
                ->assertSee($mail->viewData['logoUrl'], false)
                ->assertSee($mail->viewData['resetUrl'], false);

            $plainText = view($mail->view['text'], $mail->viewData)->render();
            $this->assertStringContainsString($mail->viewData['resetUrl'], $plainText);
            $this->assertStringNotContainsString('&amp;', $plainText);

            return true;
        });

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', $statusMessage);
        Notification::assertSentToTimes($user, BrandedPasswordResetNotification::class, 1);

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', $statusMessage);
        Notification::assertSentToTimes($user, BrandedPasswordResetNotification::class, 1);
    }

    public function test_password_reset_token_updates_password_and_sends_security_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $this->from($resetUrl)->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ABCDE1!',
            'password_confirmation' => 'ABCDE1!',
        ])->assertRedirect($resetUrl)
            ->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ABCDEF1!',
            'password_confirmation' => 'ABCDEF1!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('ABCDEF1!', $user->fresh()->password));
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_change_password_requires_the_current_password_and_sends_security_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('OldPassword123!')]);
        $newPassword = 'ABCDEF1!';

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password.update'), [
                'current_password' => 'OldPassword123!',
                'password' => 'abcdef1!',
                'password_confirmation' => 'abcdef1!',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('password');

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
