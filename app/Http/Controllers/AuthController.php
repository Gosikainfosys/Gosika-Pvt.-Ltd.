<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountEmailChangedNotification;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function showForgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink(['email' => $request->input('email')]);

        return back()->with('status', 'If the address matches an administrator account, we’ll send a reset link. Check your inbox and spam folder. If you requested a link recently, wait a minute before trying again.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $this->newPasswordRules(),
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->notify(new PasswordChangedNotification);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('success', 'Your password has been reset. You can now sign in.');
    }

    public function showEmailVerification()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function sendEmailVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Your email address is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A verification link has been sent to your email address.');
    }

    public function verifyEmail(int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $destination = Auth::check()
            ? ((int) Auth::id() === $user->id ? 'profile' : 'dashboard')
            : 'login';

        return redirect()->route($destination)->with('success', 'Your email address has been verified.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            AuditLog::log('login', 'auth', Auth::id(), 'Administrator logged in successfully');

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our administrator records.',
        ]);
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        AuditLog::log('logout', 'auth', $userId, 'Administrator logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showProfile()
    {
        return Inertia::render('System/AdminProfile', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $emailChanged = $request->input('email') !== $user->email;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'current_password' => [Rule::requiredIf($emailChanged), 'current_password'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        unset($validated['current_password']);

        if ($emailChanged) {
            $oldEmail = $user->email;
        }

        $user->fill($validated);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            Notification::route('mail', $oldEmail)
                ->notify(new AccountEmailChangedNotification($user->name, $user->email));
        }

        AuditLog::log('update', 'users', $user->id, 'Updated administrator profile information');

        return back()->with(
            'success',
            $emailChanged
                ? 'Profile updated. Check your new email address for a verification link.'
                : 'Profile updated successfully.'
        );
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => $this->newPasswordRules(),
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        AuditLog::log('update', 'users', $user->id, 'Changed administrator password');
        $request->session()->regenerate();
        $user->notify(new PasswordChangedNotification);

        return back()->with('success', 'Password updated successfully.');
    }

    private function newPasswordRules(): array
    {
        return [
            'required',
            'string',
            PasswordRule::min(8)->numbers()->symbols()->rules('regex:/[A-Z]/'),
            'confirmed',
        ];
    }
}
