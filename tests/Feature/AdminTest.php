<?php

namespace Tests\Feature;

use App\Livewire\Transactions\FormModal;
use App\Mail\InvitationMail;
use App\Models\Category;
use App\Models\Invitation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_members_cannot_access_admin_routes(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/team')->assertForbidden();
        $this->actingAs($member)->get('/admin/invitations')->assertForbidden();
    }

    public function test_admin_can_view_team_report_with_every_members_totals(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        Transaction::factory()->for($member)->create(['type' => 'collection', 'amount' => 300]);
        Transaction::factory()->for($member)->create(['type' => 'expense', 'amount' => 120]);

        Volt::actingAs($admin)
            ->test('admin.team-report')
            ->assertSee($member->name)
            ->assertSee('300.00')
            ->assertSee('120.00');
    }

    public function test_admin_can_send_an_invitation(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)
            ->test('admin.invitations')
            ->set('email', 'newperson@example.com')
            ->set('role', User::ROLE_MEMBER)
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invitations', [
            'email' => 'newperson@example.com',
            'invited_by' => $admin->id,
        ]);

        Mail::assertSent(InvitationMail::class);
    }

    public function test_invitation_email_renders_without_error(): void
    {
        // Mail::fake() never actually renders the Blade view, so it can't catch
        // template/namespace bugs — render it for real here.
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->for($admin, 'inviter')->create([
            'email' => 'invitee@example.com',
        ]);

        $html = (new InvitationMail($invitation))->render();

        $this->assertStringContainsString($invitation->token, $html);
    }

    public function test_cannot_invite_an_email_that_already_belongs_to_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();

        Volt::actingAs($admin)
            ->test('admin.invitations')
            ->set('email', $existing->email)
            ->set('role', User::ROLE_MEMBER)
            ->call('send')
            ->assertHasErrors(['email']);
    }

    public function test_admin_can_revoke_a_pending_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = Invitation::factory()->for($admin, 'inviter')->create();

        Volt::actingAs($admin)
            ->test('admin.invitations')
            ->call('revoke', $invitation->id);

        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
    }

    public function test_deactivated_member_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->assertGuest();
    }

    public function test_regular_members_cannot_access_settings(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/settings')->assertForbidden();
    }

    public function test_admin_can_add_a_custom_category(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)
            ->test('admin.settings')
            ->set('newExpenseCategory', 'Software Subscriptions')
            ->call('addCategory', 'expense')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'type' => 'expense',
            'name' => 'Software Subscriptions',
        ]);
    }

    public function test_duplicate_category_names_are_rejected_within_the_same_type(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['type' => 'collection', 'name' => 'Sales']);

        Volt::actingAs($admin)
            ->test('admin.settings')
            ->set('newCollectionCategory', 'Sales')
            ->call('addCategory', 'collection')
            ->assertHasErrors(['newCollectionCategory']);
    }

    public function test_admin_can_remove_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['type' => 'expense', 'name' => 'Old Category']);

        Volt::actingAs($admin)
            ->test('admin.settings')
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_dropdown_reflects_admin_defined_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['type' => 'expense', 'name' => 'Custom Fuel']);

        Volt::actingAs($user)
            ->test(FormModal::class)
            ->call('openForm', 'expense')
            ->assertSee('Custom Fuel');
    }

    public function test_admin_can_manually_create_a_member_account(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)
            ->test('admin.team-report')
            ->set('createName', 'Jane Cashier')
            ->set('createEmail', 'jane@example.com')
            ->set('createPassword', 'password123')
            ->set('createPassword_confirmation', 'password123')
            ->set('createRole', User::ROLE_MEMBER)
            ->call('createMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Cashier',
            'role' => User::ROLE_MEMBER,
        ]);

        $created = User::where('email', 'jane@example.com')->first();
        $this->assertTrue(Hash::check('password123', $created->password));
    }

    public function test_creating_a_member_requires_matching_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)
            ->test('admin.team-report')
            ->set('createName', 'Jane Cashier')
            ->set('createEmail', 'jane@example.com')
            ->set('createPassword', 'password123')
            ->set('createPassword_confirmation', 'nope')
            ->call('createMember')
            ->assertHasErrors(['createPassword']);
    }

    public function test_admin_can_edit_a_members_name_email_role_and_password(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        Volt::actingAs($admin)
            ->test('admin.team-report')
            ->call('openEdit', $member->id)
            ->set('editName', 'New Name')
            ->set('editEmail', 'new@example.com')
            ->set('editRole', User::ROLE_ADMIN)
            ->set('editIsActive', false)
            ->set('editPassword', 'newpassword123')
            ->set('editPassword_confirmation', 'newpassword123')
            ->call('updateMember')
            ->assertHasNoErrors();

        $member->refresh();
        $this->assertSame('New Name', $member->name);
        $this->assertSame('new@example.com', $member->email);
        $this->assertSame(User::ROLE_ADMIN, $member->role);
        $this->assertFalse($member->is_active);
        $this->assertTrue(Hash::check('newpassword123', $member->password));
    }

    public function test_editing_a_member_without_a_password_keeps_the_existing_one(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $originalPassword = $member->password;

        Volt::actingAs($admin)
            ->test('admin.team-report')
            ->call('openEdit', $member->id)
            ->set('editName', 'Updated Name')
            ->set('editEmail', $member->email)
            ->call('updateMember')
            ->assertHasNoErrors();

        $this->assertSame($originalPassword, $member->fresh()->password);
    }

    public function test_admin_cannot_change_their_own_role_or_active_status_via_edit(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)
            ->test('admin.team-report')
            ->call('openEdit', $admin->id)
            ->set('editName', $admin->name)
            ->set('editEmail', $admin->email)
            ->call('updateMember')
            ->assertHasNoErrors();

        $admin->refresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);
    }
}
