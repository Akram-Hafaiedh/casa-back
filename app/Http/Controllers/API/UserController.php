<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index() {
        $users = User::whereHas('role', function ($query){
            $query->where('name', '!=', 'Developer');
        })->with('role')->get();
        return response()->json([
            'data' => ['users' => $users],
            'status' => ['code' => 200, 'message' => 'Users retrieved successfully.']
        ]);
    }

    // TODO:: ADD admin middleware here
    public function create(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|exists:roles,name',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => Role::where('name', $data['role'])->first()->id ?? 3,
            'password' => Hash::make(Str::random(12)),
            'password_reset_token' => Str::random(32),
        ]);

        $token = $user->createToken('password_reset_token')->plainTextToken;
        

        $link = config('app.url') . '/reset-password?token=' . $token;
        
        Mail::to($user->email)->send(new PasswordResetMail($user, $link));

        return response()->json([
            'status' => ['code' => 201, 'message' => 'User created successfully.'],
            'data' => ['user' => $user->load('role')] 
        ]);
    }

    public function show($userId) {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }
        return response()->json(['status' => ['code' => 200, 'message' => 'User retrieved successfully.'], 'user' => $user]);
    }
    public function delete($userId) {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }
        $user->delete();
        return response()->json(['status' => ['code' => 200, 'message' => 'User deleted successfully.']]);
    }

    public function update(Request $request, $userId) {
        $user = User::find($userId);
        $data = $request->all();
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'role_id' => Role::where('name', $data['role'])->first()->id ?? $user->role_id,
        ]);
        return response()->json([
            'status' => ['code' => 200, 'message' => 'User updated successfully.'],
        ]);
    }
}
