<?php

namespace App\Events\Membership;

use App\Models\Invitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberInvited
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invitation $invitation) {}
}
