<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $hash = hash('sha256', $data['endpoint']);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'user_agent' => $request->userAgent(),
                'activo' => true,
            ]
        );

        return response()->json(['status' => 'suscrito'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'string']]);

        PushSubscription::where('endpoint_hash', hash('sha256', $request->input('endpoint')))
            ->where('user_id', $request->user()->id)
            ->update(['activo' => false]);

        return response()->json(['status' => 'desuscrito']);
    }
}
