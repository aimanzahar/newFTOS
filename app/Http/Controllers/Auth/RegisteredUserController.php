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

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     * This fetches the food trucks for your dropdown.
     */
    public function create(): View
    {
        // Get all trucks so we can see 'ExtraJoss' and 'truck test' in the dropdown
        $foodTrucks = FoodTruck::all();
        
        return view('auth.UserRegistrationPage', compact('foodTrucks'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'full_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
            'role'         => ['required', 'integer'],
            'foodtruck_id' => ['nullable', 'exists:food_trucks,id'], 
            'phone_no'     => ['required', 'string', 'max:20'],
        ]);

        $user = User::create([
            'full_name'    => $request->full_name,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'role'         => $request->role,
            // Only assign foodtruck_id if the role is Worker (2) or Admin (3)
            'foodtruck_id' => in_array($request->role, [2, 3]) ? $request->foodtruck_id : null,
            'phone_no'     => $request->phone_no,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}