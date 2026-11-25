<?php

namespace App\Services\Auth;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(
        private readonly PhoneVerificationService $phoneVerification,
        private readonly EmailVerificationService $emailVerification,
        private readonly AuthorizationService $authz,
        private readonly TenantResolver $tenants,
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
     *
     * If the user has exactly one active membership the token is bound to it.
     * With several memberships the token stays unbound and the client must call
     * switchOrganization() to pick one.
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

        $token = $user->createToken($deviceName);

        $memberships = $this->activeMemberships($user);

        if ($memberships->count() === 1) {
            $token->accessToken->forceFill([
                'membership_id' => $memberships->first()->getKey(),
            ])->save();
        }

        return $token;
    }

    /**
     * Issue a new token bound to the membership the user holds in $organizationId
     * and revoke the token that made the call.
     */
    public function switchOrganization(User $user, int $organizationId): NewAccessToken
    {
        $membership = $this->authz->activeMembership($user, $organizationId);

        if ($membership === null) {
            throw ValidationException::withMessages([
                'organization_id' => ['You do not have an active membership in this organization.'],
            ]);
        }

        $current = $user->currentAccessToken();

        $token = $user->createToken($current?->name ?? 'api');
        $token->accessToken->forceFill(['membership_id' => $membership->getKey()])->save();

        $current?->delete();

        $this->tenants->forget($user);

        return $token;
    }

    /**
     * Active memberships in active organizations, for the organization picker.
     *
     * @return Collection<int, Membership>
     */
    public function activeMemberships(User $user): Collection
    {
        return Membership::query()
            ->with('organization')
            ->where('user_id', $user->getKey())
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereHas('organization', fn ($query) => $query->where('status', Organization::STATUS_ACTIVE))
            ->get();
    }

    /**
     * تسجيل الخروج (حذف التوكن الحالي).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
