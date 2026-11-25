<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PhoneVerificationController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Organization\InvitationController;
use App\Http\Controllers\Api\V1\Organization\MembershipController;
use App\Http\Controllers\Api\V1\Organization\OrganizationApplicationController;
use App\Http\Controllers\Api\V1\Organization\OrganizationController;
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
                        Route::post('switch-organization', [AuthController::class, 'switchOrganization']);
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

               Route::post(
            'organizations/{organization}/transfer-ownership',
            [OrganizationController::class, 'transferOwnership']
        )->middleware('tenant');

        /* ------------------------------------------------------
         |  Memberships (per organization)
         * ------------------------------------------------------ */
               /* ------------------------------------------------------
         |  Members and invitations (per organization, tenant-bound)
         * ------------------------------------------------------ */
        Route::prefix('organizations/{organization}')->middleware('tenant')->group(function () {
            Route::get('members', [MembershipController::class, 'index']);
            Route::get('members/{membership}', [MembershipController::class, 'show'])
                ->whereNumber('membership');
            Route::patch('members/{membership}/role', [MembershipController::class, 'updateRole'])
                ->whereNumber('membership');
            Route::patch('members/{membership}/scopes', [MembershipController::class, 'updateScopes'])
                ->whereNumber('membership');
            Route::post('members/{membership}/permissions', [MembershipController::class, 'grantPermission'])
                ->whereNumber('membership');
            Route::delete('members/{membership}/permissions/{permission}', [MembershipController::class, 'revokePermission'])
                ->whereNumber('membership');
            Route::post('members/{membership}/suspend', [MembershipController::class, 'suspend'])
                ->whereNumber('membership');
            Route::post('members/{membership}/reinstate', [MembershipController::class, 'reinstate'])
                ->whereNumber('membership');
            Route::delete('members/{membership}', [MembershipController::class, 'remove'])
                ->whereNumber('membership');

          
        });

            /* --------------------------------------------------
             |  Invitations (per organization)
             * -------------------------------------------------- */
   Route::get('invitations', [InvitationController::class, 'index']);
            Route::post('invitations', [InvitationController::class, 'store']);
            Route::post('invitations/{invitationId}/resend', [InvitationController::class, 'resend'])
                ->whereNumber('invitationId');
            Route::delete('invitations/{invitationId}', [InvitationController::class, 'revoke'])
                ->whereNumber('invitationId');
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
