<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        
        /**
         * Determine layout based on user role
         * Role 1: Admin (System Admin)
         * Role 2: FT Admin (Food Truck Owner)
         * Role 3: FT Worker (Food Truck Staff)
         * Default: Customer
         */
        $layout = match ($user->role) {
            6 => 'layouts.admin.admin-layout',
            2 => 'layouts.ftadmin.ftadmin-layout',
            3 => 'layouts.ftworker.ftworker-layout',
            default => 'layouts.customer.customer-layout',
        };

        return view('profile.edit', [
            'user' => $user,
            'layout' => $layout,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}