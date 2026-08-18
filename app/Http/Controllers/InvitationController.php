<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $user = $this->pendingInvitee($token);

        return view('auth.accept-invitation', [
            'invalid' => $user === null,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $user = $this->pendingInvitee($token);

        if (! $user) {
            return redirect()->route('invitation.show', ['token' => $token]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->acceptInvitation($request->string('password')->value());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/admin');
    }

    /**
     * The stored token is a sha256 of what was emailed, so hash the presented
     * value and match on that. Returns null for unknown, already-used and
     * expired tokens alike — the page shows one message for all three rather
     * than revealing which.
     */
    private function pendingInvitee(string $token): ?User
    {
        $user = User::where('invitation_token', hash('sha256', $token))->first();

        return $user?->hasPendingInvitation() ? $user : null;
    }
}
