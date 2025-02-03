<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vacation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VacationController extends Controller
{
    use AuthorizesRequests;

    public function index() : JsonResponse
    {
        $this->authorize('viewAny', Vacation::class);        
        
        $vacations = Vacation::with('user')->get();

        return response()->json([
            'data' => ['vacations' => $vacations],
            'status' => ['code' => 200, 'message' => 'Vacations retrieved successfully.']
        ]);
    }


    public function myVacations() : JsonResponse
    {
        try {
            $this->authorize('view', Vacation::class);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => ['code' => 403, 'message' => 'You are not authorized to access other people vacations.']
            ], 403);
        }

        $vacations = Vacation::where('user_id', auth()->id)->get();

        return response()->json([
            'data' => ['vacations' => $vacations],
            'status' => ['code' => 200, 'message' => 'Vacations retrieved successfully.']
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) : JsonResponse
    {

        try {
            $this->authorize('create', Vacation::class);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => ['code' => 403, 'message' => 'You are not authorized to create vacations.']
            ], 403);
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'title' =>'required|string|max:255',
            'description' =>'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }

        $vacation = Vacation::create([
            'user_id' => auth()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'status' => 'Pending',
            'start' => $data['start'],
            'end' => $data['end'],
        ]);

        return response()->json([
            'status' => ['code' => 201, 'message' => 'Vacation stored successfully.'],
            'data' => ['vacation' => $vacation]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vacation $vacation)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'title' =>'required|string|max:255',
            'comment' =>'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => ['code' => 422, 'message' => 'Validation failed'], 'errors' => $validator->errors()]);
        }

        $vacation->update([
            'title' => $data['title'],
            'comment' => $data['comment'] ?? '',
            'status' => 'Pending',
            'start' => $data['start'],
            'end' => $data['end'],
        ]);

        return response()->json([
            'status' => ['code' => 200, 'message' => 'Vacation updated successfully.'],
            'data' => ['vacation' => $vacation]
        ]);
    }


    public function updateStatus(Request $request, String $vacationId): JsonResponse
    {

        $data = $request->all();

        $vacation = Vacation::find($vacationId);
        if (!$vacation) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Vacation not found.']]);
        }
        
        try {
            $this->authorize('updateStatus', $vacation);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => ['code' => 403, 'message' => 'You are not allowed to update this vacation status.']
            ], 403);
        }

        $validator = Validator::make($data, [
            'status' => 'required|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        
        $vacation->update(['status' => $data['status']]);

        return response()->json([
            'status' => ['code' => 200, 'message' => 'Vacation status updated successfully.'],
            'data' => ['vacation' => $vacation]
        ]);
    }

    public function destroy(String $vacationId)
    {

        $vacation = Vacation::find($vacationId);
        if (!$vacation) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Vacation not found.']]);
        }
        
        try {
            $this->authorize('delete', $vacation);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => ['code' => 403, 'message' => 'You are not allowed to delete this vacation.']
            ], 403);
        }

        $vacation->delete();

        return response()->json([
            'status' => ['code' => 204, 'message' => 'Vacation deleted successfully.']
        ]);
    }
}

