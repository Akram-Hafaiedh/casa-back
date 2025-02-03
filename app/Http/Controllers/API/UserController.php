<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetEmail;
use App\Mail\PasswordResetMail;
use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use AuthorizesRequests;
    public function employeesWithoutContract(Request $request) {
        $employeesWithoutContracts = User::whereHas('role', function ($query) {
            $query->where('name', 'Employee');
        })
        ->doesntHave('contract')
        ->with('role')
        ->get();
    
        return response()->json([
            'data' => ['employees' => $employeesWithoutContracts],
            'status' => ['code' => 200, 'message' => 'Employees without contracts retrieved successfully.']
        ]);
    }
    
    public function index(Request $request) {

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');


        $users = User::whereHas('role', function ($query){
            $query->where('name', '!=', 'Developer');
        })
        ->when($search, function ($query) use ($search) {
            $query->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        })
        ->with(['role'])
        ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'users' => $users,
                'totalPages' => $users->lastPage(),
                'currentPage' => $users->currentPage()
            ],
            'status' => ['code' => 200, 'message' => 'Users retrieved successfully.']
        ]);
    }
    public function store(Request $request): JsonResponse
    {

        try {
            $this->authorize('create', User::class);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => ['code' => 403, 'message' => 'You are not authorized to create vacations.']
            ], 403);
        }

        $data = $request->all();

        foreach ($data as $key => $value) {
            if ($value === 'null') $data[$key] = null;
        }
        $userValidator = Validator::make($data, [
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'birthday' => 'required|date',
            'id_passport' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'ahv_number' => 'required|string|max:20',
            'phone' => 'required|string|max:255',
            'documents' => 'nullable|file',
            'role' => 'required|string|exists:roles,name',
            'document_name' => 'nullable|string|max:255', // Document name for copy_id
        ]);
        $contractValidator = null;
        if(isset($data['type'])){
            $contractValidator = Validator::make($data,[
                'type' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'status' => 'required|string|max:255',
                'contract_documents' => 'nullable|file',
            ]);
        }
        
        if ($userValidator->fails() || ($contractValidator && $contractValidator->fails())) {
            $errors = array_merge(
                $userValidator->errors()->toArray(),
                $contractValidator ? $contractValidator->errors()->toArray() : []
            );
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $errors]);
        }

        DB::beginTransaction();
        try {
            $passwordResetToken = Str::random(32);
            $passwordResetTokenExpiresAt = Carbon::now()->addHours(2); // Token expires in 2 hours

            $role = Role::where('name', $data['role'])->first();
            $defaultRole = Role::where('name', 'Employee')->first();

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'birthday' => $data['birthday'],
                'id_passport' => $data['id_passport'],
                'address' => $data['address'],
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'ahv_number' => $data['ahv_number'],
                'phone' => $data['phone'],
                'password' => Hash::make(Str::random(12)),
                'password_reset_token' => $passwordResetToken,
                'password_reset_token_expires_at' => $passwordResetTokenExpiresAt,
                'role_id' => $role ? $role->id : $defaultRole->id,
            ]);

            $userDocumentsPath = "user_documents/{$user->id}-{$user->first_name}-{$user->last_name}";
            if ($request->hasFile('documents')) {
                $request->file('documents')->storeAs("public/{$userDocumentsPath}/copy_id", $request->input('document_name') ?? 'copy_id_' . now()->format('Y-m-d_H-i-s') . '.' . $request->file('documents')->getClientOriginalExtension());
            }

            if($request->hasFile('logo')) {
                $logoName = $request->file('logo')->getClientOriginalName();
                $request->file('logo')->storeAs("public/{$userDocumentsPath}/logo", $logoName);
                $user->logo = "storage/{$userDocumentsPath}/logo/{$logoName}";
                $user->save();
            }

            if (isset($data['type'])) {
                $contract = Contract::create([
                    'user_id' => $user->id,
                    'type' => $data['type'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status'],
                ]);
                if ($request->hasFile('contract_documents')) {
                    $contractName = $request->file('contract_documents')->getClientOriginalName();
                   $request->file('contract_documents')->storeAs("public/{$userDocumentsPath}/contract", $request->input('contract_document_name') ?? 'contract_' . now()->format('Y-m-d_H-i-s') . '.' . $request->input('contract_document_name') . $contractName);
                }
            }

            SendPasswordResetEmail::dispatch($user, config('app.url') . '/set-password/' . $passwordResetToken);
            
            
            DB::commit();

            return response()->json([
                'status' => ['code' => 201, 'message' => 'User created successfully'. (isset($data['type']) ? ' with contract details.' : '.')],
                'data' => ['user' => $user->load('role', 'contract')] 
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json(['status' => ['code' => 500, 'message' => 'Failed to create user.', 'error' => $e->getMessage()]]);
        }
    }


    public function show($userId) : JsonResponse
    {
        $user = User::with('contract')->find($userId);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }
        $user->load('role', 'contract', 'projects', 'projects.users');
        // TODO: Add the user documents here.
        return response()->json([
            'status' => ['code' => 200, 'message' => 'User retrieved successfully.'],
            'data'=> ['user' => $user]
        ]);
    }



    public function update(Request $request, $userId) {
        $user = User::find($userId);
        $data = $request->all();
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }

        $validator = Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
            'birthday' => 'required|date',
            'id_passport' => 'required|string|max:50',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'ahv_number' => 'required|string|max:20',
            'phone' => 'required|string|max:255',
            'documents' => 'nullable|file',
            'role' => 'required|string|exists:roles,name',
            'document_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }
        
        $user->update([
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'email' => $data['email'] ?? $user->email,
            'birthday' => $data['birthday'] ?? $user->birthday,
            'id_passport' => $data['id_passport'] ?? $user->id_passport,
            'address' => $data['address'] ?? $user->address,
            'city' => $data['city'] ?? $user->city,
            'postal_code' => $data['postal_code'] ?? $user->postal_code,
            'ahv_number' => $data['ahv_number'] ?? $user->ahv_number,
            'phone' => $data['phone'] ?? $user->phone,
            'role_id' => Role::where('name', $data['role'])->first()->id ?? $user->role_id,
        ]);

        $userDocumentsPath = "public/user_documents/{$user->id}-{$user->first_name}-{$user->last_name}";

        if ($request->hasFile('documents')) {
            Storage::deleteDirectory("{$userDocumentsPath}/copy_id"); // Delete old documents directly from storage
            $request->file('documents')->storeAs("{$userDocumentsPath}/copy_id", $request->input('document_name') ?? 'copy_id_' . now()->format('Y-m-d_H-i-s'));
        }
        if($request->hasFile('logo')) {
            $request->file('logo')->storeAs("{$userDocumentsPath}/logo", $request->file('logo')->getClientOriginalName());
            $user->logo = "user_documents/{$user->id}-{$user->first_name}-{$user->last_name}/logo/" . $request->file('logo')->getClientOriginalName();
            $user->save();
        }
        
        return response()->json([
            'status' => ['code' => 200, 'message' => 'User updated successfully.'],
        ]);
    }

    public function delete(string $userId) : JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }
        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();
            return response()->json(['status' => ['code' => 200, 'message' => 'User deleted successfully.']]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => ['code' => 500, 'message' => 'Failed to delete user.', 'error' => $e->getMessage()]]);
        }
    }
    

    public function addPayrollDocuments(Request $request, string $userId): JsonResponse {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => ['code' => 404, 'message' => 'User not found.']
            ]);
        } 
        $validator = Validator::make($request->all(), [
            'payroll_documents' => 'required|file|max:2048',
            'document_name' => 'nullable|string|max:255', // Validate document name 
        ]);
        if ($validator->fails()){
            return response()->json([
                'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        try {
            $userDocumentsPath = "public/user_documents/{$user->id}-{$user->first_name}-{$user->last_name}";
            if ($request->hasFile('payroll_documents')) {
                $request->file('payroll_documents')->store("{$userDocumentsPath}/payrolls");
            }
            return response()->json([
                'status' => ['code' => 200, 'message' => 'Payroll documents added successfully.'],
                // 'data' => $user->documents()->where('type', 'payroll_documents')->get() 
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => ['code' => 500, 'message' => 'Failed to add payroll documents.', 'error' => $e->getMessage()]
            ]);
        }
    }
}
