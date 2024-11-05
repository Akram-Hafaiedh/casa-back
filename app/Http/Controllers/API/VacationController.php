<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vacation;
use App\Http\Requests\StoreVacationRequest;
use App\Http\Requests\UpdateVacationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VacationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() : JsonResponse
    {
        $vacations = Vacation::all();
        return response()->json([
            'data' => ['vacations' => $vacations->load('user')],
            'status' => ['code' => 200, 'message' => 'Vacations retrieved successfully.']
        ]);
    }


    public function myVacations() : JsonResponse
    {
        $vacations = auth()->user()->vacations()->get();
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

        $vacation = Vacation::create([
            'user_id' => auth()->user()->id,
            'title' => $data['title'],
            'comment' => $data['comment'] ?? '',
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
    public function update(UpdateVacationRequest $request, Vacation $vacation)
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


    /**
     * Updates the status of a Vacation
     * @param string $vacationId
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateStatus(Request $request, string $vacationId): JsonResponse
    {
        $status = $request->all()['status'];

        if($status !== 'Approved' && $status !== 'Rejected') {
            return response()->json(['status' => ['code' => 422, 'message' => 'Invalid status.']]);
        }

        if (auth()->user()->role->name !== 'Administrator') {
            return response()->json(['status' => ['code' => 403, 'message' => 'Unauthorized.']]);
        }
        $vacation = Vacation::find($vacationId);
        if (!$vacation) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Vacation not found.']]);
        }
        $vacation->update(['status' => $status]);
        return response()->json([
            'status' => ['code' => 200, 'message' => 'Vacation status updated successfully.'],
            'data' => ['vacation' => $vacation]
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string$vacationId)
    {
        $vacation = Vacation::find($vacationId);
        if (!$vacation) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Vacation not found.']]);
        }
        $vacation->delete();

        return response()->json([
            'status' => ['code' => 200, 'message' => 'Vacation deleted successfully.']
        ]);
    }
}

