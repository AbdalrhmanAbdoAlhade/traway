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
use App\Http\Controllers\Api\V1\Hotel\HotelBookingController;
use App\Http\Controllers\Api\V1\Hotel\HotelController;
use App\Http\Controllers\Api\V1\Hotel\HotelReportController;
use App\Http\Controllers\Api\V1\Hotel\RoomRateController;
use App\Http\Controllers\Api\V1\Hotel\RoomTypeController;
use App\Http\Controllers\Api\V1\Organization\OrganizationRoleController;
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
                        Route::get('roles', [OrganizationRoleController::class, 'index']);

            /* ---- Hotel portal ---- */
            Route::get('hotels', [HotelController::class, 'index']);
            Route::post('hotels', [HotelController::class, 'store']);
            Route::get('hotels/{hotel}', [HotelController::class, 'show'])->whereNumber('hotel');
            Route::patch('hotels/{hotel}', [HotelController::class, 'update'])->whereNumber('hotel');
            Route::get('hotels/{hotel}/readiness', [HotelController::class, 'readiness'])->whereNumber('hotel');
            Route::post('hotels/{hotel}/publish', [HotelController::class, 'publish'])->whereNumber('hotel');
            Route::post('hotels/{hotel}/unpublish', [HotelController::class, 'unpublish'])->whereNumber('hotel');

            Route::prefix('hotels/{hotel}/room-types')->whereNumber('hotel')->group(function () {
                Route::get('/', [RoomTypeController::class, 'index']);
                Route::post('/', [RoomTypeController::class, 'store']);
                Route::get('{roomType}', [RoomTypeController::class, 'show'])->whereNumber('roomType');
                Route::patch('{roomType}', [RoomTypeController::class, 'update'])->whereNumber('roomType');
                Route::delete('{roomType}', [RoomTypeController::class, 'destroy'])->whereNumber('roomType');
                Route::get('{roomType}/calendar', [RoomRateController::class, 'calendar'])->whereNumber('roomType');
                Route::put('{roomType}/calendar', [RoomRateController::class, 'bulkUpdate'])->whereNumber('roomType');
            });

            Route::get('hotel-bookings', [HotelBookingController::class, 'index']);
            Route::get('hotel-bookings/export', [HotelBookingController::class, 'export']);
            Route::get('hotel-bookings/{booking}', [HotelBookingController::class, 'show'])->whereNumber('booking');
            Route::post('hotel-bookings/{booking}/check-in', [HotelBookingController::class, 'checkIn'])->whereNumber('booking');
            Route::post('hotel-bookings/{booking}/check-out', [HotelBookingController::class, 'checkOut'])->whereNumber('booking');

            Route::get('hotel-reports/overview', [HotelReportController::class, 'overview']);
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
