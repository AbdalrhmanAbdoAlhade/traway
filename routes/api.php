<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PhoneVerificationController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Organization\InvitationController;
use App\Http\Controllers\Api\V1\Organization\MembershipController;
use App\Http\Controllers\Api\V1\Organization\OrganizationApplicationController;
use App\Http\Controllers\Api\V1\Organization\OrganizationController;
use App\Http\Controllers\Api\V1\Organization\OrganizationServiceController;
use App\Http\Controllers\Api\V1\Organization\RoleTemplateController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /* ----------------------------------------------------------
     |  Auth — Public
     * ---------------------------------------------------------- */
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        Route::get('social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
        Route::get('social/{provider}/callback', [SocialAuthController::class, 'callback']);
    });

    /* ----------------------------------------------------------
     |  Auth — Requires Sanctum
     * ---------------------------------------------------------- */
    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);

            Route::post('phone/send',   [PhoneVerificationController::class, 'send']);
            Route::post('phone/verify', [PhoneVerificationController::class, 'verify']);

            Route::post('email/send',   [EmailVerificationController::class, 'send']);
            Route::post('email/verify', [EmailVerificationController::class, 'verify']);
        });

        /* ------------------------------------------------------
         |  Profile
         * ------------------------------------------------------ */
        Route::prefix('profile')->group(function () {
            Route::get('/',         [ProfileController::class, 'show']);
            Route::patch('/',       [ProfileController::class, 'update']);
            Route::put('/password', [ProfileController::class, 'updatePassword']);
            Route::delete('/',      [ProfileController::class, 'destroy']);
        });

        /* ------------------------------------------------------
         |  Organizations
         * ------------------------------------------------------ */
        Route::apiResource('organizations', OrganizationController::class);

        // Organization service requests (owner) and platform service lifecycle.
        Route::post(
            'organizations/{organization}/services',
            [OrganizationServiceController::class, 'request']
        );

        Route::post(
            'platform/organization-services/{organizationService}/approve',
            [OrganizationServiceController::class, 'approve']
        );
        Route::post(
            'platform/organization-services/{organizationService}/reject',
            [OrganizationServiceController::class, 'reject']
        );
        Route::post(
            'platform/organization-services/{organizationService}/suspend',
            [OrganizationServiceController::class, 'suspend']
        );
        Route::post(
            'platform/organization-services/{organizationService}/reactivate',
            [OrganizationServiceController::class, 'reactivate']
        );
        Route::post(
            'platform/organization-services/{organizationService}/disable',
            [OrganizationServiceController::class, 'disable']
        );

        Route::post(
            'organizations/{organization}/transfer-ownership',
            [OrganizationController::class, 'transferOwnership']
        );

        /* ------------------------------------------------------
         |  Memberships (per organization)
         * ------------------------------------------------------ */
        Route::prefix('organizations/{organization}')->group(function () {
            Route::get('members', [MembershipController::class, 'index']);

            Route::patch(
                'members/{membership}/role',
                [MembershipController::class, 'updateRole']
            );
            Route::patch(
                'members/{membership}/scopes',
                [MembershipController::class, 'updateScopes']
            );
            Route::post(
                'members/{membership}/suspend',
                [MembershipController::class, 'suspend']
            );
            Route::delete(
                'members/{membership}',
                [MembershipController::class, 'remove']
            );

            /* --------------------------------------------------
             |  Invitations (per organization)
             * -------------------------------------------------- */
            Route::get('invitations',  [InvitationController::class, 'index']);
            Route::post('invitations', [InvitationController::class, 'store']);

            Route::post(
                'invitations/{invitationId}/resend',
                [InvitationController::class, 'resend']
            );
            Route::delete(
                'invitations/{invitationId}',
                [InvitationController::class, 'revoke']
            );
        });

        /* ------------------------------------------------------
         |  Invitations — Accept / Decline (via token)
         * ------------------------------------------------------ */
        Route::post('invitations/{token}/accept',  [InvitationController::class, 'accept']);
        Route::post('invitations/{token}/decline', [InvitationController::class, 'decline']);

        /* ------------------------------------------------------
         |  Role Templates
         * ------------------------------------------------------ */
        Route::apiResource('role-templates', RoleTemplateController::class)
            ->parameters(['role-templates' => 'roleTemplate']);

        /* ------------------------------------------------------
         |  Organization Applications
         * ------------------------------------------------------ */
        Route::apiResource(
            'organization-applications',
            OrganizationApplicationController::class
        )->only(['index', 'store', 'show']);

        Route::post(
            'organization-applications/{application}/approve',
            [OrganizationApplicationController::class, 'approve']
        );
        Route::post(
            'organization-applications/{application}/reject',
            [OrganizationApplicationController::class, 'reject']
        );
    });
});
