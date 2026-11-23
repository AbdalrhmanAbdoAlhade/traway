<?php

namespace App\Listeners;

use App\Events\BookingActionPerformed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogBookingAction
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingActionPerformed $event): void
    {
        //
    }
}
