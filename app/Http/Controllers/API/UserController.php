<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
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
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }
        if($user->role != 'admin') {
            return response()->json(['status' => ['code' => 403, 'message' => 'Unauthorized.']]);
        }
        $user->update($request->all());
        return response()->json(['status' => ['code' => 200, 'message' => 'User updated successfully.']]);
    }
}
