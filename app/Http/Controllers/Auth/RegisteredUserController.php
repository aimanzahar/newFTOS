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
        // Only fetch trucks that have been approved by the admin
        $foodTrucks = FoodTruck::where('status', 'approved')->get();
        
        return view('auth.UserRegistrationPage', compact('foodTrucks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'full_name'           => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'            => ['required', 'confirmed', Rules\Password::defaults()],
            'role'                => ['required', 'in:1,2,3'], 
            'phone_no'            => ['required', 'string', 'max:20'],
            // Required only if Food Truck Admin
            'foodtruck_name'      => ['required_if:role,2', 'nullable', 'string', 'max:255'],
            'business_license_no' => ['required_if:role,2', 'nullable', 'string', 'max:255'],
            'foodtruck_desc'      => ['nullable', 'string', 'max:1000'], // Added to validation
            // Required only if Food Truck Worker
            'foodtruck_id'        => [
                'required_if:role,3', 
                'nullable', 
                Rule::exists('food_trucks', 'id')->where(fn ($q) => $q->where('status', 'approved')),
            ],
        ]);

        return DB::transaction(function () use ($request) {
            $user = User::create([
                'full_name' => $request->full_name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => $request->role,
                'phone_no'  => $request->phone_no,
                'status'    => ($request->role == 2) ? 'pending' : 'active',
            ]);

            if ($request->role == 2) {
                $truck = FoodTruck::create([
                    'user_id'             => $user->id,
                    'foodtruck_name'      => $request->foodtruck_name,
                    'business_license_no' => $request->business_license_no,
                    'foodtruck_desc'      => $request->foodtruck_desc,
                    'status'              => 'pending',
                ]);
                $user->update(['foodtruck_id' => $truck->id]);
            } 
            elseif ($request->role == 3) {
                $user->update(['foodtruck_id' => $request->foodtruck_id]);
            }

            event(new Registered($user));
            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        });
    }
}