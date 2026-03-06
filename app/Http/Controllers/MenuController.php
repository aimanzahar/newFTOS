<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
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
        $menuItems = Menu::where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('category', 'asc')
            ->orderBy('name', 'asc')
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
            'name'        => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],
            'base_price'  => ['required', 'numeric', 'min:0'],
            'quantity'    => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $imagePath = null;
        if ($request->filled('image_data')) {
            $base64 = $request->input('image_data');
            if (str_contains($base64, ',')) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $filename = 'menu-items/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, base64_decode($base64));
            $imagePath = $filename;
        } elseif ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-items', 'public');
        }

        Menu::create([
            'foodtruck_id' => $user->foodtruck_id,
            'name'         => $request->name,
            'category'     => $request->category,
            'base_price'   => $request->base_price,
            'quantity'     => $request->quantity,
            'description'  => $request->description,
            'image'        => $imagePath,
        ]);

        return redirect()->back()->with('success', 'Menu item added successfully!');
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],
            'base_price'  => ['required', 'numeric', 'min:0'],
            'quantity'    => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $data = $request->only(['name', 'category', 'base_price', 'quantity', 'description']);

        if ($request->filled('image_data')) {
            $base64 = $request->input('image_data');
            if (str_contains($base64, ',')) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $filename = 'menu-items/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, base64_decode($base64));
            $data['image'] = $filename;
        } elseif ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu-items', 'public');
        }

        $item->update($data);

        return redirect()->back()->with('success', 'Menu item updated.');
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $item->delete();

        return redirect()->back()->with('success', 'Menu item deleted.');
    }
}