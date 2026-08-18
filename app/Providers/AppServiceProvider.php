<?php

namespace App\Providers;

use App\Models\ImpersonationLog;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The app has no standalone "login" route — everything goes through
        // the Filament admin panel's own login page.
        Authenticate::redirectUsing(fn () => route('filament.admin.auth.login'));

        Event::listen(EnterImpersonation::class, function (EnterImpersonation $event) {
            ImpersonationLog::create([
                'impersonator_id' => $event->impersonator->id,
                'impersonated_id' => $event->impersonated->id,
                'tenant_id' => $event->impersonated->tenant_id,
                'started_at' => now(),
            ]);
        });

        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event) {
            ImpersonationLog::where('impersonator_id', $event->impersonator->id)
                ->whereNull('ended_at')
                ->latest('started_at')
                ->first()
                ?->update(['ended_at' => now()]);
        });
    }
}
