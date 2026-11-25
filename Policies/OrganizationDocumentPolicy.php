<?php

namespace App\Policies;

use App\Models\OrganizationDocument;
use App\Models\User;

class OrganizationDocumentPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'org.profile.view')
            || $this->allows($user, 'org.documents.manage')
            || $this->platform($user, 'platform.partner_requests.view');
    }

    public function view(User $user, OrganizationDocument $document): bool
    {
        $tenantAllowed = $this->allows($user, 'org.profile.view', ['resource' => $document])
            || $this->allows($user, 'org.documents.manage', ['resource' => $document]);

        return $tenantAllowed
            || $this->platform($user, 'platform.partner_requests.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'org.documents.manage');
    }

    public function update(User $user, OrganizationDocument $document): bool
    {
        return $this->allows($user, 'org.documents.manage', ['resource' => $document]);
    }

    public function delete(User $user, OrganizationDocument $document): bool
    {
        return $this->update($user, $document);
    }

    public function verify(User $user, OrganizationDocument $document): bool
    {
        return $this->platform($user, 'platform.partner_requests.review');
    }
}
