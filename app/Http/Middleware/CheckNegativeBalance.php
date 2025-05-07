<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckNegativeBalance
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->wallet && $user->wallet->balance < 0) {
            return response()->json([
                'message' => 'Conta bloqueada devido a saldo negativo',
                'balance' => $user->wallet->balance,
            ], 403);
        }

        return $next($request);
    }
}
