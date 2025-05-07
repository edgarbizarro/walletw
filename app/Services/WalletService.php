<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transactions;
use App\Models\Wallets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function deposit(User $user, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $transaction = $user->transactions()->create([
                'id' => Str::uuid(),
                'type' => 'deposit',
                'amount' => $amount,
                'description' => $description,
                'status' => 'completed',
            ]);

            $user->wallet->increment('balance', $amount);

            return $transaction;
        });
    }

    public function transfer(User $sender, User $recipient, float $amount, string $description = null): Transactions
    {
        return DB::transaction(function () use ($sender, $recipient, $amount, $description) {
            // Check if sender has sufficient balance
            if ($sender->wallet->balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            // Create transfer transaction for sender
            $transferTransaction = $sender->transactions()->create([
                'id' => Str::uuid(),
                'type' => 'transfer',
                'amount' => $amount,
                'description' => $description,
                'status' => 'completed',
                'to_user_id' => $recipient->id,
            ]);

            // Create deposit transaction for recipient
            $recipient->transactions()->create([
                'id' => Str::uuid(),
                'type' => 'deposit',
                'amount' => $amount,
                'description' => "Transfer from {$sender->name}",
                'status' => 'completed',
                'related_transaction_id' => $transferTransaction->id,
            ]);

            // Update balances
            $sender->wallet->decrement('balance', $amount);
            $recipient->wallet->increment('balance', $amount);

            return $transferTransaction;
        });
    }

    public function reverseTransaction(Transactions $transaction): Transactions
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->status === 'reversed') {
                throw new \Exception('Transaction already reversed');
            }

            $reversalAmount = $transaction->amount;

            // Create reversal transaction
            $reversalTransaction = $transaction->user->transactions()->create([
                'id' => Str::uuid(),
                'type' => 'reversal',
                'amount' => $reversalAmount,
                'description' => "Reversal of transaction {$transaction->id}",
                'status' => 'completed',
                'related_transaction_id' => $transaction->id,
            ]);

            // Handle different transaction types
            if ($transaction->type === 'deposit') {
                $transaction->user->wallet->decrement('balance', $reversalAmount);
            } elseif ($transaction->type === 'transfer') {
                // Return money to sender
                $transaction->user->wallet->increment('balance', $reversalAmount);

                // Deduct from recipient if exists
                if ($transaction->to_user_id) {
                    $recipient = User::find($transaction->to_user_id);
                    if ($recipient) {
                        $recipient->wallet->decrement('balance', $reversalAmount);

                        // Record the reversal for recipient
                        $recipient->transactions()->create([
                            'id' => Str::uuid(),
                            'type' => 'reversal',
                            'amount' => $reversalAmount,
                            'description' => "Reversal of transfer from {$transaction->user->name}",
                            'status' => 'completed',
                            'related_transaction_id' => $transaction->id,
                        ]);
                    }
                }
            }

            // Mark original transaction as reversed
            $transaction->update(['status' => 'reversed']);

            return $reversalTransaction;
        });
    }
}
