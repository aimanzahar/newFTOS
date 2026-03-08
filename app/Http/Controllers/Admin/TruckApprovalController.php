<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodTruck;
use Illuminate\Http\Request;

class TruckApprovalController extends Controller
{
    /**
     * Approve the food truck registration.
     */
    public function approve($id)
    {
        $truck = FoodTruck::findOrFail($id);

        // Update the status to 'approved'
        // Assuming your 'add_status_to_food_trucks_table' migration uses 'approved'
        $truck->update([
            'status' => 'approved'
        ]);

        // Also update the associated user's status to 'active'
        $user = $truck->user;
        if ($user) {
            $user->update([
                'status' => 'active'
            ]);
        }

        return redirect()->back()->with('success', "Food truck '{$truck->foodtruck_name}' has been approved successfully.");
    }

    /**
     * Reject and delete the food truck registration.
     */
    public function reject($id)
    {
        $truck = FoodTruck::findOrFail($id);
        $name = $truck->foodtruck_name;

        // Delete the record (as requested by your blade file logic)
        $truck->delete();

        return redirect()->back()->with('rejected', "Registration for '{$name}' was rejected and removed.");
    }
}