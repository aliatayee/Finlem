<?php

namespace Tests\Feature;

use App\Livewire\Transactions\FormModal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_member_can_record_a_cash_collection(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FormModal::class)
            ->set('type', Transaction::TYPE_COLLECTION)
            ->set('amount', '150.50')
            ->set('category', 'Sales')
            ->set('occurred_on', now()->toDateString())
            ->call('save');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'collection',
            'amount' => 150.50,
            'category' => 'Sales',
        ]);

        $this->assertEquals(150.50, $user->fresh()->balance());
    }

    public function test_member_can_record_a_cash_expense(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FormModal::class)
            ->set('type', Transaction::TYPE_EXPENSE)
            ->set('amount', '40')
            ->set('category', 'Transport')
            ->set('occurred_on', now()->toDateString())
            ->call('save');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 40,
        ]);

        $this->assertEquals(-40, $user->fresh()->balance());
    }

    public function test_amount_must_be_positive(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FormModal::class)
            ->set('type', Transaction::TYPE_COLLECTION)
            ->set('amount', '0')
            ->set('occurred_on', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['amount']);
    }

    public function test_member_can_only_edit_their_own_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = Transaction::factory()->for($owner)->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)
            ->test(FormModal::class)
            ->call('openForm', 'collection', $transaction->id);
    }

    public function test_member_sees_only_their_own_transactions_on_the_index(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Transaction::factory()->for($user)->create(['type' => 'collection', 'amount' => 100]);
        Transaction::factory()->for($other)->create(['type' => 'collection', 'amount' => 999]);

        Volt::actingAs($user)
            ->test('transactions.index')
            ->assertSee('100.00')
            ->assertDontSee('999.00');
    }
}
