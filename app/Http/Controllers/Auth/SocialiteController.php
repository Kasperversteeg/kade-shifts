<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'No account found for this email. You must be invited first.');
        }

        if (!$user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }
}
