<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user()->load('role');
            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'status' => ['code' => 200, 'message' => 'Login successful'],
                'data'=>[
                    'token' => $token,
                    'user' => $user
                ]
            ]);
        }

        return response()->json(['status' => ['code' => 401, 'message' => 'Unauthorized']]);
    }

    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);


        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json(['status' => ['code' => 200, 'message' => 'Registration successful'], 'token' => $token]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['status'=> ['code' => 200, 'message' => 'Logged out successfully.']]);
    }

    /**
     * Refresh the token.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $token = $request->user()->createToken('token')->plainTextToken;

        return response()->json([
            'status' => ['code' => 200, 'message' => 'Token refreshed successfully.'],
            'token' => $token
        ]);
    }

    /**
     * Get the authenticated User.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data'=>['user' => $request->user()->load('role')],
            'status' => ['code'=> 200, 'message'=>'User retrieved successfully.'],
        ]);
    }

    
    /**
     * Set the password of the authenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPassword(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'password' => 'required|confirmed|min:6',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([ 'data' => ['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]]);
        }
        
        $user = User::where('password_reset_token', $data['token'])->first();
        if (!$user) {
            return response()->json([
                'data' => [
                    'status' => ['code'=> 401, 'message'=>'Invalid token.'],
                ]
            ]);
        }
        if (Carbon::now()->isAfter(Carbon::parse($user->password_reset_token_expires_at))) {
            return response()->json([
                'data' => [
                    'status' => ['code' => 400, 'message' => 'Token has expired']
                ]
            ]); 
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null 
        ]);

        return response()->json([
            'data' => [
                'status' => ['code'=> 200, 'message'=>'Password set successfully.'],
            ]
        ]);
    }


    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }

        if (!Hash::check($request->old_password, $request->user()->password)) {
            return response()->json([
                'status' => ['code'=> 401, 'message'=>'Old password does not match.'],
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'status' => ['code'=> 200, 'message'=>'Password updated successfully.'],
        ]);
    }
    

}


