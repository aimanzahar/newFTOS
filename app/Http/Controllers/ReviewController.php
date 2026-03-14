<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'reviews' => 'required|array|min:1',
            'reviews.*.menu_item_name' => 'required|string|max:255',
            'reviews.*.rating' => 'required|integer|min:1|max:5',
            'reviews.*.comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $order = Order::where('id', $request->order_id)
            ->where('customer_id', $user->id)
            ->where('status', 'done')
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found or not completed.'], 403);
        }

        $created = [];
        foreach ($request->reviews as $reviewData) {
            $existing = Review::where('order_id', $order->id)
                ->where('menu_item_name', $reviewData['menu_item_name'])
                ->first();

            if ($existing) continue;

            $created[] = Review::create([
                'order_id' => $order->id,
                'customer_id' => $user->id,
                'foodtruck_id' => $order->foodtruck_id,
                'menu_item_name' => $reviewData['menu_item_name'],
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reviews submitted successfully.',
            'count' => count($created),
        ]);
    }

    public function index($truckId)
    {
        $reviews = Review::with('customer:id,full_name')
            ->where('foodtruck_id', $truckId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($reviews);
    }

    public function checkReviewed(Request $request)
    {
        $user = Auth::user();
        $orderIds = $request->query('order_ids', '');
        $ids = array_filter(explode(',', $orderIds), fn($v) => is_numeric($v));

        if (empty($ids)) {
            return response()->json(['reviewed' => []]);
        }

        $reviewedOrderIds = Review::where('customer_id', $user->id)
            ->whereIn('order_id', $ids)
            ->distinct()
            ->pluck('order_id')
            ->toArray();

        return response()->json(['reviewed' => $reviewedOrderIds]);
    }
}
