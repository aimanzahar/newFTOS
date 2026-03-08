@extends('layouts.customer.customer-layout')

@section('header_icon_class', 'fas fa-chart-line')
@section('header_title', 'Dashboard')

@section('content')

@php
    $user = Auth::user();

    $activeStatuses = ['pending', 'accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery'];

    $orders = \App\Models\Order::with('foodTruck:id,foodtruck_name')
        ->where('customer_id', $user->id)
        ->latest()
        ->get();

    $activeOrders = $orders->whereIn('status', $activeStatuses)->values();

    $activeOrderGroups = [];
    foreach ($activeOrders as $order) {
        $menuRows = [];

        foreach (($order->items ?? []) as $item) {
            $status = $item['status'] ?? $order->status;
            $statusClass = match ($status) {
                'pending' => 'bg-amber-100 text-amber-700',
                'accepted' => 'bg-blue-100 text-blue-700',
                'preparing' => 'bg-indigo-100 text-indigo-700',
                'prepared' => 'bg-purple-100 text-purple-700',
                'ready_for_pickup' => 'bg-emerald-100 text-emerald-700',
                'delivery' => 'bg-cyan-100 text-cyan-700',
                'done' => 'bg-gray-100 text-gray-700',
                default => 'bg-gray-100 text-gray-700',
            };

            $choiceText = collect($item['selected_choices'] ?? [])->map(function ($choice) {
                $name = $choice['choice_name'] ?? 'Choice';
                $price = isset($choice['price']) ? (float) $choice['price'] : 0;
                return $name . ($price > 0 ? ' (+RM ' . number_format($price, 2) . ')' : '');
            })->implode(', ');

            $menuRows[] = [
                'menu_name'       => $item['name'] ?? 'Menu Item',
                'base_price'      => (float) ($item['base_price'] ?? 0),
                'quantity'        => (int) ($item['quantity'] ?? 1),
                'selected_choices'=> $choiceText !== '' ? $choiceText : '-',
                'status_label'    => str($status)->replace('_', ' ')->title(),
                'status_class'    => $statusClass,
            ];
        }

        if (!empty($menuRows)) {
            $activeOrderGroups[] = [
                'order_id'   => $order->id,
                'truck_name' => $order->foodTruck?->foodtruck_name ?? 'Food Truck',
                'items_count'=> count($menuRows),
                'items'      => $menuRows,
            ];
        }
    }
@endphp

<script>
function customerDashboardPage() {
    return {
        showActiveOrdersModal: false,
    };
}
</script>

<div x-data="customerDashboardPage()" class="p-6">
    <div class="max-w-full mx-auto space-y-5">

        <!-- Welcome -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard</h1>
                <p class="text-gray-500 mt-1 font-medium text-sm">What are you craving today, {{ $user->full_name }}?</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button @click="showActiveOrdersModal = true"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center space-x-4 text-left cursor-pointer transition-all hover:border-blue-300 hover:shadow-md group">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-blue-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Active Orders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $activeOrders->count() }}</p>
                    <p class="text-[10px] font-black text-blue-600 mt-0.5">Click to view active order details</p>
                </div>
            </button>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-history text-amber-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $orders->count() }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-star text-emerald-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Reviews Given</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-[#0f172a] rounded-2xl shadow-xl overflow-hidden">
            <div class="px-8 py-10 md:flex items-center justify-between">
                <div class="mb-6 md:mb-0">
                    <h2 class="text-xl font-bold text-white mb-1">Ready to eat?</h2>
                    <p class="text-slate-400 text-sm">Browse through the best food trucks and order now.</p>
                </div>
                <a href="{{ route('customer.browse') }}" class="inline-flex items-center px-5 py-2.5 bg-amber-400 hover:bg-amber-300 text-amber-900 font-bold rounded-xl transition duration-200 shadow-lg text-sm">
                    <i class="fas fa-store mr-2"></i>
                    Explore Food Trucks
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 uppercase text-xs tracking-widest">Recent Orders</h3>
                <a href="#" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">View All</a>
            </div>
            <div class="p-12 text-center text-gray-400">
                <i class="fas fa-receipt text-4xl mb-3 opacity-20"></i>
                <p class="text-sm">No recent orders yet. Time to grab some lunch!</p>
            </div>
        </div>

    </div>

    <div x-show="showActiveOrdersModal"
         style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[90] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="showActiveOrdersModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-6xl rounded-3xl shadow-2xl overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div class="pr-4">
                    <h3 class="font-black text-gray-900 text-base">Active Order Details</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Each checkout is separated below, with status for each menu item.</p>
                </div>
                <button @click="showActiveOrdersModal = false"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                @if (count($activeOrderGroups) === 0)
                    <div class="py-10 text-center text-gray-400">
                        <i class="fas fa-receipt text-4xl mb-3 opacity-20"></i>
                        <p class="text-sm">No active order menus right now.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($activeOrderGroups as $group)
                            <div class="rounded-2xl border border-gray-100 overflow-hidden bg-white shadow-sm">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-gray-900 leading-tight">
                                            Order #{{ str_pad((string) $group['order_id'], 4, '0', STR_PAD_LEFT) }}
                                        </p>
                                        <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                            {{ $group['truck_name'] }}
                                        </p>
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-wide bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full whitespace-nowrap">
                                        {{ $group['items_count'] }} menu {{ $group['items_count'] > 1 ? 'items' : 'item' }}
                                    </span>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-[880px] w-full text-xs">
                                        <thead class="bg-white text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                            <tr>
                                                <th class="px-3 py-2.5 text-left font-black">Menu</th>
                                                <th class="px-3 py-2.5 text-left font-black">Base Price</th>
                                                <th class="px-3 py-2.5 text-left font-black">Qty</th>
                                                <th class="px-3 py-2.5 text-left font-black">Selected Options</th>
                                                <th class="px-3 py-2.5 text-left font-black">Menu Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($group['items'] as $item)
                                                <tr class="hover:bg-gray-50/70 transition-colors">
                                                    <td class="px-3 py-3 text-gray-800 font-bold">{{ $item['menu_name'] }}</td>
                                                    <td class="px-3 py-3 text-gray-700 whitespace-nowrap">RM {{ number_format($item['base_price'], 2) }}</td>
                                                    <td class="px-3 py-3 text-gray-700">{{ $item['quantity'] }}</td>
                                                    <td class="px-3 py-3 text-gray-600 min-w-[260px]">{{ $item['selected_choices'] }}</td>
                                                    <td class="px-3 py-3">
                                                        <span class="text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full whitespace-nowrap {{ $item['status_class'] }}">
                                                            {{ $item['status_label'] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection
