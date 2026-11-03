<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(SocialiteFactory $socialite): SymfonyRedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()
                ->route('sign-in')
                ->withErrors(['google' => 'Login Google belum dikonfigurasi. Hubungi administrator aplikasi.']);
        }

        return $socialite->driver('google')->redirect();
    }

    public function callback(Request $request, SocialiteFactory $socialite): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()
                ->route('sign-in')
                ->withErrors(['google' => 'Login Google belum dikonfigurasi. Hubungi administrator aplikasi.']);
        }

        if ($request->has('error')) {
            return redirect()->route('sign-in')->withErrors(['google' => 'Login Google dibatalkan. Silakan coba lagi.']);
        }

        try {
            $googleUser = $socialite->driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('sign-in')->withErrors(['google' => 'Sesi login Google kedaluwarsa. Silakan mulai login kembali.']);
        } catch (\Exception $exception) {
            Log::warning('Google OAuth request failed.', ['exception' => $exception::class]);

            return redirect()->route('sign-in')->withErrors(['google' => 'Layanan login Google belum dapat dihubungi. Silakan coba lagi.']);
        }

        $verified = $googleUser instanceof GoogleUser
            && filter_var($googleUser->getRaw()['email_verified'] ?? $googleUser->getRaw()['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $email = $googleUser->getEmail();

        if (! $verified || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || blank($googleUser->getId())) {
            return redirect()->route('sign-in')->withErrors(['google' => 'Google belum memverifikasi alamat email akun ini. Gunakan akun dengan email terverifikasi.']);
        }

        $email = strtolower($email);
        $user = DB::transaction(function () use ($googleUser, $email): ?User {
            $user = User::query()->where('google_id', $googleUser->getId())->lockForUpdate()->first();
            $user ??= User::query()->where('email', $email)->lockForUpdate()->first();

            if ($user && filled($user->google_id) && $user->google_id !== $googleUser->getId()) {
                return null;
            }

            $user ??= new User([
                'name' => $googleUser->getName() ?: str($email)->before('@')->headline()->toString(),
                'email' => $email,
                'password' => null,
                'role' => 'pembaca',
                'academic_level' => null,
            ]);

            $user->fill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        });

        if (! $user) {
            return redirect()->route('sign-in')->withErrors(['google' => 'Alamat email ini sudah terhubung dengan akun Google lain.']);
        }

        Auth::login($user, remember: true);

        return redirect()->route('home')->with('success', 'Berhasil masuk dengan Google.');
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
