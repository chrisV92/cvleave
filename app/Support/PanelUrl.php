<?php

namespace App\Support;

use App\Models\User;

/**
 * Builds links into the tenant-scoped admin panel.
 *
 * Panel routes are `/admin/{tenant}/...`, so hand-written `/admin/leave-requests`
 * URLs stopped resolving when multi-tenancy landed — they matched the tenant
 * slug segment instead and 404'd. Anything linking into the panel from outside
 * it (notification emails in particular) goes through here.
 */
class PanelUrl
{
    public static function forUser(User $user, string $path = '', array $query = []): string
    {
        $slug = $user->tenant?->slug;

        // Without a tenant the best we can do is the panel root, which Filament
        // redirects to whichever tenant the user can actually reach.
        $url = $slug === null
            ? url('/admin')
            : url('/admin/'.$slug.'/'.ltrim($path, '/'));

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    public static function leaveRequestsForUser(User $user, array $query = []): string
    {
        return static::forUser($user, 'leave-requests', $query);
    }

    public static function dashboardForUser(User $user): string
    {
        return static::forUser($user);
    }
}
