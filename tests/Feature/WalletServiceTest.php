<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
    }

    public function test_deposit_increases_balance()
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 100]);

        $transaction = $this->walletService->deposit($user, 50, 'Teste deposito');

        $this->assertEquals(150, $user->wallet->fresh()->balance);
        $this->assertEquals('deposit', $transaction->type);
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_transfer_updates_both_accounts()
    {
        $sender = User::factory()->create();
        $sender->wallet()->create(['balance' => 100]);

        $recipient = User::factory()->create();
        $recipient->wallet()->create(['balance' => 50]);

        $transaction = $this->walletService->transfer($sender, $recipient, 30, 'Teste transferencia');

        $this->assertEquals(70, $sender->wallet->fresh()->balance);
        $this->assertEquals(80, $recipient->wallet->fresh()->balance);
        $this->assertEquals('transfer', $transaction->type);
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_transfer_fails_with_insufficient_balance()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $sender = User::factory()->create();
        $sender->wallet()->create(['balance' => 10]);

        $recipient = User::factory()->create();
        $recipient->wallet()->create(['balance' => 0]);

        $this->walletService->transfer($sender, $recipient, 30, 'Teste transferencia');
    }

    public function test_reversal_undoes_deposit()
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 100]);

        $deposit = $this->walletService->deposit($user, 50, 'Test deposit');
        $this->assertEquals(150, $user->wallet->fresh()->balance);

        // Garante que o depósito está como 'completed'
        $deposit->refresh();
        $this->assertEquals('completed', $deposit->status);

        $reversal = $this->walletService->reverseTransaction($deposit);
        
        $this->assertEquals(100, $user->wallet->fresh()->balance);
        $this->assertEquals('reversed', $deposit->fresh()->status);
        $this->assertEquals('reversal', $reversal->type);
    }

    public function test_reversal_undoes_transfer()
    {
        $sender = User::factory()->create();
        $sender->wallet()->create(['balance' => 100]);
        
        $recipient = User::factory()->create();
        $recipient->wallet()->create(['balance' => 50]);

        $transfer = $this->walletService->transfer($sender, $recipient, 30, 'Test transfer');
        $this->assertEquals(70, $sender->wallet->fresh()->balance);
        $this->assertEquals(80, $recipient->wallet->fresh()->balance);

        // Garante que a transferência está como 'completed'
        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);

        $reversal = $this->walletService->reverseTransaction($transfer);
        
        $this->assertEquals(100, $sender->wallet->fresh()->balance);
        $this->assertEquals(50, $recipient->wallet->fresh()->balance);
        $this->assertEquals('reversed', $transfer->fresh()->status);
        $this->assertEquals('reversal', $reversal->type);
    }
}
