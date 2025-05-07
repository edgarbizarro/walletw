<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $transaction = $this->walletService->deposit(
            $user,
            $request->amount,
            $request->description
        );

        return response()->json([
            'message' => 'Deposit successful',
            'transaction' => $transaction,
            'new_balance' => $user->wallet->fresh()->balance,
        ]);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'recipient_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:255',
        ]);

        $sender = Auth::user();
        $recipient = User::findOrFail($request->recipient_id);

        if ($sender->id === $recipient->id) {
            return response()->json(['message' => 'Cannot transfer to yourself'], 422);
        }

        try {
            $transaction = $this->walletService->transfer(
                $sender,
                $recipient,
                $request->amount,
                $request->description
            );

            return response()->json([
                'message' => 'Transfer successful',
                'transaction' => $transaction,
                'new_balance' => $sender->wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reverse(Request $request, $transactionId)
    {
        $user = Auth::user();
        $transaction = Transactions::where('user_id', $user->id)
            ->orWhere('to_user_id', $user->id)
            ->findOrFail($transactionId);

        try {
            $reversalTransaction = $this->walletService->reverseTransaction($transaction);

            return response()->json([
                'message' => 'Transaction reversed successfully',
                'reversal_transaction' => $reversalTransaction,
                'new_balance' => $user->wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function balance()
    {
        $user = Auth::user();
        return response()->json([
            'balance' => $user->wallet->balance,
        ]);
    }

    public function transactions()
    {
        $user = Auth::user();
        $transactions = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($transactions);
    }
}
