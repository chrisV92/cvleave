<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives the panel a current company on the routes that have no slug in them.
 *
 * Most panel routes are `/admin/{tenant}/...`, so Filament resolves the tenant
 * from the URL. A few are not — `/admin/profile`, the one the user menu links
 * to, is registered outside the tenant group. On those, Filament still boots
 * the tenant menu, which asks the panel for a billing URL and is handed null,
 * and the page dies with a TypeError before it renders.
 *
 * Here a person belongs to exactly one company, so there is no ambiguity about
 * which one to pick.
 */
class SetDefaultTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();

        if ($panel?->hasTenancy() && ! Filament::getTenant()) {
            $tenant = $request->user()?->getTenants($panel)->first();

            if ($tenant) {
                Filament::setTenant($tenant, isQuiet: true);
            }
        }

        return $next($request);
    }
}
