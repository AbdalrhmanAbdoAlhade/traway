<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Sanctum\NewAccessToken;

class SocialAuthService
{
    /**
     * معالجة تسجيل الدخول عبر مزود اجتماعي (Google, Facebook...).
     */
    public function handleCallback(string $provider): NewAccessToken
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user;
        } else {
            // إنشاء مستخدم جديد إذا لم يكن موجودًا
            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'              => $socialUser->getName(),
                    'password'          => Hash::make(Str::random(32)),
                    'status'            => 'active',
                    'email_verified_at' => now(),
                ]
            );

            SocialAccount::create([
                'user_id'     => $user->id,
                'provider'    => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar'      => $socialUser->getAvatar(),
            ]);
        }

        if (! $user->isActive()) {
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\ValidationException::withMessages([
                    'email' => ['This account is not active.'],
                ])
            );
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user->createToken('social-' . $provider);
    }
}
