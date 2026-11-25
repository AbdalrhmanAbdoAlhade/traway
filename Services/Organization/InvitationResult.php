<?php

namespace App\Services\Organization;

use App\Models\Membership;

/**
 * The plain invitation token is returned exactly once, at creation time. Only
 * its SHA-256 hash is stored on the membership.
 */
final readonly class InvitationResult
{
    public function __construct(
        public Membership $membership,
        public string $plainToken,
    ) {}
}
