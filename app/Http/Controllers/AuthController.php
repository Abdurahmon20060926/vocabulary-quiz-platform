<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class AuthController extends Controller
{
    private function googleDriver()
    {
        return Socialite::driver('google')->setHttpClient(new Client([
            'timeout' => 15,
            'connect_timeout' => 10,
            'force_ip_resolve' => 'v4',
        ]));
    }

    public function redirectToGoogle(): RedirectResponse
    {
        try {
            return $this->googleDriver()->redirect();
        } catch (Throwable) {
            return redirect('/')->withErrors([
                'auth' => 'Google sign-in is not configured. Set the Google OAuth credentials to enable sign-in.',
            ]);
        }
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = $this->googleDriver()->user();
        } catch (InvalidStateException $exception) {
            report($exception);

            return redirect('/')->withErrors(['auth' => 'Google sign-in expired. Please try again.']);
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/')->withErrors(['auth' => 'Google sign-in failed. Please try again.']);
        }

        try {
            $user = User::updateOrCreate(
                ['google_id' => mb_substr((string) $googleUser->getId(), 0, 255)],
                [
                    'name' => mb_substr((string) $googleUser->getName(), 0, 255),
                    'email' => mb_substr((string) $googleUser->getEmail(), 0, 255),
                    'avatar_url' => mb_substr((string) $googleUser->getAvatar(), 0, 255) ?: null,
                ]
            );

            Auth::login($user, remember: true);
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/')->withErrors([
                'auth' => 'Google sign-in failed, please try again.',
            ]);
        }

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
