<?php

namespace App\Http\Controllers;

use App\Models\FoodTruck;
use App\Models\Menu;
use App\Models\MenuChoice;
use App\Models\Order;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Show all approved, operational food trucks.
     */
    public function browse()
    {
        $trucks = FoodTruck::where('status', 'approved')
            ->where('is_operational', true)
            ->withCount(['menus' => fn($q) => $q->where('status', 'available')])
            ->orderBy('foodtruck_name')
            ->get();

        return view('customer.browse-trucks', compact('trucks'));
    }

    /**
     * Show a specific truck's available menu items.
     */
    public function truckMenu($id)
    {
        $truck = FoodTruck::where('id', $id)
            ->where('status', 'approved')
            ->where('is_operational', true)
            ->firstOrFail();

        $menuItems = Menu::with(['optionGroups' => function ($q) {
                $q->orderBy('sort_order');
            }, 'optionGroups.choices' => function ($q) {
                $q->where('status', 'available')->orderBy('sort_order');
            }])
            ->where('foodtruck_id', $id)
            ->where('status', 'available')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('customer.truck-menu', compact('truck', 'menuItems'));
    }

    /**
     * Place an order. Recalculates total server-side.
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'foodtruck_id'          => 'required|exists:food_trucks,id',
            'items'                 => 'required|array|min:1',
            'items.*.menu_id'       => 'required|integer',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.selected_choices' => 'nullable|array',
            'order_type'            => 'required|in:self_pickup,table',
            'table_number'          => 'nullable|integer|min:1',
            'payment_method'        => 'required|string|max:100',
        ]);

        $user = $request->user();

        $truck = FoodTruck::where('id', $request->foodtruck_id)
            ->where('status', 'approved')
            ->where('is_operational', true)
            ->firstOrFail();

        $total = 0;
        $processedItems = [];

        foreach ($request->items as $rawItem) {
            $menu = Menu::with('optionGroups.choices')
                ->where('id', $rawItem['menu_id'])
                ->where('foodtruck_id', $truck->id)
                ->where('status', 'available')
                ->first();

            if (!$menu) continue;

            $qty = max(1, (int) $rawItem['quantity']);
            $choiceExtra = 0;
            $selectedChoices = [];

            foreach (($rawItem['selected_choices'] ?? []) as $choiceId) {
                $choice = MenuChoice::with('group')->find($choiceId);
                if ($choice && $choice->group && $choice->group->menu_id == $menu->id && $choice->status === 'available') {
                    $choiceExtra += (float) $choice->price;
                    $selectedChoices[] = [
                        'choice_id'   => $choice->id,
                        'group_name'  => $choice->group->name,
                        'choice_name' => $choice->name,
                        'price'       => (float) $choice->price,
                    ];
                }
            }

            $itemTotal = ((float) $menu->base_price + $choiceExtra) * $qty;
            $total += $itemTotal;

            $processedItems[] = [
                'menu_id'          => $menu->id,
                'name'             => $menu->name,
                'quantity'         => $qty,
                'base_price'       => (float) $menu->base_price,
                'selected_choices' => $selectedChoices,
                'status'           => 'pending',
                'item_total'       => $itemTotal,
            ];
        }

        if (empty($processedItems)) {
            return response()->json(['success' => false, 'message' => 'No valid items in order.'], 422);
        }

        $order = Order::create([
            'foodtruck_id'  => $truck->id,
            'customer_id'   => $user->id,
            'customer_name' => $user->full_name,
            'items'         => $processedItems,
            'total'         => $total,
            'status'        => 'pending',
            'order_type'    => $request->order_type,
            'table_number'  => $request->order_type === 'table' ? (int) $request->table_number : null,
            'payment_method'=> $request->payment_method,
        ]);

        return response()->json(['success' => true, 'order' => $order]);
    }
}
