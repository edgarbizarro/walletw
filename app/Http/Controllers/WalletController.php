<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Wallet Management
 *
 * Endpoints for managing user wallet operations
 *
 * @authenticated
 */
class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Deposit funds
     *
     * Add money to the user's wallet
     *
     * @bodyParam amount number required The amount to deposit (min: 0.01). Example: 100.50
     * @bodyParam description string optional Description for the deposit. Example: Paycheck
     *
     * @response 200 {
     *   "message": "Deposit successful",
     *   "transaction": {
     *     "id": "550e8400-e29b-41d4-a716-446655440000",
     *     "type": "deposit",
     *     "amount": 100.50,
     *     "status": "completed"
     *   },
     *   "new_balance": 100.50
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "amount": ["The amount must be at least 0.01."]
     *   }
     * }
     */
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
            'message' => 'Deposito realizado com sucesso',
            'transaction' => $transaction,
            'new_balance' => $user->wallet->fresh()->balance,
        ]);
    }

    /**
     * Transfer funds
     *
     * Transfer money to another user
     *
     * @bodyParam amount number required The amount to transfer (min: 0.01). Example: 50.25
     * @bodyParam recipient_id integer required The ID of the recipient user. Example: 2
     * @bodyParam description string optional Description for the transfer. Example: Lunch money
     *
     * @response 200 {
     *   "message": "Transfer successful",
     *   "transaction": {
     *     "id": "550e8400-e29b-41d4-a716-446655440000",
     *     "type": "transfer",
     *     "amount": 50.25,
     *     "status": "completed"
     *   },
     *   "new_balance": 50.25
     * }
     * @response 422 {
     *   "message": "Insufficient balance"
     * }
     */
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
            return response()->json(['message' => 'Não é permitido transferir para si mesmo'], 422);
        }

        try {
            $transaction = $this->walletService->transfer(
                $sender,
                $recipient,
                $request->amount,
                $request->description
            );

            return response()->json([
                'message' => 'Transferencia realizada com sucesso',
                'transaction' => $transaction,
                'new_balance' => $sender->wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reverse transaction
     *
     * Reverse a previously completed transaction
     *
     * @urlParam transaction string required The UUID of the transaction to reverse. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "message": "Transaction reversed successfully",
     *   "reversal_transaction": {
     *     "id": "550e8400-e29b-41d4-a716-446655440000",
     *     "type": "reversal",
     *     "amount": 50.25,
     *     "status": "completed"
     *   },
     *   "new_balance": 100.50
     * }
     * @response 404 {
     *   "message": "Transaction not found"
     * }
     * @response 422 {
     *   "message": "Transaction already reversed"
     * }
     */
    public function reverse(Request $request, $transactionId)
    {
        $user = Auth::user();
        
        // Busca a transação onde o usuário é o remetente OU o destinatário
        $transaction = Transactions::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->findOrFail($transactionId);

        try {
            $reversalTransaction = $this->walletService->reverseTransaction($transaction);

            return response()->json([
                'message' => 'Transação estornada com sucesso',
                'reversal_transaction' => $reversalTransaction,
                'new_balance' => $user->wallet->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get balance
     *
     * Retrieve the current wallet balance
     *
     * @response 200 {
     *   "balance": 100.50
     * }
     */
    public function balance()
    {
        return response()->json([
            'balance' => auth()->user()->wallet->balance
        ]);
    }

    /**
     * List transactions
     *
     * Get paginated list of user's transactions
     *
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "type": "deposit",
     *       "amount": 100.50,
     *       "description": "Paycheck",
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/wallet/transactions?page=1",
     *     "last": "http://localhost/api/wallet/transactions?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/wallet/transactions",
     *     "per_page": 10,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function transactions()
    {
        $user = Auth::user();
        $transactions = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($transactions);
    }
}
