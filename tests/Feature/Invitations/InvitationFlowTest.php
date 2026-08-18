<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function inviteToken(User $user): string
{
    return $user->generateInvitationToken();
}

it('emails an invitation when the admin leaves the password blank', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();

    actingInTenant($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Νέος Υπάλληλος',
            'email' => 'neos@example.test',
            'role' => 'employee',
            'prior_experience_years' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invitee = User::where('email', 'neos@example.test')->firstOrFail();

    expect($invitee->hasPendingInvitation())->toBeTrue();
    Notification::assertSentTo($invitee, UserInvitation::class);
});

it('does not send an invitation when the admin sets a password', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();

    actingInTenant($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Με Κωδικό',
            'email' => 'withpass@example.test',
            'role' => 'employee',
            'prior_experience_years' => 0,
            'password' => 'a-perfectly-fine-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::where('email', 'withpass@example.test')->firstOrFail();

    expect($created->invitation_token)->toBeNull()
        ->and(Hash::check('a-perfectly-fine-password', $created->password))->toBeTrue();

    Notification::assertNothingSent();
});

it('lets the invitee set a password and signs them in', function () {
    $user = User::factory()->create();
    $token = inviteToken($user);

    $this->get(route('invitation.show', ['token' => $token]))
        ->assertOk()
        ->assertSee($user->name);

    $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'my-own-strong-password',
        'password_confirmation' => 'my-own-strong-password',
    ])->assertRedirect('/admin');

    $user->refresh();

    expect(Hash::check('my-own-strong-password', $user->password))->toBeTrue()
        ->and($user->invitation_token)->toBeNull()
        ->and($user->invitation_sent_at)->toBeNull()
        ->and(auth()->id())->toBe($user->id);
});

it('refuses a token that has already been used', function () {
    $user = User::factory()->create();
    $token = inviteToken($user);

    $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'first-password-set',
        'password_confirmation' => 'first-password-set',
    ])->assertRedirect('/admin');

    auth()->logout();

    $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'someone-elses-attempt',
        'password_confirmation' => 'someone-elses-attempt',
    ])->assertRedirect(route('invitation.show', ['token' => $token]));

    expect(Hash::check('first-password-set', $user->fresh()->password))->toBeTrue()
        ->and(auth()->check())->toBeFalse();
});

it('refuses an expired token', function () {
    $user = User::factory()->create();
    $token = inviteToken($user);

    Carbon::setTestNow(now()->addDays(User::INVITATION_VALID_FOR_DAYS + 1));

    $this->get(route('invitation.show', ['token' => $token]))
        ->assertOk()
        ->assertSee(__('Ο σύνδεσμος δεν ισχύει'));

    $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'too-late-for-this',
        'password_confirmation' => 'too-late-for-this',
    ])->assertRedirect(route('invitation.show', ['token' => $token]));

    expect(auth()->check())->toBeFalse();

    Carbon::setTestNow();
});

it('refuses a token that does not exist', function () {
    $this->get(route('invitation.show', ['token' => 'complete-nonsense']))
        ->assertOk()
        ->assertSee(__('Ο σύνδεσμος δεν ισχύει'));
});

it('invalidates the previous link when an invitation is resent', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $invitee = User::factory()->for($tenant)->create();

    $oldToken = inviteToken($invitee);

    actingInTenant($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('resendInvitation', $invitee);

    Notification::assertSentTo($invitee, UserInvitation::class);

    // The old link must stop working, otherwise resending would leave two
    // valid ways in.
    $this->post(route('invitation.accept', ['token' => $oldToken]), [
        'password' => 'using-the-stale-link',
        'password_confirmation' => 'using-the-stale-link',
    ])->assertRedirect(route('invitation.show', ['token' => $oldToken]));

    expect($invitee->fresh()->hasPendingInvitation())->toBeTrue();
});
