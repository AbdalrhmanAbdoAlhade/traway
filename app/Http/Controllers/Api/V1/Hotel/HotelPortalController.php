<?php

namespace App\Http\Controllers\Api\V1\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\RoomType;
use App\Services\Authorization\TenantResolver;
use Illuminate\Http\Request;

abstract class HotelPortalController extends Controller
{
    protected function membership(Request $request): Membership
    {
        $membership = app(TenantResolver::class)->membershipFor($request->user());

        abort_if($membership === null, 403, 'No organization selected.');

        return $membership;
    }

    /**
     * A hotel id from another organization does not exist for this tenant.
     */
    protected function hotelIn(Organization $organization, Hotel $hotel): Hotel
    {
        abort_unless((int) $hotel->organization_id === (int) $organization->getKey(), 404);

        return $hotel;
    }

    protected function roomTypeIn(Organization $organization, Hotel $hotel, RoomType $roomType): RoomType
    {
        $this->hotelIn($organization, $hotel);

        abort_unless((int) $roomType->hotel_id === (int) $hotel->getKey(), 404);

        return $roomType;
    }
}
