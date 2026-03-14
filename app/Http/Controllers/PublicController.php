<?php

namespace App\Http\Controllers;

use App\Models\FoodTruck;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

class PublicController extends Controller
{
    public function landing()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        $trucks = FoodTruck::where('status', 'approved')
            ->where('is_operational', true)
            ->withCount(['menus' => fn($q) => $q->where('status', 'available')])
            ->orderBy('foodtruck_name')
            ->get();

        return view('public.landing', compact('trucks'));
    }

    public function truckMenu($id)
    {
        if (Auth::check()) {
            return redirect()->route('customer.truck-menu', $id);
        }

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

        return view('public.truck-menu', compact('truck', 'menuItems'));
    }
}
