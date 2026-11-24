<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService,
    ) {}

    /**
     * إعادة توجيه المستخدم إلى مزود الخدمة (Google, Facebook...).
     */
    public function redirect(string $provider): JsonResponse
    {
        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * استقبال الـ callback من المزود.
     */
    public function callback(string $provider): JsonResponse
    {
        $token = $this->socialAuthService->handleCallback($provider);

        return response()->json([
            'message' => 'Logged in via ' . $provider . ' successfully.',
            'token'   => $token->plainTextToken,
        ]);
    }
}
