<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => 'VND',
        ]);

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherCard->id,
            'currency_code' => 'THB',
        ]);

        $response = $this->getJson('/api/debit-card-transactions');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100.00,
            'currency_code' => 'THB', // sesuai validasi
            'description' => 'Test Transaction',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100.00,
            'currency_code' => 'THB',
            'description' => 'Test Transaction',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/debit-card-transactions', [
            'debit_card_id' => $otherCard->id,
            'amount' => 200.00,
            'currency_code' => 'THB',
            'description' => 'Invalid Transaction',
        ]);

        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => 'THB',
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transaction->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $transaction->id,
                'debit_card_id' => $this->debitCard->id,
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $otherTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherCard->id,
            'currency_code' => 'THB',
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$otherTransaction->id}");

        $response->assertForbidden();
    }
}
