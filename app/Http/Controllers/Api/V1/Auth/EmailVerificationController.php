<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verificationService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $code = $this->verificationService->sendCode($request->user());

        return response()->json([
            'message' => 'Verification code sent to your email.',
            'debug_code' => $code,
        ]);
    }

    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $this->verificationService->verifyCode(
            $request->user(),
            $request->validated('code')
        );

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
