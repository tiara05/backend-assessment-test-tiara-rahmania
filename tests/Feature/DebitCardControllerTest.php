<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Laravel\Passport\Passport;


class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Passport::actingAs($this->user);
    }

    /** @test */
    public function testCustomerCanSeeAListOfDebitCards()
    {
        $activeCard = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => null
        ]);

        $inactiveCard = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => now()
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/debit-cards');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $activeCard->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->actingAs($this->user)->postJson('/api/debit-cards', [
            'type' => 'visa'
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'visa'
        ]);
    }

    /** @test */
    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->getJson("/api/debit-cards/{$card->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $card->id,
            'type' => $card->type
        ]);
    }

    /** @test */
    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherCard = DebitCard::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/debit-cards/{$otherCard->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => null
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => false
        ]);

        $response->assertOk();
        $this->assertNotNull($card->fresh()->disabled_at);
    }

    /** @test */
    public function testCustomerCanDeactivateADebitCard()
    {
        $card = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => now()
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/debit-cards/{$card->id}", [
            'is_active' => true
        ]);

        $response->assertOk();
        $this->assertNull($card->fresh()->disabled_at);
    }

    /** @test */
    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $otherCard = DebitCard::factory()->create();

        $response = $this->actingAs($this->user)->putJson("/api/debit-cards/{$otherCard->id}", [
            'is_active' => false
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/debit-cards/{$card->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('debit_cards', [
            'id' => $card->id
        ]);
    }

    /** @test */
    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $otherCard = DebitCard::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/debit-cards/{$otherCard->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function testActiveFieldShouldBeBasedonDisabledat()
    {
        $activeCard = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => null
        ]);

        $inactiveCard = DebitCard::factory()->for($this->user)->create([
            'disabled_at' => now()
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/debit-cards');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $activeCard->id,
            'is_active' => true
        ]);
        $response->assertJsonMissing([
            'id' => $inactiveCard->id
        ]);
    }
}
