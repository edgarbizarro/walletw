<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_deposit()
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 100]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/wallet/deposit', [
            'amount' => 50,
            'description' => 'Test deposit'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Deposito realizado com sucesso',
                'new_balance' => 150,
            ]);
    }

    public function test_user_can_transfer()
    {
        $sender = User::factory()->create();
        $sender->wallet()->create(['balance' => 100]);
        Sanctum::actingAs($sender);

        $recipient = User::factory()->create();
        $recipient->wallet()->create(['balance' => 50]);

        $response = $this->postJson('/api/wallet/transfer', [
            'amount' => 30,
            'recipient_id' => $recipient->id,
            'description' => 'Teste transferencia'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transferencia realizada com sucesso',
                'new_balance' => 70,
            ]);

        $this->assertEquals(80, $recipient->wallet->fresh()->balance);
    }

    public function test_user_cannot_transfer_with_insufficient_balance()
    {
        $sender = User::factory()->create();
        $sender->wallet()->create(['balance' => 10]);
        Sanctum::actingAs($sender);

        $recipient = User::factory()->create();

        $response = $this->postJson('/api/wallet/transfer', [
            'amount' => 30,
            'recipient_id' => $recipient->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Saldo insuficiente',
            ]);
    }

    public function test_user_can_reverse_transaction()
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 100]);
        Sanctum::actingAs($user);

        $deposit = $this->postJson('/api/wallet/deposit', [
            'amount' => 50,
            'description' => 'Teste deposito'
        ]);

        $transactionId = $deposit->json('transaction.id');

        $response = $this->postJson("/api/wallet/reverse/{$transactionId}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transacao estornada com sucesso',
                'new_balance' => 100,
            ]);
    }
}
