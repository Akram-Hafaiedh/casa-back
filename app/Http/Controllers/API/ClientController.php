<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        ->with('')
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
    public function create(Request $request): JsonResponse
    {
        $data = $request->all();

        $clientValidator = Validator::make($data, [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            // 'company_name' => 'nullable|string|max:100',
            'birthday' => 'required|date',
            'gender' => 'nullable|string|max:10',
            'phone' => 'required|string|max:255|unique:clients',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'city' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:clients',
            'id_passport' => 'required|string|max:50|unique:clients',

            'portfolio.insurances' => 'required|array',
            'portfolio.insurances.*.type' => 'required|string|max:255',
            'portfolio.insurances.*.policy_number' => 'required|string|max:255',
            'portfolio.insurances.*.agency' => 'required|string|max:255',
            'portfolio.insurances.*.inception_date' => 'required|date',
            'portfolio.insurances.*.expiration_date' => 'nullable|date',
            'portfolio.insurances.*.status' => 'required|string|max:255',
            'portfolio.insurances.*.cancellation_period' => 'required|numeric',
            'portfolio.insurances.*.payment_amount' => 'required|decimal:2',
            'portfolio.insurances.*.payment_frequency' => 'required|string|max:255',
            'portfolio.insurances.*.documents' => 'nullable|file',

            'portfolio.accountings' => 'required|array',
            'portfolio.accountings.*.type' => 'required|string|max:255',
            'portfolio.accountings.*.start_date' => 'required|date',
            'portfolio.accountings.*.end_date' => 'nullable|date',
            'portfolio.accountings.*.status' => 'required|string|max:255',
            'portfolio.accountings.*.documents' => 'nullable|file',

            'portfolio.tax' => 'required|array',
            'portfolio.tax.*.name' => 'required|string|max:255',
            'portfolio.tax.*.percentage' => 'required|decimal:2',
            'portfolio.tax.*.type' => 'required|string|max:255',
            'portfolio.tax.*.documents' => 'nullable|file',
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
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                // 'company_name' => $data['company_name']?? null,
                'email' => $data['email'],
                'birthday' => $data['birthday'],
                'gender' => $data['gender']?? null,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'id_passport' => $data['id_passport'],
                'postal_code' => $data['postal_code'],
            ]);

            $portfolio = $client->portfolio()->create([]);
            $clientDocumentsPath = "public/clients/{$user->id}";

            foreach ($data['portfolio']['insurances'] as $insurance) {
                $insuranceInstance =$portfolio->insurances()->create([
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

                if ($request->hasFile('portfolio.insurances.documents')) {
                    $insuranceDocuments = [];
                    foreach ($insurance['documents'] as $document) {
                        $documentPath = $document->store("{$clientDocumentsPath}/insurances/{$portfolio->id}");
                        $insuranceDocuments[] = $documentPath;
                    }
                    $insuranceInstance->documents = $insuranceDocuments;
                    $insuranceInstance->save();
                }
            }
            foreach ($data['portfolio']['accountings'] as $accounting) {
                $accountingInstance = $portfolio->accountings()->create([
                    'type' => $accounting['type'],
                    'start_date' => $accounting['start_date'],
                    'end_date' => $accounting['end_date']?? null,
                    'status' => $accounting['status'],
                ]);
                if ($request->hasFile('portfolio.accountings.documents')) {
                    $accountingDocuments = [];
                    foreach ($accounting['documents'] as $document) {
                        $documentPath = $document->store("{$clientDocumentsPath}/accountings/{$portfolio->id}");
                        $accountingDocuments[] = $documentPath;
                    }
                    $accountingInstance->documents = $accountingDocuments;
                    $accountingInstance->save();
                }
            }
            foreach ($data['portfolio']['tax'] as $tax) {
                $taxInstance = $portfolio->tax()->create([
                    'name' => $tax['name'],
                    'percentage' => $tax['percentage'],
                    'type' => $tax['type'],
                ]);
                if ($request->hasFile('portfolio.tax.documents')) {
                    $taxDocuments = [];
                    foreach ($tax['documents'] as $document) {
                        $documentPath = $document->store("{$clientDocumentsPath}/tax/{$portfolio->id}");
                        $taxDocuments[] = $documentPath;
                    }
                    $taxInstance->documents = $taxDocuments;
                    $taxInstance->save();
                }
            }
            DB::commit();

            return response()->json([
                'status' => ['code' => 201, 'message' => 'Client created successfully with their portfolio.'],
                'data' => ['client' => $client->load('portfolio.insurances', 'portfolio.accountings', 'portfolio.taxes')]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => ['code' => 500, 'message' => 'Something went wrong.', 'errors' => $e->getMessage()],
            ]);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        //
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
}
