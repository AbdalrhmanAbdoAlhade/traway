<?php

namespace App\Services\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    private const CODE_TTL_MINUTES = 15;

    public function sendCode(User $user): string
    {
        $user->emailVerificationCodes()
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = Str::random(6); // أو رمز رقمي

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        // إرسال البريد الإلكتروني (Mail::to($user->email)->send(...))
        // نعيد الرمز مؤقتًا للتطوير.
        return $code;
    }

    public function verifyCode(User $user, string $code): void
    {
        $record = $user->emailVerificationCodes()
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
        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
