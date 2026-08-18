<?php

namespace Tests\Feature\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_route_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_invitation_acceptance_screen_can_be_rendered(): void
    {
        $invitation = Invitation::factory()->for(User::factory()->admin(), 'inviter')->create();

        $response = $this->get('/invitations/'.$invitation->token);

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.accept-invitation');
    }

    public function test_user_can_accept_a_valid_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->for($admin, 'inviter')->create([
            'email' => 'invitee@example.com',
            'role' => User::ROLE_MEMBER,
        ]);

        $component = Volt::test('pages.auth.accept-invitation', ['token' => $invitation->token])
            ->set('name', 'Invited Person')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('accept');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'invitee@example.com',
            'name' => 'Invited Person',
            'role' => User::ROLE_MEMBER,
            'invited_by' => $admin->id,
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->for($admin, 'inviter')->create([
            'expires_at' => now()->subDay(),
        ]);

        $component = Volt::test('pages.auth.accept-invitation', ['token' => $invitation->token])
            ->set('name', 'Too Late')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('accept')->assertStatus(404);

        $this->assertGuest();
    }
}
