<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
            'booking' => \App\Models\Booking::class,
            'visa_application' => \App\Models\VisaApplication::class,
            'refund' => \App\Models\Refund::class,
            'payout_request' => \App\Models\PayoutRequest::class,
            'tour' => \App\Models\Tour::class,
            'hotel' => \App\Models\Hotel::class,
            'esim_package' => \App\Models\EsimPackage::class,
            'visa_offering' => \App\Models\VisaOffering::class,
        ]);
    }
}
