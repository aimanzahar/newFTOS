<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FoodTruck;
use App\Models\MenuCategory;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

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
     * Show global menus page.
     */
    public function globalMenus()
    {
        $approvedRegistrations = FoodTruck::with(['menus' => function ($query) {
                $query->orderBy('category', 'asc')->orderBy('name', 'asc');
            }])
            ->where('status', 'approved')
            ->latest('food_trucks.created_at')
            ->paginate(10);

        $truckIds = $approvedRegistrations->getCollection()->pluck('id');

        $customCategoriesByTruck = MenuCategory::whereIn('foodtruck_id', $truckIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('foodtruck_id');

        $menusByTruck = [];

        foreach ($approvedRegistrations as $truck) {
            $customCategories = ($customCategoriesByTruck->get($truck->id) ?? collect())
                ->pluck('name')
                ->filter(fn ($name) => !in_array($name, ['Foods', 'Drinks', 'Desserts', 'Uncategorized'], true))
                ->values();

            $menusByTruck[$truck->id] = [
                'foodtruck_name' => $truck->foodtruck_name,
                'menus' => $truck->menus->map(function ($menu) {
                    return [
                        'id' => $menu->id,
                        'name' => $menu->name,
                        'category' => $menu->category ?: 'Uncategorized',
                        'base_price' => $menu->base_price,
                        'quantity' => $menu->quantity,
                        'status' => $menu->status,
                    ];
                })->values(),
                'custom_categories' => $customCategories,
            ];
        }

        return view('admin.global-menus', compact('approvedRegistrations', 'menusByTruck'));
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
     * Update owner/staff status for a specific truck (JSON).
     */
    public function updateTruckUserStatus(Request $request, $truckId, $userId)
    {
        $request->validate([
            'status' => 'required|in:active,deactivated,fired',
            'target_type' => 'nullable|in:owner,staff',
        ]);

        $truck = FoodTruck::findOrFail($truckId);
        $user = User::findOrFail($userId);

        $isOwner = (int) $user->id === (int) $truck->user_id
            && (int) $user->role === User::ROLE_FOOD_TRUCK_ADMIN;

        $isStaff = (int) $user->role === User::ROLE_FOOD_TRUCK_WORKER
            && (int) $user->foodtruck_id === (int) $truck->id;

        if (!$isOwner && !$isStaff) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user does not belong to this food truck.',
            ], 422);
        }

        $targetType = $request->input('target_type');
        if ($targetType === 'owner' && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Selected owner does not match this food truck.',
            ], 422);
        }

        if ($targetType === 'staff' && !$isStaff) {
            return response()->json([
                'success' => false,
                'message' => 'Selected staff member does not match this food truck.',
            ], 422);
        }

        $newStatus = $request->input('status');
        $lockBySystemAdmin = in_array($newStatus, ['deactivated', 'fired'], true);
        $cascadeToWorkers = $isOwner && $lockBySystemAdmin;
        $shutdownOrderStatuses = ['pending', 'accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery'];
        $cascadedWorkersCount = 0;
        $releasedOrdersCount = 0;

        DB::transaction(function () use (
            $user,
            $newStatus,
            $lockBySystemAdmin,
            $cascadeToWorkers,
            $shutdownOrderStatuses,
            $truck,
            $isStaff,
            &$cascadedWorkersCount,
            &$releasedOrdersCount
        ) {
            $user->update([
                'status' => $newStatus,
                'status_locked_by_system_admin' => $lockBySystemAdmin,
            ]);

            if ($cascadeToWorkers) {
                $workerIds = User::where('foodtruck_id', $truck->id)
                    ->where('role', User::ROLE_FOOD_TRUCK_WORKER)
                    ->pluck('id');

                $cascadedWorkersCount = $workerIds->count();

                if ($cascadedWorkersCount > 0) {
                    User::whereIn('id', $workerIds)->update([
                        'status' => $newStatus,
                        'status_locked_by_system_admin' => true,
                    ]);
                }

                $releasedOrdersCount = Order::where('foodtruck_id', $truck->id)
                    ->whereIn('status', $shutdownOrderStatuses)
                    ->update([
                        'status' => 'rejected',
                        'accepted_by' => null,
                    ]);
            } elseif ($isStaff && $lockBySystemAdmin) {
                $releasedOrdersCount = Order::where('foodtruck_id', $truck->id)
                    ->where('status', 'accepted')
                    ->where('accepted_by', $user->id)
                    ->update([
                        'status' => 'pending',
                        'accepted_by' => null,
                    ]);
            }
        });

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'status' => $user->status,
            'status_locked_by_system_admin' => (bool) $user->status_locked_by_system_admin,
            'cascaded_to_workers' => $cascadeToWorkers,
            'cascaded_workers_count' => $cascadedWorkersCount,
            'cascaded_status' => $cascadeToWorkers ? $newStatus : null,
            'released_orders_count' => $releasedOrdersCount,
            'user_id' => $user->id,
            'foodtruck_id' => $truck->id,
            'target_type' => $isOwner ? 'owner' : 'staff',
        ]);
    }

    /**
     * Approve a food truck.
     */
    public function approveTruck($id)
    {
        $truck = FoodTruck::findOrFail($id);
        $truck->status = 'approved';
        $truck->is_operational = false;
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
        $userId = $truck->user_id;
        $name = $truck->foodtruck_name;
        
        // Update user status to rejected using direct query to ensure it works
        User::where('id', $userId)->update([
            'status' => 'rejected',
            'foodtruck_id' => null,
        ]);
        
        // Delete the food truck record
        $truck->delete();

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
            'status' => 'required|in:pending,accepted,preparing,prepared,ready_for_pickup,delivery,done,rejected',
        ]);

        $order = Order::findOrFail($orderId);
        $order->status = $request->status;
        $order->save();

        return response()->json(['success' => true, 'status' => $order->status]);
    }
}