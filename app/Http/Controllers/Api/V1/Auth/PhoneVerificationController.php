<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function __construct(
        private readonly PhoneVerificationService $verificationService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $code = $this->verificationService->sendCode($request->user());

        // في الإنتاج لا تُرجع الرمز، هذا للتطوير فقط
        return response()->json([
            'message' => 'Verification code sent to your phone.',
            'debug_code' => $code,
        ]);
    }

    public function verify(VerifyPhoneRequest $request): JsonResponse
    {
        $this->verificationService->verifyCode(
            $request->user(),
            $request->validated('code')
        );

        return response()->json(['message' => 'Phone verified successfully.']);
    }
}
