<x-ftadmin-layout>

@php $user = Auth::user(); @endphp

<div class="flex flex-col h-full overflow-hidden" x-data="newOrdersPage()">

    <!-- Page Body -->
    <div class="flex-1 overflow-hidden p-6">
        <div class="max-w-full mx-auto h-full flex flex-col gap-5">

            <!-- Page Heading -->
            <div class="flex items-center justify-between animate-fade-in-up">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">New Orders</h1>
                    <p class="text-gray-500 mt-1 font-medium text-sm">Manage and update incoming customer orders.</p>
                </div>
            </div>

            <!-- Two-Panel Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start flex-1 min-h-0 stagger-children animate-fade-in">

                <!-- ═══════════════════════════════════════ -->
                <!-- LEFT PANEL — Pending Orders             -->
                <!-- ═══════════════════════════════════════ -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full min-h-0">

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
                        class="flex-1 min-h-0 flex flex-col items-center justify-center py-14 text-center px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <i class="fas fa-clock text-xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500">No pending orders</p>
                        <p class="text-xs text-gray-400 mt-1">New orders from customers will appear here.</p>
                    </div>

                    <!-- Table -->
                    <div x-show="pendingOrders.length > 0" class="flex-1 min-h-0 overflow-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order #</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order Details</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Type</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="order in pendingOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50/60 transition-colors cursor-pointer align-top"
                                        @click="openAcceptModal(order)">

                                        <!-- Order # -->
                                        <td class="px-4 py-3 align-top">
                                            <span class="font-black text-gray-800 text-sm"
                                                  x-text="'#' + String(order.id).padStart(4, '0')"></span>
                                        </td>

                                        <!-- Order Details -->
                                        <td class="px-4 py-3 min-w-[330px]">
                                            <div class="space-y-2">
                                                <template x-for="(item, idx) in normalizeItems(order.items)" :key="idx">
                                                    <div class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">
                                                        <p class="text-xs font-bold text-gray-700"
                                                           x-text="(item.quantity || 1) + '× ' + (item.name || 'Item')"></p>
                                                        <p class="text-[11px] text-gray-500 mt-0.5"
                                                           x-text="'Unit: RM ' + calcUnitPrice(item).toFixed(2) + ' · Line: RM ' + calcLineTotal(item).toFixed(2)"></p>
                                                        <template x-if="choiceSummary(item)">
                                                            <p class="text-[10px] text-purple-600 font-medium mt-1"
                                                               x-text="choiceSummary(item)"></p>
                                                        </template>
                                                    </div>
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

                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- ═══════════════════════════════════════ -->
                <!-- RIGHT PANEL — My Orders                 -->
                <!-- ═══════════════════════════════════════ -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full min-h-0">

                    <!-- Panel Header -->
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                        <div>
                            <h2 class="text-base font-black text-gray-900">My Orders</h2>
                            <p class="text-xs text-gray-400 font-medium mt-0.5">Track active work and completed orders</p>
                        </div>
                        <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-1.5">
                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                  :class="activeOrders.length > 0 ? 'bg-emerald-400 animate-pulse' : 'bg-gray-300'"></span>
                            <span class="text-xs font-black text-gray-600"
                                  x-text="activeOrders.length + ' active'"></span>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                        <button @click="activityTab = 'active'"
                                class="px-3 py-1.5 text-[11px] font-black uppercase tracking-wide rounded-lg transition-all"
                                :class="activityTab === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                            Order Activity
                        </button>
                        <button @click="activityTab = 'completed'"
                                class="px-3 py-1.5 text-[11px] font-black uppercase tracking-wide rounded-lg transition-all"
                                :class="activityTab === 'completed' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                            Completed Orders
                        </button>
                    </div>

                    <!-- Active Empty State -->
                    <div x-show="activityTab === 'active' && activeOrders.length === 0"
                        class="flex-1 min-h-0 flex flex-col items-center justify-center py-14 text-center px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <i class="fas fa-clipboard-check text-xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500">No accepted orders yet</p>
                        <p class="text-xs text-gray-400 mt-1">Accept a pending order from the left to start working on it.</p>
                    </div>

                    <!-- Active Table -->
                    <div x-show="activityTab === 'active' && activeOrders.length > 0" class="flex-1 min-h-0 overflow-auto">
                        <table class="w-full table-fixed text-sm">
                            <colgroup>
                                <col class="w-[14%]">
                                <col class="w-[38%]">
                                <col class="w-[16%]">
                                <col class="w-[20%]">
                                <col class="w-[12%]">
                            </colgroup>
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order #</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order Details</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Update Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="order in activeOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50/60 transition-colors align-top">

                                        <!-- Order # -->
                                        <td class="px-4 py-3 align-top">
                                            <span class="font-black text-gray-800 text-sm"
                                                  x-text="'#' + String(order.id).padStart(4, '0')"></span>
                                        </td>

                                        <!-- Order Details -->
                                        <td class="px-4 py-3 min-w-[330px]">
                                            <div class="space-y-2">
                                                <template x-for="(item, idx) in normalizeItems(order.items)" :key="idx">
                                                    <div class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">
                                                        <p class="text-xs font-bold text-gray-700"
                                                           x-text="(item.quantity || 1) + '× ' + (item.name || 'Item')"></p>
                                                        <p class="text-[11px] text-gray-500 mt-0.5"
                                                           x-text="'Unit: RM ' + calcUnitPrice(item).toFixed(2) + ' · Line: RM ' + calcLineTotal(item).toFixed(2)"></p>
                                                        <template x-if="choiceSummary(item)">
                                                            <p class="text-[10px] text-purple-600 font-medium mt-1"
                                                               x-text="choiceSummary(item)"></p>
                                                        </template>
                                                    </div>
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
                                            <span class="inline-flex w-36 justify-center items-center px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wide whitespace-nowrap"
                                                  :class="statusBadgeClass(order.status)"
                                                  x-text="statusLabel(order.status)"></span>
                                        </td>

                                        <!-- Action Menu -->
                                        <td class="px-4 py-3 text-center">
                                            <div class="relative inline-block">
                                                <button type="button"
                                                        @click.stop="activeStatusActionMenu = activeStatusActionMenu === order.id ? null : order.id"
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                                                    <i class="fas fa-ellipsis-v text-xs"></i>
                                                </button>

                                                <div x-show="activeStatusActionMenu === order.id"
                                                     @click.away="activeStatusActionMenu = null"
                                                     x-transition:enter="transition ease-out duration-100"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     style="display:none;"
                                                     class="absolute right-full top-1/2 -translate-y-1/2 mr-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 w-44 z-50">

                                                    <button type="button"
                                                            @click.stop="setStatus(order, 'accepted'); activeStatusActionMenu = null"
                                                            :disabled="order.status === 'accepted' || updatingStatus === order.id"
                                                            :class="order.status === 'accepted' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors disabled:opacity-60">
                                                        <i class="fas fa-check w-3 text-center"></i>
                                                        <span>Accepted</span>
                                                    </button>

                                                    <button type="button"
                                                            @click.stop="setStatus(order, 'preparing'); activeStatusActionMenu = null"
                                                            :disabled="order.status === 'preparing' || updatingStatus === order.id"
                                                            :class="order.status === 'preparing' ? 'text-amber-600 bg-amber-50' : 'text-gray-600 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors disabled:opacity-60">
                                                        <i class="fas fa-fire w-3 text-center"></i>
                                                        <span>Preparing</span>
                                                    </button>

                                                    <button type="button"
                                                            @click.stop="setStatus(order, 'prepared'); activeStatusActionMenu = null"
                                                            :disabled="order.status === 'prepared' || updatingStatus === order.id"
                                                            :class="order.status === 'prepared' ? 'text-emerald-600 bg-emerald-50' : 'text-gray-600 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors disabled:opacity-60">
                                                        <i class="fas fa-check-double w-3 text-center"></i>
                                                        <span>Prepared</span>
                                                    </button>

                                                    <button type="button"
                                                            @click.stop="setStatus(order, 'ready_for_pickup'); activeStatusActionMenu = null"
                                                            :disabled="order.status === 'ready_for_pickup' || updatingStatus === order.id"
                                                            :class="order.status === 'ready_for_pickup' ? 'text-purple-600 bg-purple-50' : 'text-gray-600 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors disabled:opacity-60">
                                                        <i class="fas fa-bag-shopping w-3 text-center"></i>
                                                        <span>Ready</span>
                                                    </button>

                                                    <button type="button"
                                                            @click.stop="setStatus(order, 'delivery'); activeStatusActionMenu = null"
                                                            :disabled="order.status === 'delivery' || updatingStatus === order.id"
                                                            :class="order.status === 'delivery' ? 'text-cyan-600 bg-cyan-50' : 'text-gray-600 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors disabled:opacity-60">
                                                        <i class="fas fa-motorcycle w-3 text-center"></i>
                                                        <span>Out For Delivery</span>
                                                    </button>

                                                    <div class="border-t border-gray-100 my-1"></div>

                                                    <button type="button"
                                                            @click.stop="setStatus(order, 'done'); activeStatusActionMenu = null"
                                                            :disabled="order.status === 'done' || updatingStatus === order.id"
                                                            :class="order.status === 'done' ? 'text-slate-700 bg-slate-100' : 'text-gray-600 hover:bg-gray-50'"
                                                            class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors disabled:opacity-60">
                                                        <i class="fas fa-flag-checkered w-3 text-center"></i>
                                                        <span>Done</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>

                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Completed Empty State -->
                    <div x-show="activityTab === 'completed' && completedOrders.length === 0"
                        class="flex-1 min-h-0 flex flex-col items-center justify-center py-14 text-center px-6">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <i class="fas fa-flag-checkered text-xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500">No completed orders yet</p>
                        <p class="text-xs text-gray-400 mt-1">Orders marked as done will appear here with full details.</p>
                    </div>

                    <!-- Completed Table -->
                    <div x-show="activityTab === 'completed' && completedOrders.length > 0" class="flex-1 min-h-0 overflow-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order #</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Completed At</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Order Details</th>
                                    <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="order in completedOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50/60 transition-colors align-top">
                                        <td class="px-4 py-3">
                                            <span class="font-black text-gray-800 text-sm"
                                                  x-text="'#' + String(order.id).padStart(4, '0')"></span>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span class="text-xs text-gray-500 font-semibold"
                                                  x-text="formatDateTime(order.updated_at)"></span>
                                        </td>

                                        <td class="px-4 py-3 min-w-[330px]">
                                            <div class="space-y-2">
                                                <template x-for="(item, idx) in normalizeItems(order.items)" :key="idx">
                                                    <div class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">
                                                        <p class="text-xs font-bold text-gray-700"
                                                           x-text="(item.quantity || 1) + '× ' + (item.name || 'Item')"></p>
                                                        <p class="text-[11px] text-gray-500 mt-0.5"
                                                           x-text="'Unit: RM ' + calcUnitPrice(item).toFixed(2) + ' · Line: RM ' + calcLineTotal(item).toFixed(2)"></p>
                                                        <template x-if="choiceSummary(item)">
                                                            <p class="text-[10px] text-purple-600 font-medium mt-1"
                                                               x-text="choiceSummary(item)"></p>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span class="font-black text-gray-900 text-sm"
                                                  x-text="'RM ' + parseFloat(order.total).toFixed(2)"></span>
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

    <div x-show="showInfoModal"
     style="display:none;"
     class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100"
         @click.away="showInfoModal = false">
        <div class="h-1.5 w-full bg-orange-400"></div>
        <div class="p-6">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mb-4">
                <i class="fas fa-id-card text-lg"></i>
            </div>
            <h3 class="text-base font-black text-gray-900 mb-2" x-text="infoModalTitle"></h3>
            <p class="text-sm text-gray-500 leading-relaxed" x-text="infoModalMessage"></p>
            <button @click="showInfoModal = false"
                    class="mt-5 w-full py-2.5 px-4 bg-slate-900 hover:bg-blue-600 text-white font-black text-xs rounded-xl transition-all uppercase tracking-wide">
                Understood
            </button>
        </div>
    </div>

</div>

    <div x-show="showAcceptModal"
         style="display:none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="closeAcceptModal()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-white/20">

            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-gray-900">Confirm Accept Order</h2>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">Review and confirm this order claim.</p>
                </div>
                <button @click="closeAcceptModal()"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="px-6 py-5">
                <div class="flex items-center gap-3 p-4 rounded-2xl border border-blue-100 bg-blue-50">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-hand-pointer text-sm"></i>
                    </div>
                    <p class="text-sm font-bold text-blue-800"
                       x-text="acceptingOrder ? ('Do you want to accept this -' + String(acceptingOrder.id).padStart(4, '0') + '-?') : ''"></p>
                </div>
            </div>

            <div class="px-6 pb-6 flex gap-3">
                <button @click="closeAcceptModal()"
                        class="flex-1 py-3 px-3 rounded-2xl bg-gray-100 text-gray-500 hover:bg-gray-200 transition-all font-black text-sm">
                    Cancel
                </button>
                <button @click="confirmAcceptOrder()"
                        :disabled="acceptingOrder && accepting === acceptingOrder.id"
                        class="flex-1 py-3 px-3 rounded-2xl bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all disabled:opacity-60 disabled:cursor-wait font-black text-sm">
                    Confirm
                </button>
            </div>
        </div>

    </div>

</div>

<script>
function newOrdersPage() {
    return {
        pendingOrders: [],
        myOrders: [],
        activityTab: 'active',
        accepting: null,
        updatingStatus: null,
        showInfoModal: false,
        showAcceptModal: false,
        acceptingOrder: null,
        activeStatusActionMenu: null,
        infoModalTitle: '',
        infoModalMessage: '',

        get activeOrders() {
            return this.myOrders.filter(order => order.status !== 'done');
        },

        get completedOrders() {
            return this.myOrders.filter(order => order.status === 'done');
        },

        async init() {
            await Promise.all([this.loadPending(), this.loadMyActivity()]);
            // Poll every 1 second
            setInterval(() => {
                this.loadPending();
                this.loadMyActivity();
            }, 1000);
        },

        redirectToRestrictedDashboard() {
            window.location.href = '{{ route('ftadmin.dashboard') }}';
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
                    const denied = await res.json().catch(() => ({}));
                    if (denied.code === 'punch_card_required') {
                        this.openInfoModal(
                            'Punch Card Required',
                            denied.message || 'Please punch in before accepting new orders to begin your shift.'
                        );
                        return;
                    }
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

        openAcceptModal(order) {
            if (!order || this.accepting === order.id) return;
            this.acceptingOrder = order;
            this.showAcceptModal = true;
        },

        closeAcceptModal() {
            this.showAcceptModal = false;
            this.acceptingOrder = null;
        },

        async confirmAcceptOrder() {
            if (!this.acceptingOrder) return;
            const order = this.acceptingOrder;
            this.closeAcceptModal();
            await this.acceptOrder(order);
        },

        openInfoModal(title, message) {
            this.infoModalTitle = title;
            this.infoModalMessage = message;
            this.showInfoModal = true;
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
                delivery:         'Out For Delivery',
                done:             'Done',
            };
            return map[status] ?? status;
        },

        normalizeItems(items) {
            return Array.isArray(items) ? items : [];
        },

        calcUnitPrice(item) {
            if (!item || typeof item === 'string') return 0;

            const basePrice = Number(item.base_price ?? 0) || 0;
            const selectedChoices = Array.isArray(item.selected_choices) ? item.selected_choices : [];
            const choiceTotal = selectedChoices.reduce((total, choice) => {
                return total + (Number(choice?.price ?? 0) || 0);
            }, 0);

            return basePrice + choiceTotal;
        },

        calcLineTotal(item) {
            if (!item || typeof item === 'string') return 0;

            const storedTotal = Number(item.item_total);
            if (Number.isFinite(storedTotal)) {
                return storedTotal;
            }

            const qty = Number(item.quantity ?? 1) || 1;
            return this.calcUnitPrice(item) * qty;
        },

        choiceSummary(item) {
            if (!item || typeof item === 'string') return '';

            const selectedChoices = Array.isArray(item.selected_choices) ? item.selected_choices : [];
            if (selectedChoices.length === 0) return '';

            const labels = selectedChoices
                .map(choice => {
                    const groupName = choice?.group_name || 'Option';
                    const choiceName = choice?.choice_name || '';
                    const extra = Number(choice?.price ?? 0) || 0;
                    const extraLabel = extra > 0 ? ` (+RM ${extra.toFixed(2)})` : '';
                    return `${groupName}: ${choiceName}${extraLabel}`;
                })
                .filter(Boolean);

            return labels.length ? `Options: ${labels.join(', ')}` : '';
        },

        formatTime(datetime) {
            if (!datetime) return '';
            const d = new Date(datetime);
            return d.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' });
        },

        formatDateTime(datetime) {
            if (!datetime) return '';
            const d = new Date(datetime);
            return d.toLocaleString('en-MY', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
}
</script>

</x-ftadmin-layout>
