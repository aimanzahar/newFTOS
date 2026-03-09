<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodTruck;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TruckApprovalController extends Controller
{
    /**
     * Approve the food truck registration.
     */
    public function approve($id)
    {
        $truck = FoodTruck::with('owner')->findOrFail($id);

        $truck->update(['status' => 'approved']);

        // Clear the pending status on the owner's user account so the
        // overlay in ftadmin-layout no longer appears after approval.
        if ($truck->owner) {
            $truck->owner->update(['status' => 'active']);
        }

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

        DB::transaction(function () use ($truck) {
            User::where('id', $truck->user_id)->update([
                'status' => 'rejected',
                'foodtruck_id' => null,
            ]);

            $truck->delete();
        });

        return redirect()->back()->with('rejected', "Registration for '{$name}' was rejected and removed.");
    }
}