<?php

namespace App\Filament\Concerns;

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Str;

/**
 * Shared by the tenant and platform "create user" pages: leaving the password
 * blank means the new employee gets an emailed invitation and picks their own,
 * rather than the admin inventing one and passing it along out of band.
 */
trait InvitesUsers
{
    protected bool $shouldSendInvitation = false;

    protected function prepareInvitation(array $data): array
    {
        $this->shouldSendInvitation = blank($data['password'] ?? null);

        if ($this->shouldSendInvitation) {
            // The column is not nullable, so park an unusable random value.
            // Nobody can sign in with it; accepting the invitation replaces it.
            $data['password'] = Str::random(64);
        }

        return $data;
    }

    protected function sendInvitationIfRequested(User $user): void
    {
        if (! $this->shouldSendInvitation) {
            return;
        }

        $user->notify(new UserInvitation($user->generateInvitationToken()));
    }
}
