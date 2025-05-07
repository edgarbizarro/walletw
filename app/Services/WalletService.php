<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallets;
use Illuminate\Support\Str;
use App\Models\Transactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;

class WalletService
{
    public function deposit(User $user, float $amount, string $description = null): Transactions
    {
        if ($user->wallet->balance < 0) {
            throw new \Exception('Não é possível depositar em conta com saldo negativo');
        }

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
                throw new \Exception('Saldo insuficiente');
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
                'description' => "Transferido para {$sender->name}",
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
        // Verifica se a transação pode ser revertida
        if (!in_array($transaction->type, ['deposit', 'transfer'])) {
            throw new \Exception('Este tipo de transação não pode ser revertido');
        }

        if ($transaction->status !== 'completed') {
            throw new \Exception('Apenas transações completadas podem ser revertidas');
        }

        if ($transaction->status === 'reversed') {
            throw new \Exception('Esta transação já foi revertida');
        }

        return DB::transaction(function () use ($transaction) {

            $reversalAmount = $transaction->amount;

            // Cria a transação de reversão
            $reversalTransaction = $transaction->user->transactions()->create([
                'id' => Str::uuid(),
                'type' => 'reversal',
                'amount' => $reversalAmount,
                'description' => "Estorno da transação {$transaction->id}",
                'status' => 'completed',
                'related_transaction_id' => $transaction->id,
            ]);

            // Lógica de reversão baseada no tipo de transação original
            switch ($transaction->type) {
                case 'deposit':
                    // Reverte um depósito: subtrai o valor da carteira
                    $transaction->user->wallet->decrement('balance', $reversalAmount);
                    break;

                case 'transfer':
                    // Reverte uma transferência: devolve o valor ao remetente
                    $transaction->user->wallet->increment('balance', $reversalAmount);

                    // Se houver destinatário, subtrai o valor dele
                    if ($transaction->to_user_id) {
                        $recipient = User::find($transaction->to_user_id);
                        if ($recipient && $recipient->wallet) {
                            $recipient->wallet->decrement('balance', $reversalAmount);

                            // Cria registro de reversão para o destinatário
                            $recipient->transactions()->create([
                                'id' => Str::uuid(),
                                'type' => 'reversal',
                                'amount' => $reversalAmount,
                                'description' => "Estorno de transferência de {$transaction->user->name}",
                                'status' => 'completed',
                                'related_transaction_id' => $transaction->id,
                            ]);
                        }
                    }
                    break;

                default:
                    throw new \Exception('Tipo de transação não suportado para reversão');
            }

            // Atualiza o status da transação original
            $transaction->update(['status' => 'reversed']);

            return $reversalTransaction;
        });
    }

    public function reverse(Request $request, $transactionId)
    {
        $user = Auth::user();

        // Busca a transação onde o usuário é o remetente ou destinatário
        $transaction = Transactions::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('to_user_id', $user->id);
        })
            ->findOrFail($transactionId);

        try {
            $reversalTransaction = $this->walletService->reverseTransaction($transaction);

            return response()->json([
                'message' => 'Transação revertida com sucesso',
                'reversal_transaction' => $reversalTransaction,
                'new_balance' => $user->wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao reverter transação: ' . $e->getMessage()
            ], 422);
        }
    }
}
