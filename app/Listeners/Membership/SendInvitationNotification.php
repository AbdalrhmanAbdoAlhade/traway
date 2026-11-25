<?php

namespace App\Listeners\Membership;

use App\Events\Membership\MemberInvited;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvitationNotification implements ShouldQueue
{
    public function handle(MemberInvited $event): void
    {
        // Mail::to($event->invitation->email)->send(new ...);
        // أو Notification:
        // \Notification::route('mail', $event->invitation->email)
        //     ->notify(new \App\Notifications\OrganizationInvitation($event->invitation));
    }
}
