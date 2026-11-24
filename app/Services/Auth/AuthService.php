<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(
        private readonly PhoneVerificationService $phoneVerification,
        private readonly EmailVerificationService $emailVerification,
    ) {}

    /**
     * تسجيل مستخدم جديد (بدون التحقق بعد).
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status'   => 'active', // يمكن تغييره حسب سياسة التفعيل
        ]);

        // إرسال رمز التحقق تلقائيًا (اختياري)
        if (! empty($data['phone'])) {
            $this->phoneVerification->sendCode($user);
        }
        $this->emailVerification->sendCode($user);

        return $user;
    }

    /**
     * تسجيل الدخول وإصدار توكن Sanctum.
     */
    public function login(string $email, string $password, string $deviceName = 'api'): NewAccessToken
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user->createToken($deviceName);
    }

    /**
     * تسجيل الخروج (حذف التوكن الحالي).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
