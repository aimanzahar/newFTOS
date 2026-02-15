<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FoodTruck;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create()
    {
        $foodTrucks = FoodTruck::all();
        return view('auth.UserRegistrationPage', compact('foodTrucks'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone_no' => ['required', 'string', 'max:20', 'unique:'.User::class],
            'role' => ['required', 'in:1,2,3'], 
            
            'foodtruck_name' => ['required_if:role,2', 'nullable', 'string', 'max:255'],
            'business_license_no' => ['required_if:role,2', 'nullable', 'string', 'unique:food_trucks,business_license_no'],
            'foodtruck_desc' => ['nullable', 'string'],
            'foodtruck_id' => ['required_if:role,3', 'nullable', 'exists:food_trucks,id'],
        ]);

        // 2. Wrap in a Transaction to ensure data integrity
        $user = DB::transaction(function () use ($request) {
            
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_no' => $request->phone_no,
                'role' => (int)$request->role,
            ]);

            // 3. Logic for Food Truck Admin (Role 2)
            if ($user->role === 2) {
                $foodTruck = FoodTruck::create([
                    'foodtruck_name' => $request->foodtruck_name,
                    'business_license_no' => $request->business_license_no,
                    'foodtruck_desc' => $request->foodtruck_desc,
                    'user_id' => $user->id, 
                ]);

                // Link the admin to their newly created truck
                $user->update(['foodtruck_id' => $foodTruck->id]);
            } 
            
            // 4. Logic for Food Truck Worker (Role 3)
            elseif ($user->role === 3) {
                // Link the worker to the existing selected truck
                $user->update(['foodtruck_id' => $request->foodtruck_id]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}