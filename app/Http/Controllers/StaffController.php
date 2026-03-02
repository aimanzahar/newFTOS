<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // 3. Redirect back with success message
        return redirect()->back()->with('success', 'Staff member registered successfully.');
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