@extends('layouts.customer.customer-layout')

@section('header_icon_class', 'fas fa-chart-line')
@section('header_title', 'Dashboard')

@section('content')

@php
    $user = Auth::user();
    $welcomeType = session('customer_welcome_type');
    $showWelcomeModal = in_array($welcomeType, ['new', 'back'], true);

    $welcomeModalTitle = $welcomeType === 'new'
        ? 'Welcome, ' . $user->full_name . '!'
        : 'Welcome back, ' . $user->full_name . '!';

    $welcomeModalMessage = $welcomeType === 'new'
        ? 'Hi ' . $user->full_name . ', your customer account has been created successfully. You can now explore food trucks and place your first order.'
        : 'Welcome back, ' . $user->full_name . '. Ready to order something delicious today?';

    $activeStatuses = ['pending', 'accepted', 'preparing', 'prepared', 'ready_for_pickup', 'delivery'];

    $orders = \App\Models\Order::with('foodTruck:id,foodtruck_name')
        ->where('customer_id', $user->id)
        ->latest()
        ->get();

    $orderSnapshotToken = sha1(
        $orders
            ->map(function ($order) {
                return implode('|', [
                    (string) $order->id,
                    (string) ($order->status ?? ''),
                    (string) optional($order->updated_at)->toIso8601String(),
                    (string) ($order->notes ?? ''),
                ]);
            })
            ->implode(';')
    );

    $purchasedOrderGroups = [];
    foreach ($orders as $order) {
        $menuRows = [];

        foreach (($order->items ?? []) as $item) {
            $status = $order->status ?? ($item['status'] ?? 'pending');
            $statusClass = match ($status) {
                'pending' => 'bg-amber-100 text-amber-700',
                'accepted' => 'bg-blue-100 text-blue-700',
                'preparing' => 'bg-indigo-100 text-indigo-700',
                'prepared' => 'bg-purple-100 text-purple-700',
                'ready_for_pickup' => 'bg-emerald-100 text-emerald-700',
                'delivery' => 'bg-cyan-100 text-cyan-700',
                'rejected' => 'bg-rose-100 text-rose-700',
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
                'item_total'      => (float) ($item['item_total'] ?? 0),
                'selected_choices'=> $choiceText !== '' ? $choiceText : '-',
                'status_label'    => str($status)->replace('_', ' ')->title(),
                'status_class'    => $statusClass,
            ];
        }

        if (!empty($menuRows)) {
            $status = (string) ($order->status ?? 'pending');
            $statusClass = match ($status) {
                'pending' => 'bg-amber-100 text-amber-700',
                'accepted' => 'bg-blue-100 text-blue-700',
                'preparing' => 'bg-indigo-100 text-indigo-700',
                'prepared' => 'bg-purple-100 text-purple-700',
                'ready_for_pickup' => 'bg-emerald-100 text-emerald-700',
                'delivery' => 'bg-cyan-100 text-cyan-700',
                'rejected' => 'bg-rose-100 text-rose-700',
                'done' => 'bg-gray-100 text-gray-700',
                default => 'bg-gray-100 text-gray-700',
            };

            $purchasedOrderGroups[] = [
                'order_id'       => $order->id,
                'truck_name'     => $order->foodTruck?->foodtruck_name ?? 'Food Truck',
                'items_count'    => count($menuRows),
                'total'          => (float) ($order->total ?? 0),
                'payment_method' => $order->payment_method ?? null,
                'order_type'     => $order->order_type ?? 'self_pickup',
                'table_number'   => $order->table_number ?? null,
                'created_at'     => $order->created_at?->toIso8601String(),
                'status'         => $status,
                'status_label'   => str($status)->replace('_', ' ')->title(),
                'status_class'   => $statusClass,
                'items'          => $menuRows,
            ];
        }
    }

    $activeOrders = $orders->whereIn('status', $activeStatuses)->values();
    $activeOrderGroups = collect($purchasedOrderGroups)
        ->whereIn('status', $activeStatuses)
        ->values()
        ->all();

    $rejectedOrders = $orders->where('status', 'rejected')->values();

    $rejectedOrderNotices = [];
    foreach ($rejectedOrders as $order) {
        $paymentMethodRaw = (string) ($order->payment_method ?? '');
        $isCashRefund = strcasecmp($paymentMethodRaw, 'cash') === 0;
        $paymentMethodLabel = $paymentMethodRaw !== ''
            ? ($isCashRefund ? 'Cash' : $paymentMethodRaw)
            : 'Not specified';
        $formattedTotal = number_format((float) ($order->total ?? 0), 2);

        $rejectedOrderNotices[] = [
            'order_id' => $order->id,
            'truck_name' => $order->foodTruck?->foodtruck_name ?? 'Food Truck',
            'total' => (float) ($order->total ?? 0),
            'payment_method' => $paymentMethodLabel,
            'created_at' => $order->created_at?->format('d M Y, h:i A'),
            'reason_message' => trim((string) ($order->notes ?? '')),
            'refund_message' => $isCashRefund
                ? "Please show your order receipt at our food truck to receive your cash refund of RM {$formattedTotal}."
                : "Your payment of RM {$formattedTotal} via {$paymentMethodLabel} will be refunded to the same payment method. Please allow a short processing period.",
        ];
    }
@endphp

<script>
function customerDashboardPage() {
    return {
        showWelcomeModal: @json($showWelcomeModal),
        showActiveOrdersModal: false,
        showOrderReceiptModal: false,
        selectedReceipt: null,
        orderSnapshotToken: @json($orderSnapshotToken),
        orderSnapshotPollingTimer: null,

        initLiveRefresh() {
            this.checkOrderSnapshot();
            this.orderSnapshotPollingTimer = setInterval(() => {
                this.checkOrderSnapshot();
            }, 1000);

            window.addEventListener('beforeunload', () => {
                if (this.orderSnapshotPollingTimer) {
                    clearInterval(this.orderSnapshotPollingTimer);
                }
            });
        },

        async checkOrderSnapshot() {
            try {
                const res = await fetch('{{ route('customer.order-status-snapshot') }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success || !data.snapshot_token) return;

                if (data.snapshot_token !== this.orderSnapshotToken) {
                    window.location.reload();
                }
            } catch (error) {
                console.error(error);
            }
        },

        viewOrderReceipt(group) {
            this.selectedReceipt = group;
            this.showOrderReceiptModal = true;
        },

        formatCurrency(amount) {
            return 'RM ' + parseFloat(amount || 0).toFixed(2);
        },

        formatDateTime(value) {
            if (!value) return '-';
            const dt = new Date(value);
            if (Number.isNaN(dt.getTime())) return value;
            return dt.toLocaleString();
        },

        paymentMethodLabel(method) {
            if (!method) return '-';
            return method === 'cash' ? 'Cash' : method;
        },

        statusLabel(status) {
            if (!status) return 'Pending';
            return status.replace(/_/g, ' ').replace(/\b\w/g, s => s.toUpperCase());
        },

        statusClass(status) {
            const map = {
                pending: 'bg-amber-100 text-amber-700',
                accepted: 'bg-blue-100 text-blue-700',
                preparing: 'bg-indigo-100 text-indigo-700',
                prepared: 'bg-purple-100 text-purple-700',
                ready_for_pickup: 'bg-emerald-100 text-emerald-700',
                delivery: 'bg-cyan-100 text-cyan-700',
                rejected: 'bg-rose-100 text-rose-700',
                done: 'bg-gray-100 text-gray-700',
            };
            return map[status] || 'bg-gray-100 text-gray-700';
        },

        escapeForPrint(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        printSelectedReceipt() {
            if (!this.selectedReceipt) return;

            const receipt = this.selectedReceipt;
            const paymentMethod = this.paymentMethodLabel(receipt.payment_method);
            const paymentTime = this.formatDateTime(receipt.created_at);
            const total = this.formatCurrency(receipt.total);

            const rows = (receipt.items || []).map((item) => {
                const basePrice = parseFloat(item.base_price || 0) > 0
                    ? this.formatCurrency(item.base_price)
                    : '-';

                return `
                    <tr>
                        <td>${this.escapeForPrint(item.menu_name || 'Menu Item')}</td>
                        <td>${this.escapeForPrint(basePrice)}</td>
                        <td>${this.escapeForPrint(item.quantity || 1)}</td>
                        <td>${this.escapeForPrint(this.formatCurrency(item.item_total || 0))}</td>
                    </tr>
                `;
            }).join('');

            const printableHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8" />
                    <title>Receipt #${this.escapeForPrint(String(receipt.order_id || 0).padStart(4, '0'))}</title>
                    <style>
                        body { font-family: Arial, sans-serif; color: #1f2937; padding: 24px; }
                        h1 { margin: 0 0 12px; font-size: 20px; }
                        .meta { margin-bottom: 16px; }
                        .meta p { margin: 4px 0; font-size: 13px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { border: 1px solid #e5e7eb; padding: 8px; font-size: 12px; text-align: left; }
                        th { background: #f9fafb; }
                        .total { margin-top: 12px; font-weight: 700; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <h1>Purchased Order Receipt</h1>
                    <div class="meta">
                        <p><strong>Truck Name:</strong> ${this.escapeForPrint(receipt.truck_name || 'Food Truck')}</p>
                        <p><strong>Order ID:</strong> #${this.escapeForPrint(String(receipt.order_id || 0).padStart(4, '0'))}</p>
                        <p><strong>Payment Method:</strong> ${this.escapeForPrint(paymentMethod)}</p>
                        <p><strong>Payment Time:</strong> ${this.escapeForPrint(paymentTime)}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Order Name</th>
                                <th>Base Price</th>
                                <th>Quantity</th>
                                <th>Total Final Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                    <p class="total">Total Final Price: ${this.escapeForPrint(total)}</p>
                </body>
                </html>
            `;

            const printWindow = window.open('', '_blank', 'width=900,height=700');
            if (!printWindow) return;

            printWindow.document.open();
            printWindow.document.write(printableHtml);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
            }, 200);
        },
    };
}
</script>

<div x-data="customerDashboardPage()" x-init="initLiveRefresh()" class="p-6">
    <div x-show="showWelcomeModal"
         style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[98] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">

        <div @click.away="showWelcomeModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">

            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hand-sparkles text-amber-500 text-xl"></i>
                </div>
                <h2 class="text-xl font-black text-gray-900 mb-2">{{ $welcomeModalTitle }}</h2>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $welcomeModalMessage }}</p>
            </div>

            <div class="px-6 pb-6 flex justify-center">
                <button @click="showWelcomeModal = false"
                        class="px-6 py-2.5 bg-slate-900 hover:bg-amber-500 text-white font-black text-xs rounded-xl transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-full mx-auto space-y-5">

        <!-- Welcome -->
        <div class="flex items-center justify-between animate-fade-in-up">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard</h1>
                <p class="text-gray-500 mt-1 font-medium text-sm">What are you craving today, {{ $user->full_name }}?</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 stagger-children">
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

        @if (count($rejectedOrderNotices) > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-hidden">
                <div class="p-5 border-b border-rose-100 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-widest">Rejected Orders & Refund Notice</h3>
                        <p class="text-[11px] text-gray-500 mt-1">These orders were rejected and are now in refund handling.</p>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full whitespace-nowrap">
                        {{ count($rejectedOrderNotices) }} {{ count($rejectedOrderNotices) > 1 ? 'orders' : 'order' }}
                    </span>
                </div>

                <div class="p-5 space-y-3">
                    @foreach ($rejectedOrderNotices as $notice)
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-black text-gray-900">Order #{{ str_pad((string) $notice['order_id'], 4, '0', STR_PAD_LEFT) }}</p>
                                    <p class="text-[11px] text-gray-600 mt-0.5">{{ $notice['truck_name'] }} @if($notice['created_at']) • {{ $notice['created_at'] }} @endif</p>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-wide bg-rose-100 text-rose-700 px-2.5 py-1 rounded-full whitespace-nowrap">Rejected</span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-700">
                                <p><span class="font-black text-gray-600">Amount:</span> RM {{ number_format($notice['total'], 2) }}</p>
                                <p><span class="font-black text-gray-600">Payment Method:</span> {{ $notice['payment_method'] }}</p>
                            </div>

                            @if (!empty($notice['reason_message']))
                                <p class="mt-3 text-xs font-semibold text-rose-800 leading-relaxed">{{ $notice['reason_message'] }}</p>
                            @endif
                            <p class="mt-2 text-xs font-semibold text-rose-700 leading-relaxed">{{ $notice['refund_message'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Call to Action -->
        <div class="bg-[#0f172a] rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
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

        <!-- Purchased Orders -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-800 uppercase text-xs tracking-widest">Purchased Orders</h3>
                    <p class="text-[11px] text-gray-500 mt-1">All paid orders with downloadable-style receipt details.</p>
                </div>
                <span class="text-[10px] font-black uppercase tracking-wide bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full whitespace-nowrap">
                    {{ count($purchasedOrderGroups) }} {{ count($purchasedOrderGroups) > 1 ? 'orders' : 'order' }}
                </span>
            </div>

            @if (count($purchasedOrderGroups) === 0)
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-receipt text-4xl mb-3 opacity-20"></i>
                    <p class="text-sm">No purchased orders yet.</p>
                </div>
            @else
                {{-- Mobile: Card layout --}}
                <div class="md:hidden divide-y divide-gray-100">
                    @foreach ($purchasedOrderGroups as $group)
                        <div class="p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-black text-gray-900">#{{ str_pad((string) $group['order_id'], 4, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full whitespace-nowrap {{ $group['status_class'] }}">
                                    {{ $group['status_label'] }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600"><i class="fas fa-truck mr-1"></i>{{ $group['truck_name'] }}</p>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Total</span>
                                <span class="font-bold text-gray-900">RM {{ number_format($group['total'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Payment</span>
                                <span class="text-gray-700">{{ $group['payment_method'] === 'cash' ? 'Cash' : ($group['payment_method'] ?? '-') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Date</span>
                                <span class="text-gray-700">{{ $group['created_at'] ? \Carbon\Carbon::parse($group['created_at'])->format('d M Y, h:i A') : '-' }}</span>
                            </div>
                            <button @click="viewOrderReceipt(@json($group))"
                                    class="w-full mt-1 px-3 py-2 bg-amber-100 hover:bg-amber-200 text-amber-700 font-black text-[10px] rounded-lg transition-all text-center">
                                <i class="fas fa-receipt mr-1"></i>Show Receipt
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop: Table layout --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide border-b border-gray-100">
                            <tr>
                                <th class="px-3 py-3 text-left font-black">Order ID</th>
                                <th class="px-3 py-3 text-left font-black">Truck Name</th>
                                <th class="px-3 py-3 text-left font-black">Total Final Price</th>
                                <th class="px-3 py-3 text-left font-black">Payment Method</th>
                                <th class="px-3 py-3 text-left font-black">Payment Time</th>
                                <th class="px-3 py-3 text-left font-black">Status</th>
                                <th class="px-3 py-3 text-left font-black">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($purchasedOrderGroups as $group)
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-3 py-3 text-gray-800 font-bold">#{{ str_pad((string) $group['order_id'], 4, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $group['truck_name'] }}</td>
                                    <td class="px-3 py-3 text-gray-700 whitespace-nowrap">RM {{ number_format($group['total'], 2) }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $group['payment_method'] === 'cash' ? 'Cash' : ($group['payment_method'] ?? '-') }}</td>
                                    <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $group['created_at'] ? \Carbon\Carbon::parse($group['created_at'])->format('d M Y, h:i A') : '-' }}</td>
                                    <td class="px-3 py-3">
                                        <span class="text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full whitespace-nowrap {{ $group['status_class'] }}">
                                            {{ $group['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <button @click="viewOrderReceipt(@json($group))"
                                                class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-700 font-black text-[10px] rounded-lg transition-all whitespace-nowrap">
                                            <i class="fas fa-receipt mr-1"></i>Show Receipt
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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
                                    <table class="w-full text-xs">
                                        <thead class="bg-white text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                            <tr>
                                                <th class="px-3 py-2.5 text-left font-black">Menu</th>
                                                <th class="px-3 py-2.5 text-left font-black">Base Price</th>
                                                <th class="px-3 py-2.5 text-left font-black">Qty</th>
                                                <th class="px-3 py-2.5 text-left font-black">Selected Choices</th>
                                                <th class="px-3 py-2.5 text-left font-black">Status</th>
                                                <th class="px-3 py-2.5 text-left font-black">Receipt</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($group['items'] as $item)
                                                <tr class="hover:bg-gray-50/70 transition-colors">
                                                    <td class="px-3 py-3 text-gray-800 font-bold">{{ $item['menu_name'] }}</td>
                                                    <td class="px-3 py-3 text-gray-700 whitespace-nowrap">RM {{ number_format($item['base_price'], 2) }}</td>
                                                    <td class="px-3 py-3 text-gray-700">{{ $item['quantity'] }}</td>
                                                    <td class="px-3 py-3 text-gray-600">{{ $item['selected_choices'] }}</td>
                                                    <td class="px-3 py-3">
                                                        <span class="text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full whitespace-nowrap {{ $item['status_class'] }}">
                                                            {{ $item['status_label'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <button @click="viewOrderReceipt(@json($group))"
                                                                class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-700 font-black text-[10px] rounded-lg transition-all whitespace-nowrap">
                                                            <i class="fas fa-receipt mr-1"></i>Show Receipt
                                                        </button>
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

            <div class="flex justify-end px-6 py-4 border-t border-gray-100">
                <button @click="showActiveOrdersModal = false"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-amber-500 text-white font-black text-xs rounded-xl transition-all">
                    Close
                </button>
            </div>

        </div>
    </div>

    <div x-show="showOrderReceiptModal"
         style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="showOrderReceiptModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="font-black text-gray-900 text-base">Purchased Order Receipt</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Payment and order details from your completed checkout.</p>
                </div>
                <button @click="showOrderReceiptModal = false"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="px-6 py-5 max-h-[75vh] overflow-y-auto space-y-4">
                <template x-if="selectedReceipt">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Truck Name</p>
                                <p class="text-sm font-black text-gray-800" x-text="selectedReceipt.truck_name || 'Food Truck'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Order ID</p>
                                <p class="text-sm font-black text-gray-800" x-text="'#' + String(selectedReceipt.order_id || 0).padStart(4, '0')"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Payment Method</p>
                                <p class="text-sm font-black text-gray-800" x-text="paymentMethodLabel(selectedReceipt.payment_method)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Payment Time</p>
                                <p class="text-sm font-black text-gray-800" x-text="formatDateTime(selectedReceipt.created_at)"></p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-2xl border border-gray-100">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide border-b border-gray-100">
                                    <tr>
                                        <th class="px-3 py-3 text-left font-black">Order Name</th>
                                        <th class="px-3 py-3 text-left font-black">Base Price</th>
                                        <th class="px-3 py-3 text-left font-black">Quantity</th>
                                        <th class="px-3 py-3 text-left font-black">Total Final Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(item, idx) in (selectedReceipt.items || [])" :key="idx">
                                        <tr class="bg-white">
                                            <td class="px-3 py-3 text-gray-800 font-bold" x-text="item.menu_name || 'Menu Item'"></td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap" x-text="parseFloat(item.base_price || 0) > 0 ? formatCurrency(item.base_price) : '-' "></td>
                                            <td class="px-3 py-3 text-gray-700" x-text="item.quantity || 1"></td>
                                            <td class="px-3 py-3 text-gray-700 whitespace-nowrap" x-text="formatCurrency(item.item_total)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                            <span class="text-xs font-black text-gray-500 uppercase tracking-widest">Total Final Price</span>
                            <span class="text-lg font-black text-gray-900" x-text="formatCurrency(selectedReceipt.total)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <button @click="printSelectedReceipt()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-gray-200 hover:border-amber-300 hover:bg-amber-50 text-gray-700 font-black text-[11px] rounded-lg transition-all">
                    <i class="fas fa-print"></i>
                    Print Receipt
                </button>

                <button @click="showOrderReceiptModal = false"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-amber-500 text-white font-black text-xs rounded-xl transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
