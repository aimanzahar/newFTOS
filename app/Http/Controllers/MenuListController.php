<?php

namespace App\Http\Controllers;

use App\Models\MenuList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuListController extends Controller
{
    /**
     * Display the menu list data on the dashboard.
     * Since you want a popup on the dashboard, we fetch the data
     * and pass it to the existing dashboard view.
     */
    public function index()
    {
        $user = Auth::user();

        // Fetch menu items belonging only to this admin's food truck
        $menuItems = MenuList::where('food_truck_id', $user->foodtruck_id)
            ->orderBy('menu_category', 'asc')
            ->orderBy('menu_name', 'asc')
            ->get();

        // Fetch workers (to keep the dashboard functional)
        $ftworkers = User::where('role', 3)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->get();

        // Instead of a new page, we return the dashboard view with both workers and menu items
        return view('ftadmin.ftadmin-dashboard', compact('menuItems', 'ftworkers'));
    }

    /**
     * Store a newly created menu item in the database.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validation based on your preferred field names
        $request->validate([
            'menu_name' => ['required', 'string', 'max:255'],
            'menu_category' => ['required', 'string', 'max:100'],
            'menu_baseprice' => ['required', 'numeric', 'min:0'],
            'menu_quantity' => ['required', 'integer', 'min:0'],
            'menu_desc' => ['nullable', 'string'],
            'menu_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $imagePath = null;
        if ($request->hasFile('menu_image')) {
            $imagePath = $request->file('menu_image')->store('menu-items', 'public');
        }

        MenuList::create([
            'food_truck_id' => $user->foodtruck_id,
            'menu_name' => $request->menu_name,
            'menu_category' => $request->menu_category,
            'menu_baseprice' => $request->menu_baseprice,
            'menu_quantity' => $request->menu_quantity,
            'menu_desc' => $request->menu_desc,
            'menu_image' => $imagePath,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Menu item added successfully!');
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $item = MenuList::where('id', $id)
            ->where('food_truck_id', $user->foodtruck_id)
            ->firstOrFail();

        $request->validate([
            'menu_name' => ['required', 'string', 'max:255'],
            'menu_baseprice' => ['required', 'numeric'],
            'menu_quantity' => ['required', 'integer'],
        ]);

        $item->update($request->all());

        return redirect()->back()->with('success', 'Menu item updated.');
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $item = MenuList::where('id', $id)
            ->where('food_truck_id', $user->foodtruck_id)
            ->firstOrFail();

        $item->delete();

        return redirect()->back()->with('success', 'Menu item deleted.');
    }
}