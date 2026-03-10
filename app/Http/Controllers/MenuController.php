<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuOptionGroup;
use App\Models\MenuChoice;
use App\Models\MenuCategory;
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
        $menuItems = Menu::with(['optionGroups.choices'])
            ->where('foodtruck_id', $user->foodtruck_id)
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
            'category'    => ['nullable', 'string', 'max:100'],
            'base_price'  => ['nullable', 'numeric', 'min:0'],
            'quantity'    => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $optionGroups = json_decode($request->input('option_groups', '[]'), true);
        if (!is_array($optionGroups)) {
            $optionGroups = [];
        }

        $optionError = $this->validateOptionGroupQuantities($optionGroups);
        if ($optionError) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $optionError], 422);
            }

            return redirect()->back()->withErrors(['option_groups' => $optionError])->withInput();
        }

        // Validate pricing: either base_price or choice prices must be filled
        $pricingError = $this->validatePricing($request->base_price, $optionGroups);
        if ($pricingError) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $pricingError], 422);
            }

            return redirect()->back()->withErrors(['pricing' => $pricingError])->withInput();
        }

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

        // Set category to "Uncategorized" if empty
        $category = !empty($request->category) ? $request->category : 'Uncategorized';

        $item = Menu::create([
            'foodtruck_id'   => $user->foodtruck_id,
            'name'           => $request->name,
            'category'       => $category,
            'base_price'     => $request->base_price,
            'quantity'       => $request->filled('quantity') ? (int) $request->quantity : null,
            'description'    => $request->description,
            'image'          => $imagePath,
            'original_image' => $originalImagePath,
        ]);

        // Save option groups and their choices
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
                    $choiceQtyRaw = $choiceData['quantity'] ?? '';
                    $choiceQty = ($choiceQtyRaw !== '' && $choiceQtyRaw !== null) ? (int) $choiceQtyRaw : null;
                    $choiceStatusInput = $choiceData['status'] ?? 'available';
                    // Only set unavailable if quantity is explicitly 0, not if it's empty
                    $choiceStatus = ($choiceQty !== null && $choiceQty <= 0)
                        ? 'unavailable'
                        : (in_array($choiceStatusInput, ['available', 'unavailable'])
                            ? $choiceStatusInput
                            : 'available');
                    $group->choices()->create([
                        'name'       => $choiceData['name'],
                        'price'      => is_numeric($choiceData['price'] ?? '') ? (float) $choiceData['price'] : 0,
                        'quantity'   => $choiceQty,
                        'sort_order' => $j,
                        'status'     => $choiceStatus,
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
            'base_price'  => ['nullable', 'numeric', 'min:0'],
            'quantity'    => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $data = $request->only(['name', 'category', 'base_price', 'quantity', 'description']);
        $data['quantity'] = $request->filled('quantity') ? (int) $request->quantity : null;

        $optionGroups = json_decode($request->input('option_groups', '[]'), true);
        if (!is_array($optionGroups)) {
            $optionGroups = [];
        }

        $optionError = $this->validateOptionGroupQuantities($optionGroups);
        if ($optionError) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $optionError], 422);
            }

            return redirect()->back()->withErrors(['option_groups' => $optionError])->withInput();
        }

        // Validate pricing: either base_price or choice prices must be filled
        $pricingError = $this->validatePricing($request->base_price, $optionGroups);
        if ($pricingError) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $pricingError], 422);
            }

            return redirect()->back()->withErrors(['pricing' => $pricingError])->withInput();
        }

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
                    $choiceQtyRaw = $choiceData['quantity'] ?? '';
                    $choiceQty = ($choiceQtyRaw !== '' && $choiceQtyRaw !== null) ? (int) $choiceQtyRaw : null;
                    $choiceStatusInput = $choiceData['status'] ?? 'available';
                    // Only set unavailable if quantity is explicitly 0, not if it's empty
                    $choiceStatus = ($choiceQty !== null && $choiceQty <= 0)
                        ? 'unavailable'
                        : (in_array($choiceStatusInput, ['available', 'unavailable'])
                            ? $choiceStatusInput
                            : 'available');
                    $group->choices()->create([
                        'name'       => $choiceData['name'],
                        'price'      => is_numeric($choiceData['price'] ?? '') ? (float) $choiceData['price'] : 0,
                        'quantity'   => $choiceQty,
                        'sort_order' => $j,
                        'status'     => $choiceStatus,
                    ]);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'item' => $item->fresh(['optionGroups.choices']),
            ]);
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
            'base_price'  => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        // Validate pricing: either base_price or choice prices must be filled
        $item->load('optionGroups.choices');
        $optionGroups = $item->optionGroups->map(fn($g) => [
            'choices' => $g->choices->map(fn($c) => [
                'name' => $c->name,
                'price' => $c->price,
            ])->toArray()
        ])->toArray();
        
        $pricingError = $this->validatePricing($request->base_price, $optionGroups);
        if ($pricingError) {
            return response()->json(['success' => false, 'message' => $pricingError], 422);
        }

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

        $optionError = $this->validateOptionGroupQuantities($optionGroups);
        if ($optionError) {
            return response()->json(['success' => false, 'message' => $optionError], 422);
        }

        // Validate pricing: either base_price or choice prices must be filled
        $pricingError = $this->validatePricing($item->base_price, $optionGroups);
        if ($pricingError) {
            return response()->json(['success' => false, 'message' => $pricingError], 422);
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
                $choiceQtyRaw = $choiceData['quantity'] ?? '';
                $choiceQty = ($choiceQtyRaw !== '' && $choiceQtyRaw !== null) ? (int) $choiceQtyRaw : null;
                $choiceStatusInput = $choiceData['status'] ?? 'available';
                // Only set unavailable if quantity is explicitly 0, not if it's empty
                $choiceStatus = ($choiceQty !== null && $choiceQty <= 0)
                    ? 'unavailable'
                    : (in_array($choiceStatusInput, ['available', 'unavailable'])
                        ? $choiceStatusInput
                        : 'available');
                $group->choices()->create([
                    'name'       => $choiceData['name'],
                    'price'      => is_numeric($choiceData['price'] ?? '') ? (float) $choiceData['price'] : 0,
                    'quantity'   => $choiceQty,
                    'sort_order' => $j,
                    'status'     => $choiceStatus,
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

    private function validateOptionGroupQuantities(array $optionGroups): ?string
    {
        foreach ($optionGroups as $groupData) {
            foreach (($groupData['choices'] ?? []) as $choiceData) {
                if (empty($choiceData['name'])) continue;

                // Quantity can be empty (will be updated later) or must be numeric if provided
                if (array_key_exists('quantity', $choiceData) && $choiceData['quantity'] !== '' && $choiceData['quantity'] !== null) {
                    if (!is_numeric($choiceData['quantity']) || (int) $choiceData['quantity'] < 0) {
                        return 'Please enter a valid quantity (0 or more) for every option choice.';
                    }
                }
            }
        }

        return null;
    }

    private function validatePricing($basePrice, array $optionGroups): ?string
    {
        // Check if base_price is filled
        $hasBasePrice = $basePrice !== null && $basePrice !== '' && is_numeric($basePrice);
        
        // Check named choices and their prices
        $hasNamedChoices = false;
        $hasPricesInChoices = true;
        foreach ($optionGroups as $groupData) {
            foreach (($groupData['choices'] ?? []) as $choiceData) {
                // Skip unnamed choices
                if (empty($choiceData['name'])) continue;

                $hasNamedChoices = true;
                // If choice has a name, it must have a price
                $choicePrice = $choiceData['price'] ?? null;
                if ($choicePrice === null || $choicePrice === '' || !is_numeric($choicePrice)) {
                    $hasPricesInChoices = false;
                    break 2;
                }
            }
        }
        
        // Valid if either base_price is filled OR named choices exist and all have prices
        if (!$hasBasePrice && !($hasNamedChoices && $hasPricesInChoices)) {
            return 'Please provide pricing: Fill the Base Price in Section 1, OR Fill the Price for all choices in Section 2.';
        }
        
        return null;
    }

    /**
     * Create a new custom category for a food truck.
     */
    public function createCategory(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name'  => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
        ]);

        $category = MenuCategory::create([
            'foodtruck_id' => $user->foodtruck_id,
            'name'         => $request->name,
            'color'        => $request->color,
            'sort_order'   => MenuCategory::where('foodtruck_id', $user->foodtruck_id)->count(),
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    /**
     * Get all categories for the current user's food truck.
     */
    public function getCategories()
    {
        $user = Auth::user();
        
        $categories = MenuCategory::where('foodtruck_id', $user->foodtruck_id)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    /**
     * Update an existing category (rename and/or change color).
     */
    public function updateCategory(Request $request, $categoryId)
    {
        $user = Auth::user();
        
        $category = MenuCategory::where('foodtruck_id', $user->foodtruck_id)
            ->findOrFail($categoryId);

        // Don't allow renaming of Uncategorized category
        if ($category->name === 'Uncategorized') {
            return response()->json(['success' => false, 'message' => 'Cannot rename the Uncategorized category'], 403);
        }

        $request->validate([
            'name'  => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
        ]);

        // Check if new name already exists (case-insensitive)
        $existingCategory = MenuCategory::where('foodtruck_id', $user->foodtruck_id)
            ->where('id', '!=', $categoryId)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->first();

        if ($existingCategory) {
            return response()->json(['success' => false, 'message' => 'A category with this name already exists'], 422);
        }

        $oldName = $category->name;
        $category->update([
            'name'  => $request->name,
            'color' => $request->color,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    /**
     * Delete a category and move its menu items to Uncategorized.
     */
    public function deleteCategory($categoryId)
    {
        $user = Auth::user();
        
        $category = MenuCategory::where('foodtruck_id', $user->foodtruck_id)
            ->findOrFail($categoryId);

        // Don't allow deleting default categories
        if (in_array($category->name, ['Foods', 'Drinks', 'Desserts', 'Uncategorized'])) {
            return response()->json(['success' => false, 'message' => 'Cannot delete default categories'], 403);
        }

        // Get or create Uncategorized category
        $uncategorized = MenuCategory::where('foodtruck_id', $user->foodtruck_id)
            ->where('name', 'Uncategorized')
            ->first();

        if (!$uncategorized) {
            $uncategorized = MenuCategory::create([
                'foodtruck_id' => $user->foodtruck_id,
                'name'         => 'Uncategorized',
                'color'        => 'gray',
                'sort_order'   => 0,
            ]);
        }

        // Move all menu items from this category to Uncategorized
        Menu::where('foodtruck_id', $user->foodtruck_id)
            ->where('category', $category->name)
            ->update(['category' => $uncategorized->name]);

        // Delete the category
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted. Menu items moved to Uncategorized.']);
    }
}
