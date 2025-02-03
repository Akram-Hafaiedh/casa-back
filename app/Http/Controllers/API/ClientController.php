<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\ClientDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\DocumentHelper;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');


        $clients = Client::when($search, function ($query) use ($search) {
            $query->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('id_passport', 'like', '%' . $search . '%')
                ->orWhere('birthday', 'like', '%' . $search. '%')
                ->orWhere('phone', 'like', '%' . $search. '%');
        })
        ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'clients' => $clients,
                'totalPages' => $clients->lastPage(),
                'currentPage' => $clients->currentPage()
            ],
            'status' => ['code' => 200, 'message' => 'Clients retrieved successfully.']
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        // dd($data['accountings'], $data['insurances']);

        $user = Auth::user();
        
        $clientValidator = Validator::make($data, [
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            // 'company_name' => 'nullable|string|max:100',
            'birthday' => 'required|date',
            'gender' => 'nullable|string|max:10',
            'phone' => 'required|string|max:255|unique:clients',
            'address' => 'required|string|max:255',
            'postalCode' => 'required|numeric',
            'city' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:clients',
            'idPassport' => 'required|string|max:50|unique:clients,id_passport',

            'insurances' => 'required|array',
            'insurances.*.type' => 'required|string|max:255',
            'insurances.*.policy_number' => 'required|string|max:255',
            'insurances.*.agency' => 'required|string|max:255',
            'insurances.*.inception_date' => 'required|date',
            'insurances.*.expiration_date' => 'nullable|date',
            'insurances.*.status' => 'required|string|max:255',
            'insurances.*.cancellation_period' => 'required|numeric',
            'insurances.*.payment_amount' => 'required|numeric',
            'insurances.*.payment_frequency' => 'required|string|max:255',

            'accountings' => 'required|array',
            // 'portfolio.accountings.*.type' => 'required|string|max:255', // this should
            'accountings.*.start_date' => 'required|date',
            'accountings.*.end_date' => 'nullable|date',
            'accountings.*.tax_included' => 'required|in:0,1',
            'accountings.*.status' => 'required|string|max:255',
            'accountings.*.documents' => 'nullable|array',
            'accountings.*.documents.*' => 'file|mimes:pdf,jpg,png',

            'taxes' => 'required|array',
            'taxes.*.name' => 'required|string|max:255',
            'taxes.*.type' => 'required|string|max:255',
            'taxes.*.value' => 'required|string|max:255',
            'taxes.*.documents' => 'nullable|array',
            'taxes.*.documents.*' => 'file|mimes:pdf,jpg,png',
        ]);

        if ($clientValidator->fails()) {
            return response()->json([
                'status' => ['code' => 422, 'message' => 'Client validation failed.'],
                'errors' => $clientValidator->errors(),
            ]);
        }

        DB::beginTransaction();
        try {

            $client = Client::create([
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                // 'company_name' => $data['company_name']?? null,
                'email' => $data['email'],
                'birthday' => $data['birthday'],
                'gender' => $data['gender']?? null,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'id_passport' => $data['idPassport'],
                'postal_code' => $data['postalCode'],
            ]);

            $clientDocumentsPath = "public/clients/{$user->id}";

            foreach ($data['insurances'] as $insurance) {
                $client->insurances()->create([
                    'type' => $insurance['type'],
                    'agency' => $insurance['agency'],
                    'policy_number' => $insurance['policy_number'],
                    'inception_date' => $insurance['inception_date'],
                    'expiration_date' => $insurance['expiration_date']?? null,
                    'status' => $insurance['status'],
                    'cancellation_period' => $insurance['cancellation_period'],
                    'payment_amount' => $insurance['payment_amount'],
                    'payment_frequency' => $insurance['payment_frequency'],
                ]);
            }
            foreach ($data['accountings'] as $accounting) {
                $accountingInstance = $client->accountings()->create([
                    'status' => $accounting['status'],
                    'contract_start_date' => $accounting['start_date'],
                    'end_date' => $accounting['end_date']?? null,
                    'tax_included' => $accounting['tax_included'] ?? 0,
                ]);
                if (isset($accounting['documents'])) {
                    DocumentHelper::saveDocuments(
                        $accounting['documents'], // Uploaded files
                        "{$clientDocumentsPath}/accountings/{$user->id}", // Path
                        'accounting', // Document type
                        $accountingInstance, // Associated model
                        $client->id // Client ID
                    );
                }
            }
            foreach ($data['taxes'] as $tax) {
                $taxInstance = $client->taxes()->create([
                    'name' => $tax['name'],
                    'value' => $tax['value'],
                    'type' => $tax['type'],
                ]);
                if (isset($tax['documents'])) {
                    DocumentHelper::saveDocuments(
                        $tax['documents'], // Uploaded files
                        "{$clientDocumentsPath}/taxes/{$user->id}", // Path
                        'tax', // Document type
                        $taxInstance, // Associated model
                        $client->id // Client ID
                    );
                }
            }
            DB::commit();

            return response()->json([
                'status' => ['code' => 201, 'message' => 'Client created successfully.'],
                'data' => ['client' => $client->load('insurances', 'accountings', 'taxes')]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => ['code' => 500, 'message' => 'Something went wrong.', 'errors' => $e->__toString()],
            ]);
        }

    }


    /**
     * Display the specified resource.
     */
    public function show(String $clientId): JsonResponse
    {
        $client = Client::with('insurances', 'accountings', 'taxes', 'documents')->find($clientId);

        if (!$client) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'Client not found.'
                ]
            ]);
        }

        return response()->json([
            'status' => [
                'code' => 200,
                'message' => 'Client retrieved successfully.'
            ],
            'data' => [
                'customer' => $client
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }

    public function deleteClientTax(String $clientId, String $taxId): JsonResponse
    {
        $client = Client::find($clientId);
        if (!$client) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'Client not found.'
                ]
            ]);
        }
        $tax = $client->taxes()->find($taxId);
        if (!$tax) {
            return response()->json([
                'status' => [
                    'code' => 404,
                    'message' => 'Tax not found.'
                ]
            ]);
        }
        $tax->delete();
        return response()->json([
            'status' => [
                'code' => 200,
                'message' => 'Tax deleted successfully.'
            ]
        ]);
    }
}
