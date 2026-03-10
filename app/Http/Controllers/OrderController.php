<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Return all pending orders for the current user's food truck.
     * Shared across all ftworkers and the ftadmin of the same truck.
     */
    public function pending(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('foodtruck_id', $user->foodtruck_id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Return all orders accepted by the current logged-in user.
     */
    public function myActivity(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('accepted_by', $user->id)
            ->where('status', '!=', 'pending')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Accept a pending order. Uses a DB transaction + lock to prevent
     * two users from accepting the same order simultaneously.
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();

        if ((int) $user->role === User::ROLE_FOOD_TRUCK_WORKER) {
            $hasActivePunchCard = WorkerPunchCard::query()
                ->where('user_id', $user->id)
                ->where('foodtruck_id', $user->foodtruck_id)
                ->whereNull('punched_out_at')
                ->exists();

            if (!$hasActivePunchCard) {
                return response()->json([
                    'success' => false,
                    'code' => 'punch_card_required',
                    'message' => 'Please punch in before accepting new orders to start your shift.',
                ], 403);
            }
        }

        $order = null;

        DB::transaction(function () use ($id, $user, &$order) {
            $order = Order::where('id', $id)
                ->where('foodtruck_id', $user->foodtruck_id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$order) return;

            $order->status = 'accepted';
            $order->accepted_by = $user->id;
            $order->save();
        });

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already accepted by someone else.',
            ], 422);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Update the status of an order the current user has accepted.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('accepted_by', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $valid = ['accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery', 'done'];
        $status = $request->input('status');

        if (!in_array($status, $valid)) {
            return response()->json(['success' => false, 'message' => 'Invalid status.'], 422);
        }

        $order->status = $status;
        $order->save();

        return response()->json(['success' => true, 'order' => $order]);
    }
}
