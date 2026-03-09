<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FoodTruck;
use App\Models\Order;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard with statistics.
     */
    public function index()
    {
        $approvedTrucks = FoodTruck::where('status', 'approved')->count();
        $pendingApprovals = FoodTruck::where('status', 'pending')->count();
        $totalTrucks = FoodTruck::count();

        return view('admin.admin-dashboard', compact(
            'approvedTrucks', 
            'pendingApprovals', 
            'totalTrucks'
        ));
    }

    /**
     * Show registrations that need approval.
     */
    public function pendingTrucks()
    {
        $pendingRegistrations = FoodTruck::join('users', 'food_trucks.user_id', '=', 'users.id')
            ->where('food_trucks.status', 'pending')
            ->select(
                'food_trucks.*', 
                'users.full_name', 
                'users.email', 
                'users.phone_no'
            )
            ->latest('food_trucks.created_at')
            ->paginate(10);

        return view('admin.pending-truck-registrations', compact('pendingRegistrations'));
    }

    /**
     * Show approved food trucks.
     */
    public function approvedTrucks()
    {
        $approvedRegistrations = FoodTruck::with(['owner', 'staff', 'menus.optionGroups.choices'])
            ->where('status', 'approved')
            ->latest('food_trucks.created_at')
            ->paginate(10);

        return view('admin.approved-trucks-list', compact('approvedRegistrations'));
    }

    /**
     * Update truck details (name, description).
     */
    public function updateTruckDetails(Request $request, $truckId)
    {
        $request->validate([
            'foodtruck_name' => 'required|string|max:255',
            'foodtruck_desc' => 'nullable|string|max:1000',
        ]);

        $truck = FoodTruck::findOrFail($truckId);
        $truck->foodtruck_name = $request->input('foodtruck_name');
        $truck->foodtruck_desc = $request->input('foodtruck_desc');
        $truck->save();

        return response()->json([
            'success' => true,
            'message' => 'Truck details updated successfully',
            'truck' => $truck,
        ]);
    }

    /**
     * Approve a food truck.
     */
    public function approveTruck($id)
    {
        $truck = FoodTruck::findOrFail($id);
        $truck->status = 'approved';
        $truck->save();

        $user = User::find($truck->user_id);
        if ($user) {
            $user->status = 'active';
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();
        }
        
        return back()->with('success', "The '{$truck->foodtruck_name}' registration has been approved.");
    }

    /**
     * Reject a food truck registration.
     */
    public function rejectTruck($id)
    {
        $truck = FoodTruck::findOrFail($id);
        $user = $truck->user;
        $name = $truck->foodtruck_name;
        
        // Set user status to rejected
        $user->update(['status' => 'rejected']);
        
        // Optionally set truck status to rejected as well
        $truck->update(['status' => 'rejected']);

        return back()->with('rejected', "The registration for '{$name}' has been rejected.");
    }

    /**
     * Return orders for a specific food truck (JSON).
     */
    public function truckOrders($truckId)
    {
        $orders = Order::where('foodtruck_id', $truckId)
            ->latest()
            ->limit(100)
            ->get();

        return response()->json($orders);
    }

    /**
     * Update status of a specific order (JSON).
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,preparing,prepared,ready_for_pickup,delivery,done',
        ]);

        $order = Order::findOrFail($orderId);
        $order->status = $request->status;
        $order->save();

        return response()->json(['success' => true, 'status' => $order->status]);
    }
}