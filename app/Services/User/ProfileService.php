<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (isset($data['email']) && $data['email'] !== $user->email) {
                // إعادة تعيين التحقق عند تغيير البريد
                $data['email_verified_at'] = null;
            }

            if (isset($data['phone']) && $data['phone'] !== $user->phone) {
                $data['phone_verified_at'] = null;
            }

            if (isset($data['avatar'])) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $data['avatar'] = $data['avatar']->store('avatars', 'public');
            }

            $user->fill($data)->save();

            return $user->fresh();
        });
    }

    public function updatePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill(['password' => Hash::make($new)])->save();
        // إبطال كل التوكنات بعد تغيير كلمة المرور
        $user->tokens()->delete();
    }

    public function deleteAccount(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password is incorrect.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });
    }
}
