<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Http\Request;

class WorkerPunchCardController extends Controller
{
    public function punchIn(Request $request)
    {
        $user = $request->user();

        if (!$user || (int) $user->role !== User::ROLE_FOOD_TRUCK_WORKER) {
            return response()->json([
                'success' => false,
                'message' => 'Only food truck workers can use punch card actions.',
            ], 403);
        }

        $activeCard = WorkerPunchCard::query()
            ->where('user_id', $user->id)
            ->whereNull('punched_out_at')
            ->latest('punched_in_at')
            ->first();

        if ($activeCard) {
            return $this->respond($request, false, 'You are already punched in for an active shift.', 422);
        }

        WorkerPunchCard::query()->create([
            'user_id' => $user->id,
            'foodtruck_id' => $user->foodtruck_id,
            'punched_in_at' => now(),
        ]);

        return $this->respond($request, true, 'Punch in recorded successfully. You can now accept new orders.');
    }

    public function punchOut(Request $request)
    {
        $user = $request->user();

        if (!$user || (int) $user->role !== User::ROLE_FOOD_TRUCK_WORKER) {
            return response()->json([
                'success' => false,
                'message' => 'Only food truck workers can use punch card actions.',
            ], 403);
        }

        $activeCard = WorkerPunchCard::query()
            ->where('user_id', $user->id)
            ->whereNull('punched_out_at')
            ->latest('punched_in_at')
            ->first();

        if (!$activeCard) {
            return $this->respond($request, false, 'No active punch-in record found. Please punch in first.', 422);
        }

        $activeCard->update([
            'punched_out_at' => now(),
        ]);

        return $this->respond($request, true, 'Punch out recorded successfully.');
    }

    private function respond(Request $request, bool $success, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $status);
        }

        if ($success) {
            return redirect()->route('ftworker.dashboard')->with('success', $message);
        }

        return redirect()->route('ftworker.dashboard')->with('error', $message);
    }
}
