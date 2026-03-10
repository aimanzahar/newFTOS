<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FoodTruck;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        /** * Note: $foodTrucks is no longer strictly needed for registration 
         * since worker selection was removed, but kept if the view still 
         * references the variable to avoid undefined variable errors.
         */
        $foodTrucks = FoodTruck::where('status', 'approved')->get();
        
        return view('auth.UserRegistrationPage', compact('foodTrucks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'full_name'           => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'lowercase', 'email', 'max:255', 
                                      Rule::unique('users')->where(function ($query) {
                                          $query->where('status', '!=', 'rejected');
                                      })],
            'password'            => ['required', 'confirmed', Rules\Password::defaults()],
            'role'                => ['required', 'in:1,2'], // Removed '3' from allowed roles
            'phone_no'            => ['required', 'string', 'max:20'],
            
            // Required only if Food Truck Admin (Role 2)
            'foodtruck_name'      => ['required_if:role,2', 'nullable', 'string', 'max:255'],
            'business_license_no' => ['required_if:role,2', 'nullable', 'string', 'max:255'],
            'foodtruck_desc'      => ['nullable', 'string', 'max:1000'],
            
            /**
             * Removed 'foodtruck_id' validation as Role 3 (Worker) 
             * registration is no longer supported via this form.
             */
        ]);

        return DB::transaction(function () use ($request) {
            // Check if a rejected user is re-registering
            $existingRejected = User::where('email', $request->email)->where('status', 'rejected')->first();
            
            if ($existingRejected) {
                // Reactivate the rejected user
                $user = $existingRejected;
                $user->update([
                    'full_name' => $request->full_name,
                    'password'  => Hash::make($request->password),
                    'role'      => $request->role,
                    'phone_no'  => $request->phone_no,
                    'status'    => ($request->role == 2) ? 'pending' : 'active',
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'full_name' => $request->full_name,
                    'email'     => $request->email,
                    'password'  => Hash::make($request->password),
                    'role'      => $request->role,
                    'phone_no'  => $request->phone_no,
                    'status'    => ($request->role == 2) ? 'pending' : 'active',
                ]);
            }

            // Logic for Food Truck Admin
            if ($request->role == 2) {
                $truck = FoodTruck::create([
                    'user_id'             => $user->id,
                    'foodtruck_name'      => $request->foodtruck_name,
                    'business_license_no' => $request->business_license_no,
                    'foodtruck_desc'      => $request->foodtruck_desc,
                    'status'              => 'pending',
                ]);
                
                // Link the admin to their newly created truck
                $user->update(['foodtruck_id' => $truck->id]);
            } 

            event(new Registered($user));
            Auth::login($user);

            return match ((int) $user->role) {
                User::ROLE_SYSTEM_ADMIN => redirect(route('admin.dashboard', absolute: false)),
                User::ROLE_FOOD_TRUCK_ADMIN => redirect(route('ftadmin.dashboard', absolute: false)),
                User::ROLE_FOOD_TRUCK_WORKER => redirect(route('ftworker.dashboard', absolute: false)),
                User::ROLE_CUSTOMER => redirect(route('customer.dashboard', absolute: false))
                    ->with('customer_welcome_type', 'new'),
                default => redirect(route('dashboard', absolute: false)),
            };
        });
    }
}