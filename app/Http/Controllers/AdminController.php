<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FoodTruck;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard with statistics.
     */
    public function index()
    {
        $totalTrucks = FoodTruck::count();
        
        // Count truck owners awaiting verification
        $pendingApprovals = User::where('role', 'truck_owner')
                                ->whereNull('email_verified_at') 
                                ->count();

        // Matches: resources/views/admin/admin-dashboard.blade.php
        return view('admin.admin-dashboard', compact('totalTrucks', 'pendingApprovals'));
    }

    /**
     * Show registrations that need approval.
     */
    public function pendingTrucks()
    {
        /**
         * Logic: Get trucks where the owner (user) is not yet verified.
         * We alias users.id to user_actual_id to avoid overwriting food_trucks.id.
         */
        $pendingRegistrations = FoodTruck::join('users', 'food_trucks.user_id', '=', 'users.id')
            ->whereNull('users.email_verified_at')
            ->select(
                'food_trucks.*', 
                'users.full_name', 
                'users.email', 
                'users.phone_no', 
                'users.id as user_actual_id'
            )
            ->get();

        /**
         * FIX: Pluralized "registrations" to match your physical file:
         * resources/views/admin/pending-truck-registrations.blade.php
         */
        return view('admin.pending-truck-registrations', compact('pendingRegistrations'));
    }

    /**
     * Approve a user/truck registration.
     */
    public function approveUser($id)
    {
        $user = User::findOrFail($id);
        
        // Approve by setting verification timestamp
        $user->email_verified_at = now();
        $user->save();
        
        return back()->with('success', "Account for {$user->full_name} has been approved.");
    }
}