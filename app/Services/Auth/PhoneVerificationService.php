<?php

namespace App\Services\Auth;

use App\Models\PhoneVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhoneVerificationService
{
    private const CODE_TTL_MINUTES = 5;

    /**
     * إرسال رمز تحقق (OTP) إلى رقم الهاتف.
     */
    public function sendCode(User $user): string
    {
        // إبطال أي رموز سابقة غير مستخدمة
        $user->phoneVerificationCodes()
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        PhoneVerificationCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        // هنا يتم إرسال الرمز عبر SMS (يمكن استخدام خدمة مثل Twilio).
        // لأغراض التطوير، نعيد الرمز في الاستجابة (يجب إزالته في الإنتاج).
        return $code;
    }

    /**
     * التحقق من الرمز وتأكيد رقم الهاتف.
     */
    public function verifyCode(User $user, string $code): void
    {
        $record = $user->phoneVerificationCodes()
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'code' => ['No active verification code found.'],
            ]);
        }

        if ($record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['The verification code has expired.'],
            ]);
        }

        if (! Hash::check($code, $record->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid.'],
            ]);
        }

        $record->forceFill(['used_at' => now()])->save();
        $user->forceFill(['phone_verified_at' => now()])->save();
    }
}
