<?php

namespace App\Listeners;

use App\Events\AdminActionPerformed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogAdminAction
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
    public function handle(AdminActionPerformed $event): void
    {
        //
    }
}
