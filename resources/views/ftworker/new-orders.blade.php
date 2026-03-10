<x-ftworker-layout>

@php $user = Auth::user(); @endphp

<div class="flex flex-col h-full" x-data="newOrdersPage()">

    <!-- Top Header -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
            <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center text-gray-400 space-x-2">
                <span class="w-5 flex justify-center"><i class="fas fa-clipboard-list text-sm"></i></span>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-gray-700">New Orders</span>
            </div>
        </div>

        <div class="flex items-center space-x-6">
            <!-- Poll indicator -->
            <div class="hidden sm:flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Live
            </div>
            <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-bell"></i>
                <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            </button>
            <div class="h-6 w-px bg-gray-200"></div>
            <div class="flex items-center group cursor-pointer" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <div class="text-right mr-3 hidden lg:block">
                    <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ $user->full_name }}</p>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-orange-600 bg-orange-50 px-2 py-0.5 rounded">
                        FT Worker
                    </span>
                </div>
                <div class="relative">
                    <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md group-hover:scale-105 transition-all">
                        {{ substr($user->full_name, 0, 1) }}
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Body -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-full mx-auto space-y-5">

            <!-- Page Heading -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">New Orders</h1>
                    <p class="text-gray-500 mt-1 font-medium text-sm">Manage and update incoming customer orders.</p>
                </div>
            </div>

            <!-- Two-Panel Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">

                <!-- ═══════════════════════════════════════ -->
                <!-- LEFT PANEL — Pending Orders             -->
                <!-- ═══════════════════════════════════════ -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

                    <!-- Panel Header -->
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                        <div>
                            <h2 class="text-base font-black text-gray-900">Pending Orders</h2>
                            <p class="text-xs text-gray-400 font-medium mt-0.5">Shared queue — first to accept claims the order</p>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-1.5">
                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                  :class="pendingOrders.length > 0 ? 'bg-amber-400 animate-pulse' : 'bg-gray-300'"></span>
                            <span class="text-xs font-black text-gray-600"
                                  x-text="pendingOrders.length + ' waiting'"></span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div x-show="pendingOrders.length === 0"
                         class="flex flex-col items-center justify-center py-14 text-center px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <i class="fas fa-clock text-xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500">No pending orders</p>
                        <p class="text-xs text-gray-400 mt-1">New orders from customers will appear here.</p>
                    </div>

                    <!-- Table -->
                    <div x-show="pendingOrders.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order #</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Items</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Type</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Time</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="order in pendingOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50/60 transition-colors">

                                        <!-- Order # -->
                                        <td class="px-4 py-3">
                                            <span class="font-black text-gray-800 text-sm"
                                                  x-text="'#' + String(order.id).padStart(4, '0')"></span>
                                        </td>

                                        <!-- Customer -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                                     x-text="order.customer_name.charAt(0)"></div>
                                                <span class="font-semibold text-gray-700 text-xs" x-text="order.customer_name"></span>
                                            </div>
                                        </td>

                                        <!-- Items -->
                                        <td class="px-4 py-3 max-w-[140px]">
                                            <div class="space-y-0.5">
                                                <template x-for="(item, idx) in order.items" :key="idx">
                                                    <div class="text-xs text-gray-600 truncate"
                                                         x-text="typeof item === 'string' ? item : (item.quantity + '× ' + item.name)"></div>
                                                </template>
                                            </div>
                                        </td>

                                        <!-- Total -->
                                        <td class="px-4 py-3">
                                            <span class="font-black text-gray-900 text-sm"
                                                  x-text="'RM ' + parseFloat(order.total).toFixed(2)"></span>
                                        </td>

                                        <!-- Order Type -->
                                        <td class="px-4 py-3">
                                            <template x-if="order.order_type === 'table'">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-purple-100 text-purple-700 text-[10px] font-black uppercase tracking-wide">
                                                    <i class="fas fa-chair text-[9px]"></i>
                                                    <span x-text="'T' + (order.table_number ?? '?')"></span>
                                                </span>
                                            </template>
                                            <template x-if="order.order_type !== 'table'">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-wide">
                                                    <i class="fas fa-person-walking text-[9px]"></i>
                                                    Pickup
                                                </span>
                                            </template>
                                        </td>

                                        <!-- Time -->
                                        <td class="px-4 py-3">
                                            <span class="text-xs text-gray-400 font-medium"
                                                  x-text="formatTime(order.created_at)"></span>
                                        </td>

                                        <!-- Accept Button -->
                                        <td class="px-4 py-3">
                                            <button @click="acceptOrder(order)"
                                                    :disabled="accepting === order.id"
                                                    class="flex flex-col items-center justify-center py-2 px-3 rounded-xl transition-all duration-150 bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60 disabled:cursor-wait">
                                                <template x-if="accepting !== order.id">
                                                    <i class="fas fa-hand-pointer text-xs mb-0.5"></i>
                                                </template>
                                                <template x-if="accepting === order.id">
                                                    <i class="fas fa-spinner fa-spin text-xs mb-0.5"></i>
                                                </template>
                                                <span class="text-[9px] font-black uppercase tracking-wide leading-none">Accept</span>
                                            </button>
                                        </td>

                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- ═══════════════════════════════════════ -->
                <!-- RIGHT PANEL — My Order Activity         -->
                <!-- ═══════════════════════════════════════ -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

                    <!-- Panel Header -->
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                        <div>
                            <h2 class="text-base font-black text-gray-900">My Order Activity</h2>
                            <p class="text-xs text-gray-400 font-medium mt-0.5">Orders you've accepted — update their status below</p>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-1.5">
                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                  :class="myOrders.length > 0 ? 'bg-emerald-400 animate-pulse' : 'bg-gray-300'"></span>
                            <span class="text-xs font-black text-gray-600"
                                  x-text="myOrders.length + ' active'"></span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div x-show="myOrders.length === 0"
                         class="flex flex-col items-center justify-center py-14 text-center px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <i class="fas fa-clipboard-check text-xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500">No accepted orders yet</p>
                        <p class="text-xs text-gray-400 mt-1">Accept a pending order from the left to start working on it.</p>
                    </div>

                    <!-- Table -->
                    <div x-show="myOrders.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order #</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Items</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Update Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="order in myOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50/60 transition-colors">

                                        <!-- Order # -->
                                        <td class="px-4 py-3">
                                            <span class="font-black text-gray-800 text-sm"
                                                  x-text="'#' + String(order.id).padStart(4, '0')"></span>
                                        </td>

                                        <!-- Customer -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                                     x-text="order.customer_name.charAt(0)"></div>
                                                <span class="font-semibold text-gray-700 text-xs" x-text="order.customer_name"></span>
                                            </div>
                                        </td>

                                        <!-- Items -->
                                        <td class="px-4 py-3 max-w-[140px]">
                                            <div class="space-y-0.5">
                                                <template x-for="(item, idx) in order.items" :key="idx">
                                                    <div class="text-xs text-gray-600 truncate"
                                                         x-text="typeof item === 'string' ? item : (item.quantity + '× ' + item.name)"></div>
                                                </template>
                                            </div>
                                        </td>

                                        <!-- Total -->
                                        <td class="px-4 py-3">
                                            <span class="font-black text-gray-900 text-sm"
                                                  x-text="'RM ' + parseFloat(order.total).toFixed(2)"></span>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wide whitespace-nowrap"
                                                  :class="statusBadgeClass(order.status)"
                                                  x-text="statusLabel(order.status)"></span>
                                        </td>

                                        <!-- Action Buttons -->
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">

                                                <!-- Accepted -->
                                                <button @click="setStatus(order, 'accepted')"
                                                        :disabled="order.status === 'accepted' || updatingStatus === order.id"
                                                        :class="order.status === 'accepted'
                                                            ? 'bg-blue-500 text-white cursor-default shadow-sm'
                                                            : 'bg-gray-100 text-gray-500 hover:bg-blue-50 hover:text-blue-600'"
                                                        class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all duration-150 disabled:opacity-70">
                                                    <i class="fas fa-check text-xs mb-0.5"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-wide leading-none">Accepted</span>
                                                </button>

                                                <!-- Preparing -->
                                                <button @click="setStatus(order, 'preparing')"
                                                        :disabled="order.status === 'preparing' || updatingStatus === order.id"
                                                        :class="order.status === 'preparing'
                                                            ? 'bg-amber-400 text-white cursor-default shadow-sm'
                                                            : 'bg-gray-100 text-gray-500 hover:bg-amber-50 hover:text-amber-500'"
                                                        class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all duration-150 disabled:opacity-70">
                                                    <i class="fas fa-fire text-xs mb-0.5"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-wide leading-none">Preparing</span>
                                                </button>

                                                <!-- Prepared -->
                                                <button @click="setStatus(order, 'prepared')"
                                                        :disabled="order.status === 'prepared' || updatingStatus === order.id"
                                                        :class="order.status === 'prepared'
                                                            ? 'bg-emerald-500 text-white cursor-default shadow-sm'
                                                            : 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600'"
                                                        class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all duration-150 disabled:opacity-70">
                                                    <i class="fas fa-check-double text-xs mb-0.5"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-wide leading-none">Prepared</span>
                                                </button>

                                                <!-- Ready For Pickup -->
                                                <button @click="setStatus(order, 'ready_for_pickup')"
                                                        :disabled="order.status === 'ready_for_pickup' || updatingStatus === order.id"
                                                        :class="order.status === 'ready_for_pickup'
                                                            ? 'bg-purple-500 text-white cursor-default shadow-sm'
                                                            : 'bg-gray-100 text-gray-500 hover:bg-purple-50 hover:text-purple-600'"
                                                        class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all duration-150 disabled:opacity-70">
                                                    <i class="fas fa-bag-shopping text-xs mb-0.5"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-wide leading-none">Ready</span>
                                                </button>

                                                <!-- Delivery -->
                                                <button @click="setStatus(order, 'delivery')"
                                                        :disabled="order.status === 'delivery' || updatingStatus === order.id"
                                                        :class="order.status === 'delivery'
                                                            ? 'bg-cyan-500 text-white cursor-default shadow-sm'
                                                            : 'bg-gray-100 text-gray-500 hover:bg-cyan-50 hover:text-cyan-600'"
                                                        class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all duration-150 disabled:opacity-70">
                                                    <i class="fas fa-motorcycle text-xs mb-0.5"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-wide leading-none">Delivery</span>
                                                </button>

                                                <!-- Done -->
                                                <button @click="setStatus(order, 'done')"
                                                        :disabled="order.status === 'done' || updatingStatus === order.id"
                                                        :class="order.status === 'done'
                                                            ? 'bg-slate-700 text-white cursor-default shadow-sm'
                                                            : 'bg-gray-100 text-gray-500 hover:bg-slate-100 hover:text-slate-700'"
                                                        class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all duration-150 disabled:opacity-70">
                                                    <i class="fas fa-flag-checkered text-xs mb-0.5"></i>
                                                    <span class="text-[9px] font-black uppercase tracking-wide leading-none">Done</span>
                                                </button>

                                            </div>
                                        </td>

                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                </div>
                <!-- end right panel -->

            </div>
        </div>
    </div>

</div>

<script>
function newOrdersPage() {
    return {
        pendingOrders: [],
        myOrders: [],
        accepting: null,
        updatingStatus: null,

        async init() {
            await Promise.all([this.loadPending(), this.loadMyActivity()]);
            // Poll every 5 seconds
            setInterval(() => this.loadPending(), 5000);
            setInterval(() => this.loadMyActivity(), 5000);
        },

        redirectToRestrictedDashboard() {
            window.location.href = '{{ route('ftworker.dashboard') }}';
        },

        async loadPending() {
            try {
                const res = await fetch('/orders/pending', { headers: { 'Accept': 'application/json' } });
                if (res.status === 403) {
                    this.redirectToRestrictedDashboard();
                    return;
                }
                if (res.ok) this.pendingOrders = await res.json();
            } catch (e) { console.error(e); }
        },

        async loadMyActivity() {
            try {
                const res = await fetch('/orders/my-activity', { headers: { 'Accept': 'application/json' } });
                if (res.status === 403) {
                    this.redirectToRestrictedDashboard();
                    return;
                }
                if (res.ok) this.myOrders = await res.json();
            } catch (e) { console.error(e); }
        },

        async acceptOrder(order) {
            if (this.accepting === order.id) return;
            this.accepting = order.id;
            try {
                const res = await fetch(`/orders/${order.id}/accept`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                });
                if (res.status === 403) {
                    this.redirectToRestrictedDashboard();
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    // Optimistically remove from pending, add to my activity
                    this.pendingOrders = this.pendingOrders.filter(o => o.id !== order.id);
                    this.myOrders.unshift(data.order);
                } else {
                    // Already taken — refresh pending list
                    await this.loadPending();
                }
            } catch (e) { console.error(e); }
            this.accepting = null;
        },

        async setStatus(order, status) {
            if (this.updatingStatus === order.id) return;
            this.updatingStatus = order.id;
            try {
                const res = await fetch(`/orders/${order.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ status }),
                });
                if (res.status === 403) {
                    this.redirectToRestrictedDashboard();
                    return;
                }
                const data = await res.json();
                if (data.success) order.status = data.order.status;
            } catch (e) { console.error(e); }
            this.updatingStatus = null;
        },

        statusBadgeClass(status) {
            const map = {
                accepted:        'bg-blue-100 text-blue-700',
                preparing:       'bg-amber-100 text-amber-700',
                prepared:        'bg-emerald-100 text-emerald-700',
                ready_for_pickup:'bg-purple-100 text-purple-700',
                delivery:        'bg-cyan-100 text-cyan-700',
                done:            'bg-slate-100 text-slate-700',
            };
            return map[status] ?? 'bg-gray-100 text-gray-600';
        },

        statusLabel(status) {
            const map = {
                pending:          'Pending',
                accepted:         'Accepted',
                preparing:        'Preparing',
                prepared:         'Prepared',
                ready_for_pickup: 'Ready For Pickup',
                delivery:         'Delivery',
                done:             'Done',
            };
            return map[status] ?? status;
        },

        formatTime(datetime) {
            if (!datetime) return '';
            const d = new Date(datetime);
            return d.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>

</x-ftworker-layout>
