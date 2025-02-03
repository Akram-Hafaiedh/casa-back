<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {   
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');

        $contracts = Contract::when($search, function ($query) use ($search) {
            $query->where('type', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%');
        })
        ->with(['user', 'user.documents'])
        ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'status' => ['code' => 200,'message' => 'Success'],
            'data' => [
                'contracts' => $contracts,
                'totalPages' => $contracts->lastPage(),
                'currentPage' => $contracts->currentPage()
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $contractValidator = Validator::make($data, [
            'user_id' => 'required|integer',
            'type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string|max:255',
            'contract_documents' => 'nullable|file',
            'document_name' => 'nullable|string|max:255',
        ]);

        if ($contractValidator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $contractValidator->errors()]);
        }

        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }

        $contract = Contract::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'],
        ]);

        if ($request->hasFile('contract_documents')) {
            $contractDocumentsPath = $request->file('contract_documents')->store("public/contract_documents/{$contract->id}");
            $contractDocumentName = $data['document_name']  ?? 'contract_'. now()->format('Y-m-d_H-i-s');
            $user->documents->create([
                'type' => 'contract',
                'path' => $contractDocumentsPath,
                'name' => $contractDocumentName,
            ]);
        }

        return response()->json([
            'status' => ['code' => 200, 'message' => 'Contract created successfully.'],
            'data' => [ 'user' => $user->load('role', 'contract', 'documents') ]
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(String $contractId): JsonResponse
    {   
        $contract = Contract::find($contractId);
        if (!$contract) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Contract not found.']]);
        }
        return response()->json([
            'status' => ['code' => 200, 'message' => 'Contract retrieved successfully.'],
            'data' => [ 'contract' => $contract->load('user', 'user.documents') ]
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, String $contractId) : JsonResponse
    {
        $data = $request->all();
        $contractValidator = Validator::make($data, [
            'user_id' => 'required|integer',
            'type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|string|max:255',
            'contract_document' => 'nullable|file',
            'document_name' => 'nullable|string|max:255',
        ]);

        if ($contractValidator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $contractValidator->errors()]);
        }


        $contract = Contract::find($contractId);
        if (!$contract) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Contract not found.']]);
        }

        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json(['status' => ['code' => 404, 'message' => 'User not found.']]);
        }

        $contract->update([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'],
        ]);

        if ($request->hasFile('contract_document')) {
            $userDocumentsPath = "public/user_documents/{$user->id}";
            $user->documents()->where('type', 'contract')->delete();
            $contractDocumentName = $data['document_name']  ?? 'contract_'. now()->format('Y-m-d_H-i-s');
            $contractDocumentsPath = $request->file('contract_document')->store("{$userDocumentsPath}/contract");
            $user->documents()->create([
                'type' => 'contract',
                'path' => $contractDocumentsPath,
                'name' => $contractDocumentName,
            ]);
        }

        return response()->json([
            'status' => ['code' => 200, 'message' => 'Contract updated successfully.'],
            'data' => [ 'contract' => $contract->load('user', 'user.documents', 'user.role') ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract) : JsonResponse
    {
        $contract->delete();

        return response()->json(['status' => [
            'code' => 200, 'message' => 'Contract deleted successfully.']
        ]);   
    }
    
}
