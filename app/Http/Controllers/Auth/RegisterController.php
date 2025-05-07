<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Wallets;

/**
 * @group Authentication
 *
 * Endpoints for user registration and authentication
 */
class RegisterController extends Controller
{
    /**
     * Register a new user
     *
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam email string required The email of the user. Example: john@example.com
     * @bodyParam password string required The password (min: 8 characters). Example: secret123
     * @bodyParam password_confirmation string required The password confirmation. Example: secret123
     * @bodyParam document string required The user's document (CPF/CNPJ). Example: 12345678901
     * @bodyParam type string required The user type (individual or business). Example: individual
     *
     * @response 200 {
     *   "access_token": "token_value",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "document": "12345678901",
     *     "type": "individual"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'document' => 'required|string|unique:users',
            'type' => 'required|in:individual,business',
        ]);

        if ($validator->fails()) {
            return response()->api($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'document' => $request->document,
            'type' => $request->type,
        ]);

        // Create a wallet for the user
        $user->wallet()->create(['balance' => 0]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->api([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->toArray()
        ]);
    }
}
