<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuOptionGroup;
use App\Models\MenuChoice;
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
        $originalImagePath = null;

        $imageData = $request->input('image_data', '');
        if (str_starts_with($imageData, 'data:')) {
            $base64 = str_contains($imageData, ',')
                ? substr($imageData, strpos($imageData, ',') + 1)
                : $imageData;
            $filename = 'menu-items/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, base64_decode($base64));
            $imagePath = $filename;
        } elseif ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-items', 'public');
        }

        $originalData = $request->input('original_image_data', '');
        if (str_starts_with($originalData, 'data:')) {
            $base64Orig = str_contains($originalData, ',')
                ? substr($originalData, strpos($originalData, ',') + 1)
                : $originalData;
            $origFilename = 'menu-items/orig_' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($origFilename, base64_decode($base64Orig));
            $originalImagePath = $origFilename;
        }

        $item = Menu::create([
            'foodtruck_id'   => $user->foodtruck_id,
            'name'           => $request->name,
            'category'       => $request->category,
            'base_price'     => $request->base_price,
            'quantity'       => $request->quantity,
            'description'    => $request->description,
            'image'          => $imagePath,
            'original_image' => $originalImagePath,
        ]);

        // Save option groups and their choices
        $optionGroups = json_decode($request->input('option_groups', '[]'), true);
        if (is_array($optionGroups)) {
            foreach ($optionGroups as $i => $groupData) {
                if (empty($groupData['name'])) continue;
                $selType = in_array($groupData['selectionType'] ?? 'single', ['single', 'multiple'])
                    ? $groupData['selectionType']
                    : 'single';
                $group = $item->optionGroups()->create([
                    'name'           => $groupData['name'],
                    'selection_type' => $selType,
                    'sort_order'     => $i,
                ]);
                foreach (($groupData['choices'] ?? []) as $j => $choiceData) {
                    if (empty($choiceData['name'])) continue;
                    $group->choices()->create([
                        'name'       => $choiceData['name'],
                        'price'      => is_numeric($choiceData['price'] ?? '') ? (float) $choiceData['price'] : 0,
                        'quantity'   => is_numeric($choiceData['quantity'] ?? '') ? (int) $choiceData['quantity'] : 0,
                        'sort_order' => $j,
                        'status'     => in_array($choiceData['status'] ?? 'available', ['available', 'unavailable']) ? $choiceData['status'] : 'available',
                    ]);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'item' => $item->fresh()]);
        }

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

        $imageData = $request->input('image_data', '');
        if (str_starts_with($imageData, 'data:')) {
            $base64 = str_contains($imageData, ',')
                ? substr($imageData, strpos($imageData, ',') + 1)
                : $imageData;
            $filename = 'menu-items/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, base64_decode($base64));
            $data['image'] = $filename;
        } elseif ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu-items', 'public');
        }

        $originalData = $request->input('original_image_data', '');
        if (str_starts_with($originalData, 'data:')) {
            $base64Orig = str_contains($originalData, ',')
                ? substr($originalData, strpos($originalData, ',') + 1)
                : $originalData;
            $origFilename = 'menu-items/orig_' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($origFilename, base64_decode($base64Orig));
            $data['original_image'] = $origFilename;
        }

        $item->update($data);

        // Replace option groups
        $item->optionGroups()->delete();
        $optionGroups = json_decode($request->input('option_groups', '[]'), true);
        if (is_array($optionGroups)) {
            foreach ($optionGroups as $i => $groupData) {
                if (empty($groupData['name'])) continue;
                $selType = in_array($groupData['selectionType'] ?? 'single', ['single', 'multiple'])
                    ? $groupData['selectionType']
                    : 'single';
                $group = $item->optionGroups()->create([
                    'name'           => $groupData['name'],
                    'selection_type' => $selType,
                    'sort_order'     => $i,
                ]);
                foreach (($groupData['choices'] ?? []) as $j => $choiceData) {
                    if (empty($choiceData['name'])) continue;
                    $group->choices()->create([
                        'name'       => $choiceData['name'],
                        'price'      => is_numeric($choiceData['price'] ?? '') ? (float) $choiceData['price'] : 0,
                        'quantity'   => is_numeric($choiceData['quantity'] ?? '') ? (int) $choiceData['quantity'] : 0,
                        'sort_order' => $j,
                        'status'     => in_array($choiceData['status'] ?? 'available', ['available', 'unavailable']) ? $choiceData['status'] : 'available',
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Menu item updated.');
    }

    /**
     * Quick-update name, category, price and description of a menu item.
     */
    public function updateDetails(Request $request, $id)
    {
        $user = Auth::user();
        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'max:100'],
            'base_price'  => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $item->update($request->only(['name', 'category', 'base_price', 'description']));

        return response()->json(['success' => true, 'item' => $item->fresh()]);
    }

    /**
     * Quick-update only the quantity of a menu item.
     */
    public function updateQuantity(Request $request, $id)
    {
        $user = Auth::user();
        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $item->update(['quantity' => $request->quantity]);

        return response()->json(['success' => true, 'quantity' => $item->fresh()->quantity]);
    }

    /**
     * Replace the option groups (and choices) for a menu item.
     */
    public function updateOptions(Request $request, $id)
    {
        $user = Auth::user();
        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $item->optionGroups()->delete();

        $optionGroups = $request->input('option_groups', []);
        if (!is_array($optionGroups)) {
            $optionGroups = json_decode($optionGroups, true) ?? [];
        }

        foreach ($optionGroups as $i => $groupData) {
            if (empty($groupData['name'])) continue;
            $selType = in_array($groupData['selectionType'] ?? 'single', ['single', 'multiple'])
                ? $groupData['selectionType']
                : 'single';
            $group = $item->optionGroups()->create([
                'name'           => $groupData['name'],
                'selection_type' => $selType,
                'sort_order'     => $i,
            ]);
            foreach (($groupData['choices'] ?? []) as $j => $choiceData) {
                if (empty($choiceData['name'])) continue;
                $group->choices()->create([
                    'name'       => $choiceData['name'],
                    'price'      => is_numeric($choiceData['price'] ?? '') ? (float) $choiceData['price'] : 0,
                    'quantity'   => is_numeric($choiceData['quantity'] ?? '') ? (int) $choiceData['quantity'] : 0,
                    'sort_order' => $j,
                    'status'     => in_array($choiceData['status'] ?? 'available', ['available', 'unavailable']) ? $choiceData['status'] : 'available',
                ]);
            }
        }

        $item->load('optionGroups.choices');
        return response()->json(['success' => true, 'option_groups' => $item->optionGroups]);
    }

    /**
     * Toggle a menu item's status between available and unavailable.
     */
    public function toggleStatus($id)
    {
        $user = Auth::user();

        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $newStatus = $item->status === 'unavailable' ? 'available' : 'unavailable';
        $item->update(['status' => $newStatus]);

        return response()->json(['success' => true, 'status' => $newStatus]);
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        
        $item = Menu::where('id', $id)
            ->where('foodtruck_id', $user->foodtruck_id)
            ->firstOrFail();

        $item->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Menu item deleted.');
    }
}