<?php

namespace App\Http\Controllers\Store;

use App\Domain\Customers\Actions\RegisterCustomer;
use App\Domain\Orders\Cart\Cart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\LoginRequest;
use App\Http\Requests\Store\RegisterRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Customer registration, login, password reset and email verification.
 */
class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('store.auth.register');
    }

    public function register(RegisterRequest $request, RegisterCustomer $register): RedirectResponse
    {
        $customer = $register->handle($request->safe()->except(['accept_terms', 'marketing', 'password_confirmation']), $request->boolean('marketing'));

        Auth::guard('web')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function showLogin(): View
    {
        return view('store.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $customer = Auth::guard('web')->user();
        $customer->forceFill(['last_login_at' => now()])->saveQuietly();
        app(Cart::class)->mergeSaved($customer);

        return redirect()->intended(route('account.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('store.home');
    }

    public function showForgotPassword(): View
    {
        return view('store.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('customers')->sendResetLink(['email' => mb_strtolower(trim($request->string('email')))]);

        // Same answer whether or not the email exists (no account enumeration).
        return back()->with('status', __('Si el correo está registrado, recibirás un enlace para crear una nueva contraseña.'));
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('store.auth.reset-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer, string $password): void {
                $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($customer));
            },
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __('Contraseña cambiada. Ya puedes entrar.'))
            : back()->withInput($request->only('email'))->withErrors(['email' => __('El enlace no es válido o ha caducado. Pide uno nuevo.')]);
    }

    public function verificationNotice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('account.dashboard')
            : view('store.auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('account.dashboard')->with('status', __('Correo verificado. ¡Gracias!'));
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', __('Te hemos enviado un nuevo enlace de verificación.'));
    }
}
