<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ComingSoonSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemController extends Controller
{
    public function notifyMe(Request $request)
    {
        $data = $request->all();
        $validated = Validator::make($data, [
            'email' => 'required|email',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'data' => $data,
                'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validated->errors()
            ]);
        }

        if (ComingSoonSubscription::where('email', $data['email'])->exists()) {
            return response()->json([
                'data' => [],
                'status' => ['code' => 409, 'message' => 'You are already subscribed']
            ]);
        }

        ComingSoonSubscription::create($data);

        return response()->json([
            'data' => [],
            'status' => ['code' => 200, 'message' => 'You will be notified when we launch!']
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $data = request()->all();
        $validated = Validator::make($data,[
            'email' => 'required|email',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validated->errors()
            ]);
        }
        $subscription = ComingSoonSubscription::where('email',$data['email'])->first();
        if (!$subscription) {
            return response()->json([
                'data' => [],
                'status' => ['code' => 404, 'message' => 'Subscription not found']
            ]);
        }
        $subscription->delete();
        return response()->json([
            'data' => [],
            'status' => ['code' => 200, 'message' => 'You will no longer be notified when we launch!']
        ]);
    }
    public function getSubscribedUsers()
    {
        $subscriptions = ComingSoonSubscription::all();
        if ($subscriptions->isEmpty()) {
            return response()->json([
                'data' => [],
                'status' => ['code' => 404, 'message' => 'No subscriptions found']
            ]);
        }
        return response()->json([
            'data' => ['subscriptions' => $subscriptions],
            'status' => ['code' => 200, 'message' => 'Subscriptions retrieved statusfully.']
        ]);
    }
}
