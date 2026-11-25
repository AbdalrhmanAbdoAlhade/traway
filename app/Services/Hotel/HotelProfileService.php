<?php

namespace App\Services\Hotel;

use App\Exceptions\BusinessRuleException;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationDocument;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class HotelProfileService
{
    /** Fields that must be filled before a property can be published (BR-110). */
    private const REQUIRED_FOR_PUBLISH = [
        'name', 'description', 'city', 'address', 'latitude', 'longitude',
        'star_rating', 'check_in_time', 'check_out_time', 'cover_image',
    ];

    public function __construct(private readonly AuthorizationService $authz) {}

    /**
     * @return Collection<int, Hotel>
     */
    public function listFor(Organization $organization, Membership $actor): Collection
    {
        $query = Hotel::query()
            ->forOrganization($organization)
            ->withCount('roomTypes')
            ->orderBy('id');

        return $this->authz->restrictToHotels($query, $actor, 'id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, Membership $actor, array $data): Hotel
    {
        if ($organization->type !== Organization::TYPE_HOTEL) {
            throw new BusinessRuleException('Only a hotel organization can own hotel properties.');
        }

        if ($organization->status !== Organization::STATUS_ACTIVE) {
            throw new BusinessRuleException('The organization is not active.');
        }

        if (! $actor->is_owner) {
            throw new AuthorizationException('Only the owner can add a property.');
        }

        return Hotel::create($data + [
            'organization_id' => $organization->getKey(),
            'status' => Hotel::STATUS_DRAFT,
        ])->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Hotel $hotel, array $data): Hotel
    {
        if ($hotel->status === Hotel::STATUS_SUSPENDED) {
            throw new BusinessRuleException('A suspended property cannot be edited. Contact Traway support.');
        }

        $hotel->fill($data)->save();

        return $hotel->refresh();
    }

    /**
     * Machine-readable list of what still blocks publishing. Empty = ready.
     *
     * @return array<int, string>
     */
    public function missingForPublishing(Hotel $hotel): array
    {
        $missing = [];

        foreach (self::REQUIRED_FOR_PUBLISH as $field) {
            $value = $hotel->getAttribute($field);

            if (is_array($value) ? array_filter($value) === [] : blank($value)) {
                $missing[] = $field;
            }
        }

        if (! $hotel->roomTypes()->where('is_active', true)->exists()) {
            $missing[] = 'active_room_type';
        }

        $documents = OrganizationDocument::query()
            ->where('organization_id', $hotel->organization_id)
            ->where('status', '!=', OrganizationDocument::STATUS_REJECTED)
            ->get();

        $valid = $documents->filter(
            fn (OrganizationDocument $d) => $d->status === OrganizationDocument::STATUS_VERIFIED && ! $d->isExpired()
        );

        if ($valid->isEmpty()) {
            $missing[] = 'verified_documents';
        }

        if ($documents->contains(fn (OrganizationDocument $d) => $d->status === OrganizationDocument::STATUS_PENDING)) {
            $missing[] = 'documents_pending_review';
        }

        if ($documents->contains(fn (OrganizationDocument $d) => $d->status === OrganizationDocument::STATUS_EXPIRED || $d->isExpired())) {
            $missing[] = 'documents_expired';
        }

        return $missing;
    }

    public function publish(Hotel $hotel): Hotel
    {
        if ($hotel->status === Hotel::STATUS_PUBLISHED) {
            return $hotel;
        }

        if ($hotel->status === Hotel::STATUS_SUSPENDED) {
            throw new BusinessRuleException('A suspended property cannot be published.');
        }

        $missing = $this->missingForPublishing($hotel);

        if ($missing !== []) {
            throw new BusinessRuleException(
                'The property is not ready to publish. Missing: '.implode(', ', $missing).'.'
            );
        }

        $hotel->forceFill([
            'status' => Hotel::STATUS_PUBLISHED,
            'published_at' => now(),
        ])->save();

        return $hotel->refresh();
    }

    public function unpublish(Hotel $hotel): Hotel
    {
        if ($hotel->status !== Hotel::STATUS_PUBLISHED) {
            throw new BusinessRuleException('Only a published property can be unpublished.');
        }

        $hotel->forceFill(['status' => Hotel::STATUS_DRAFT])->save();

        return $hotel->refresh();
    }
}
