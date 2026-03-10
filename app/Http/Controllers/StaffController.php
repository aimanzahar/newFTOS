<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\WorkerPunchCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class StaffController extends Controller
{
    /**
     * Store a newly created staff member (Role 3).
     */
    public function store(Request $request)
    {
        $admin = Auth::user();

        // 1. Validation
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_no' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 2. Security: Force Role 3 and the Admin's Food Truck ID
        // We use $admin->foodtruck_id to ensure the staff is tied to the correct truck.
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_no' => $request->phone_no,
            'password' => Hash::make($request->password),
            'role' => 3, // ROLE_FOOD_TRUCK_WORKER
            'foodtruck_id' => $admin->foodtruck_id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'user' => $user->fresh()]);
        }

        // 3. Redirect back with success message
        return redirect()->back()->with('success', 'Staff member registered successfully.');
    }

    /**
     * Mark a staff member as fired.
     */
    public function fire($id)
    {
        $admin = Auth::user();

        $staff = User::where('id', $id)
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('role', 3)
            ->firstOrFail();

        if ((bool) $staff->status_locked_by_system_admin && in_array($staff->status, ['deactivated', 'fired'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff status is managed by system admin and cannot be changed here.',
            ], 403);
        }

        $releasedOrdersCount = 0;

        DB::transaction(function () use ($staff, $admin, &$releasedOrdersCount) {
            $staff->update([
                'status' => 'fired',
                'status_locked_by_system_admin' => false,
            ]);

            $releasedOrdersCount = Order::where('foodtruck_id', $admin->foodtruck_id)
                ->where('status', 'accepted')
                ->where('accepted_by', $staff->id)
                ->update([
                    'status' => 'pending',
                    'accepted_by' => null,
                ]);
        });

        return response()->json([
            'success' => true,
            'status' => 'fired',
            'released_orders_count' => $releasedOrdersCount,
        ]);
    }

    /**
     * Toggle a staff member's status between active and deactivated.
     */
    public function deactivate($id)
    {
        $admin = Auth::user();

        $staff = User::where('id', $id)
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('role', 3)
            ->firstOrFail();

        if ((bool) $staff->status_locked_by_system_admin && in_array($staff->status, ['deactivated', 'fired'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff status is managed by system admin and cannot be changed here.',
            ], 403);
        }

        $newStatus = $staff->status === 'deactivated' ? 'active' : 'deactivated';

        $releasedOrdersCount = 0;

        DB::transaction(function () use ($staff, $admin, $newStatus, &$releasedOrdersCount) {
            $staff->update([
                'status' => $newStatus,
                'status_locked_by_system_admin' => false,
            ]);

            if ($newStatus === 'deactivated') {
                $releasedOrdersCount = Order::where('foodtruck_id', $admin->foodtruck_id)
                    ->where('status', 'accepted')
                    ->where('accepted_by', $staff->id)
                    ->update([
                        'status' => 'pending',
                        'accepted_by' => null,
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'released_orders_count' => $releasedOrdersCount,
        ]);
    }

    /**
     * Permanently delete a fired staff member.
     */
    public function delete($id)
    {
        $admin = Auth::user();

        $staff = User::where('id', $id)
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('role', 3)
            ->firstOrFail();

        if ((bool) $staff->status_locked_by_system_admin && in_array($staff->status, ['deactivated', 'fired'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff status is managed by system admin and cannot be deleted here.',
            ], 403);
        }

        $staff->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Return staff activity + punch card details for ftadmin modal.
     */
    public function details(Request $request, $id)
    {
        $admin = Auth::user();

        $staff = User::where('id', $id)
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('role', 3)
            ->firstOrFail();

        $activeOrderStatuses = ['accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery'];
        $activeOrders = Order::query()
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('accepted_by', $staff->id)
            ->whereIn('status', $activeOrderStatuses)
            ->orderByDesc('updated_at')
            ->get();

        $range = $request->query('range', 'all');
        $punchLogsQuery = WorkerPunchCard::query()
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('user_id', $staff->id);

        if ($range === 'today') {
            $punchLogsQuery->whereDate('punched_in_at', now()->toDateString());
        } elseif ($range === 'week') {
            $punchLogsQuery->whereBetween('punched_in_at', [
                now()->copy()->startOfWeek(),
                now()->copy()->endOfWeek(),
            ]);
        }

        $punchLogs = $punchLogsQuery
            ->orderByDesc('punched_in_at')
            ->limit(120)
            ->get();

        $serializedPunchLogs = $punchLogs
            ->map(function (WorkerPunchCard $log) use ($admin, $staff) {
                $windowStart = $log->punched_in_at;
                $windowEnd = $log->punched_out_at;

                $completedOrdersQuery = Order::query()
                    ->where('foodtruck_id', $admin->foodtruck_id)
                    ->where('accepted_by', $staff->id)
                    ->where('status', 'done')
                    ->where('updated_at', '>=', $windowStart);

                if ($windowEnd) {
                    $completedOrdersQuery->where('updated_at', '<=', $windowEnd);
                }

                return [
                    'id' => $log->id,
                    'punched_in_at' => $windowStart?->toIso8601String(),
                    'punched_out_at' => $windowEnd?->toIso8601String(),
                    'total_completed_orders' => $completedOrdersQuery->count(),
                ];
            })
            ->values();

        $activePunchCard = WorkerPunchCard::query()
            ->where('foodtruck_id', $admin->foodtruck_id)
            ->where('user_id', $staff->id)
            ->whereNull('punched_out_at')
            ->latest('punched_in_at')
            ->first();

        return response()->json([
            'success' => true,
            'staff' => [
                'id' => $staff->id,
                'full_name' => $staff->full_name,
                'email' => $staff->email,
                'phone_no' => $staff->phone_no,
                'status' => $staff->status,
                'status_locked_by_system_admin' => (bool) ($staff->status_locked_by_system_admin ?? false),
                'shift_status' => $activePunchCard ? 'active' : 'inactive',
                'active_punched_in_at' => $activePunchCard?->punched_in_at?->toIso8601String(),
            ],
            'active_orders' => $activeOrders,
            'punch_logs' => $serializedPunchLogs,
            'range' => $range,
        ]);
    }

    /**
     * List staff for the specific food truck
     */
    public function index()
    {
        $admin = Auth::user();
        
        $ftworkers = User::where('foodtruck_id', $admin->foodtruck_id)
            ->where('role', 3)
            ->get();

        // FIXED: Changed 'ftadmin.dashboard' to 'ftadmin.ftadmin-dashboard' 
        // to match your actual file: resources/views/ftadmin/ftadmin-dashboard.blade.php
        return view('ftadmin.ftadmin-dashboard', compact('ftworkers'));
    }
}