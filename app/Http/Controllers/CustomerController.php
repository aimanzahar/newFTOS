<?php

namespace App\Http\Controllers;

use App\Models\FoodTruck;
use App\Models\Menu;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Show all approved, operational food trucks.
     */
    public function browse()
    {
        $systemOperational = SystemSetting::where('key', 'is_operational')->value('value');

        $trucks = FoodTruck::where('status', 'approved')
            ->where('is_operational', true)
            ->withCount(['menus' => fn($q) => $q->where('status', 'available')])
            ->orderBy('foodtruck_name')
            ->get();

        return view('customer.browse-trucks', compact('trucks', 'systemOperational'));
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

        return DB::transaction(function () use ($request, $truck, $user) {
            $menuIds = collect($request->items)
                ->map(fn ($rawItem) => $rawItem['menu_id'] ?? null)
                ->filter(fn ($menuId) => is_numeric($menuId))
                ->map(fn ($menuId) => (int) $menuId)
                ->unique()
                ->values();

            $menusById = Menu::with('optionGroups.choices')
                ->whereIn('id', $menuIds)
                ->where('foodtruck_id', $truck->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $processedItems = [];
            $requestedMenuQuantities = [];

            foreach ($request->items as $rawItem) {
                $menuId = is_numeric($rawItem['menu_id'] ?? null) ? (int) $rawItem['menu_id'] : null;
                $menu = $menuId ? ($menusById[$menuId] ?? null) : null;

                if (!$menu) {
                    continue;
                }

                $qty = max(1, (int) $rawItem['quantity']);

                if ($menu->quantity !== null) {
                    $availableQty = max(0, (int) $menu->quantity);
                    $alreadyRequested = $requestedMenuQuantities[$menu->id] ?? 0;
                    $requestedTotalForMenu = $alreadyRequested + $qty;

                    if ($requestedTotalForMenu > $availableQty) {
                        $remainingQty = max(0, $availableQty - $alreadyRequested);

                        return response()->json([
                            'success' => false,
                            'message' => $remainingQty > 0
                                ? 'Only ' . $remainingQty . ' quantity left for ' . $menu->name . '.'
                                : $menu->name . ' is out of stock.',
                        ], 422);
                    }

                    $requestedMenuQuantities[$menu->id] = $requestedTotalForMenu;
                }

                $choiceExtra = 0;
                $selectedChoices = [];
                $selectedChoiceIds = collect(is_array($rawItem['selected_choices'] ?? null) ? $rawItem['selected_choices'] : [])
                    ->filter(fn ($choiceId) => is_numeric($choiceId))
                    ->map(fn ($choiceId) => (int) $choiceId)
                    ->unique()
                    ->values()
                    ->all();

                $availableChoicesById = [];
                foreach ($menu->optionGroups as $group) {
                    foreach ($group->choices as $choice) {
                        if ($choice->status !== 'available') {
                            continue;
                        }

                        $availableChoicesById[(int) $choice->id] = [
                            'choice' => $choice,
                            'group' => $group,
                        ];
                    }
                }

                $selectedChoiceMetaByGroup = [];
                foreach ($selectedChoiceIds as $choiceId) {
                    $choiceMeta = $availableChoicesById[$choiceId] ?? null;

                    if (!$choiceMeta) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid or unavailable option selected for ' . $menu->name . '.',
                        ], 422);
                    }

                    $groupId = (int) $choiceMeta['group']->id;
                    $selectedChoiceMetaByGroup[$groupId][] = $choiceMeta;
                }

                foreach ($menu->optionGroups as $group) {
                    if ($group->selection_type !== 'single') {
                        continue;
                    }

                    $selectedCount = count($selectedChoiceMetaByGroup[(int) $group->id] ?? []);
                    if ($selectedCount !== 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Please select exactly one option for "' . $group->name . '" in ' . $menu->name . '.',
                        ], 422);
                    }
                }

                foreach ($selectedChoiceMetaByGroup as $groupSelections) {
                    foreach ($groupSelections as $selection) {
                        $choice = $selection['choice'];
                        $group = $selection['group'];
                        $choicePrice = is_numeric($choice->price) ? (float) $choice->price : 0;

                        $choiceExtra += $choicePrice;
                        $selectedChoices[] = [
                            'choice_id'   => $choice->id,
                            'group_name'  => $group->name,
                            'choice_name' => $choice->name,
                            'price'       => $choicePrice,
                        ];
                    }
                }

                $basePrice = is_numeric($menu->base_price) ? (float) $menu->base_price : 0;
                $itemTotal = ($basePrice + $choiceExtra) * $qty;
                $total += $itemTotal;

                $processedItems[] = [
                    'menu_id'          => $menu->id,
                    'name'             => $menu->name,
                    'quantity'         => $qty,
                    'base_price'       => $basePrice,
                    'selected_choices' => $selectedChoices,
                    'status'           => 'pending',
                    'item_total'       => $itemTotal,
                ];
            }

            if (empty($processedItems)) {
                return response()->json(['success' => false, 'message' => 'No valid items in order.'], 422);
            }

            foreach ($requestedMenuQuantities as $menuId => $orderedQty) {
                $menu = $menusById[$menuId] ?? null;

                if (!$menu || $menu->quantity === null) {
                    continue;
                }

                $newQuantity = max(0, ((int) $menu->quantity) - $orderedQty);
                $menu->quantity = $newQuantity;

                if ($newQuantity === 0) {
                    $menu->status = 'unavailable';
                }

                $menu->save();
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

            $orderPayload = $order->toArray();
            $orderPayload['truck_name'] = $truck->foodtruck_name;
            $orderPayload['payment_time'] = $order->created_at?->toIso8601String();

            return response()->json(['success' => true, 'order' => $orderPayload]);
        });
    }
}
