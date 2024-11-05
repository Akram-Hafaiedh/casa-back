<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request) {

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');


        $users = User::whereHas('role', function ($query){
            $query->where('name', '!=', 'Developer');
        })
        ->when($search, function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        })
        ->with(['role', 'contract'])
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
        $data = $request->all();
        foreach ($data as $key => $value) {
            if ($value === 'null') $data[$key] = null;
        }
        $userValidator = Validator::make($data, [
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
                'role_id' => Role::where('name', $data['role'])->first()->id ?? 3,
            ]);
            $userDocumentsPath = "public/user_documents/{$user->id}";
            if ($request->hasFile('documents')) {
                $copyIdPath = $request->file('documents')->store("{$userDocumentsPath}/copy_id");
                $documentName = $documentName = $request->input('document_name') ?? 'copy_id_' . now()->format('Y-m-d_H-i-s');
                $user->documents()->create([
                    'type' => 'copy_id',
                    'path' => $copyIdPath,
                    'name' => $documentName, 
                ]); 
            }
            $link = config('app.url') . '/set-password/' . $passwordResetToken;
            Mail::to($user->email)->send(new PasswordResetMail($user, $link));


            if (isset($data['type'])) {
                $contract = Contract::create([
                    'user_id' => $user->id,
                    'type' => $data['type'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status'],
                ]);

                $contractDocuments = [];
                if ($request->hasFile('contract_documents')) {
                    $contractDocumentName = $request->input('contract_document_name') ?? 'contract_document_' . now()->format('Y-m-d_H-i-s');
                    $contractDocumentsPath = $request->file('contract_documents')->store("{$userDocumentsPath}/contract");
                    $contractDocuments['contract_documents'] = $contractDocumentsPath;
                }
                if($request->hasFile('payroll_documents')) {
                    $payrollDocumentsPath = $request->file('payroll_documents')->store("{$userDocumentsPath}/payrolls");
                    $contractDocuments['payroll_documents'] = $payrollDocumentsPath;
                    $contract->documents()->create([
                        'type' => 'contract',
                        'path' => $contractDocumentsPath,
                        'name' => $contractDocumentName, // Use the provided name or default
                    ]);
                }

            }

            DB::commit();

            return response()->json([
                'status' => ['code' => 201, 'message' => 'User created successfully'. (isset($data['type']) ? ' with contract details.' : '.')],
                'data' => ['user' => $user->load('role', 'contract', 'documents')] 
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => ['code' => 500, 'message' => 'Failed to create user.', 'error' => $e->getMessage()]]);
        }
    }

    // TODO:: ADD admin middleware here



    public function show($userId) : JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }
        $user->load('role', 'contract');
        $user->documents = !is_null($user->documents) ? json_decode($user->documents) : null;
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

    public function delete($userId) : JsonResponse
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
    

    public function addPayrollDocuments(Request $request, $userId): JsonResponse {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => ['code' => 404, 'message' => 'User not found.']
            ]);
        } 
        $validator = Validator::make($request->all(), [
            'payroll_documents' => 'nullable|file',
            'document_name' => 'nullable|string|max:255', // Validate document name 
        ]);
        if ($validator->fails()){
            return response()->json([
                'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        try {
            $userDocumentsPath = "public/user_documents/{$user->id}";
            if ($request->hasFile('payroll_documents')) {
                $payrollDocumentsPath = $request->file('payroll_documents')->store("{$userDocumentsPath}/payrolls");
                $userDocument = new UserDocument([
                    'user_id' => $user->id,
                    'type' => 'payroll_documents',
                    'path' => $payrollDocumentsPath,
                    'name' => $request->input('document_name'), // Save document name
                ]);
                $userDocument->save();
            }
            return response()->json([
                'status' => ['code' => 200, 'message' => 'Payroll documents added successfully.'],
                'data' => $user->documents()->where('type', 'payroll_documents')->get() 
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => ['code' => 500, 'message' => 'Failed to add payroll documents.', 'error' => $e->getMessage()]
            ]);
        }
    }
}
