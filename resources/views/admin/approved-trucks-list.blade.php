<x-app-layout>
    {{-- This slot override helps prevent Breeze from injecting headers into the top --}}
    <x-slot name="header"></x-slot>

    <div class="fixed inset-0 flex h-screen bg-gray-50 font-sans antialiased overflow-hidden z-50">

        <!-- Sidebar Component (Fixed Position) -->
        @include('layouts.admin.admin-left-navbar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

            <!-- Fixed Top Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">

                <!-- Left Side: Mobile Toggle & Breadcrumbs -->
                <div class="flex items-center">
                    <button
                        id="openSidebar"
                        class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3"
                    >
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="hidden md:flex items-center text-gray-400 space-x-2">
                        <i class="fas fa-home text-sm"></i>
                        <span class="text-gray-300">/</span>
                        <span class="text-sm font-bold text-gray-700">
                            Approved Trucks
                        </span>
                    </div>
                </div>

                <!-- Right Side: Notifications & Profile -->
                <div class="flex items-center space-x-6">
                    <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                    </button>

                    <div class="h-6 w-px bg-gray-200"></div>

                    <div
                        class="flex items-center group cursor-pointer"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    >
                        <div class="text-right mr-3 hidden lg:block">
                            <p class="text-sm font-bold text-gray-800 leading-none mb-1">
                                {{ Auth::user()->full_name }}
                            </p>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                Super Admin
                            </span>
                        </div>

                        <div class="relative">
                            <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md group-hover:scale-105 transition-all">
                                {{ substr(Auth::user()->full_name, 0, 1) }}
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content Section -->
            <main class="flex-1 overflow-y-auto p-6 lg:p-10 scroll-smooth">
                <div class="max-w-7xl mx-auto">

                    <!-- Page Header Area -->
                    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-black text-gray-900 tracking-tight">
                                Approved Trucks
                            </h1>
                            <p class="text-gray-500 mt-1 font-medium">
                                Currently operating food trucks on the platform.
                            </p>
                        </div>

                        <div class="mt-4 sm:mt-0">
                            <a
                                href="{{ route('admin.dashboard') }}"
                                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 transition flex items-center"
                            >
                                <i class="fas fa-arrow-left mr-2 text-blue-500"></i>
                                Back to Overview
                            </a>
                        </div>
                    </div>

                    <!-- Success Alert -->
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Rejected Alert -->
                    @if(session('rejected'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
                            <i class="fas fa-times-circle mr-3"></i>
                            {{ session('rejected') }}
                        </div>
                    @endif

                    <!-- Table Content -->
                    <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full leading-normal table-fixed">
                                <colgroup>
                                    <col class="w-[8%]">
                                    <col class="w-[20%]">
                                    <col class="w-[18%]">
                                    <col class="w-[26%]">
                                    <col class="w-[14%]">
                                    <col class="w-[14%]">
                                </colgroup>
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        <th class="px-6 py-4">ID</th>
                                        <th class="px-6 py-4">Truck Name</th>
                                        <th class="px-6 py-4">License No.</th>
                                        <th class="px-6 py-4">Description</th>
                                        <th class="px-6 py-4">Approved On</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-50">
                                    @forelse($approvedRegistrations as $truck)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 text-xs font-bold text-gray-400">
                                                #{{ $truck->id }}
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-gray-800">
                                                        {{ $truck->foodtruck_name }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                                                {{ $truck->business_license_no }}
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="max-w-xs">
                                                    <p
                                                        class="text-xs text-gray-600 line-clamp-2 leading-relaxed"
                                                        title="{{ $truck->foodtruck_desc }}"
                                                    >
                                                        {{ $truck->foodtruck_desc ?? 'No description provided.' }}
                                                    </p>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                                {{ $truck->updated_at->format('M d, Y') }}
                                                <span class="block text-[10px] text-gray-400 uppercase tracking-tighter">
                                                    {{ $truck->updated_at->diffForHumans() }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">

                                                    <!-- See More Details Button -->
                                                    <button
                                                        type="button"
                                                        onclick="openTruckModal({{ $truck->id }})"
                                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                                    >
                                                        See More Details
                                                    </button>

                                                    <!-- Manage Button (Optional) -->
                                                    <button
                                                        type="button"
                                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                                        onclick="alert('Management options coming soon')"
                                                    >
                                                        Manage
                                                    </button>

                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-20 text-center text-gray-400 bg-white">
                                                <div class="flex flex-col items-center">
                                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                                                        <i class="fas fa-truck text-3xl"></i>
                                                    </div>
                                                    <p class="text-lg font-bold text-gray-500">
                                                        No Approved Trucks
                                                    </p>
                                                    <p class="text-sm text-gray-400">
                                                        No food trucks have been approved yet.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($approvedRegistrations->hasPages())
                        <div class="mt-8">
                            {{ $approvedRegistrations->links() }}
                        </div>
                    @endif

                </div>
            </main>
        </div>

        {{-- Safe truck data store: browser never parses this as JS --}}
        @php
            $trucksForJs = [];
            foreach($approvedRegistrations as $truck) {
                $truck->load(['owner', 'staff', 'menus.optionGroups.choices']);
                $trucksForJs[$truck->id] = $truck;
            }
        @endphp
        <script id="trucks-json-data" type="application/json">{!! json_encode($trucksForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             TRUCK DETAILS MODAL
             â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div id="truckDetailModal"
             style="display:none;"
             class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[70] flex items-center justify-center p-4"
             onclick="handleModalBackdropClick(event)">

            <div id="truckModalPanel"
                 class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh] overflow-hidden">

                <!-- â”€â”€ Modal Header â”€â”€ -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <div>
                        <h2 id="modalTruckName" class="text-base font-black text-gray-900"></h2>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">Food Truck Details & Information</p>
                    </div>
                    <button onclick="closeModal()"
                            class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- â”€â”€ Tab Bar â”€â”€ -->
                <div class="flex gap-2 px-6 py-4 border-b border-gray-100 flex-shrink-0 bg-gray-50/50">
                    <button id="tab-btn-truck"   onclick="switchTab('truck')"  class="modal-tab-btn active-tab">
                        <i class="fas fa-info-circle text-sm mr-2"></i><span class="font-bold text-sm">Truck Details</span>
                    </button>
                    <button id="tab-btn-owner"   onclick="switchTab('owner')"  class="modal-tab-btn">
                        <i class="fas fa-user text-sm mr-2"></i><span class="font-bold text-sm">Owner & Staff</span>
                    </button>
                    <button id="tab-btn-menu"    onclick="switchTab('menu')"   class="modal-tab-btn">
                        <i class="fas fa-utensils text-sm mr-2"></i><span class="font-bold text-sm">Menu List</span>
                    </button>
                    <button id="tab-btn-orders"  onclick="switchTab('orders')" class="modal-tab-btn">
                        <i class="fas fa-receipt text-sm mr-2"></i><span class="font-bold text-sm">Orders</span>
                    </button>
                </div>

                <!-- â”€â”€ Tab Panels â”€â”€ -->
                <div class="flex-1 overflow-y-auto">

                    <!-- â”€ Tab 1: Truck Details â”€ -->
                    <div id="tab-truck" class="p-6 space-y-4 w-full">

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Truck Name</label>
                            <div class="flex items-center gap-2">
                                <div id="detail-truck-name-display" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <p id="detail-truck-name" class="text-sm font-bold text-gray-800"></p>
                                </div>
                                <input type="text" id="detail-truck-name-edit" class="hidden flex-1 px-4 py-2.5 bg-white border border-blue-300 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                <button onclick="toggleEditMode('truckName')" class="px-3 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl transition-all flex-shrink-0" title="Edit truck name">
                                    <i class="fas fa-pen text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Business License Number</label>
                            <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl">
                                <p id="detail-license" class="text-sm font-bold font-mono text-gray-700"></p>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Truck Description</label>
                            <div class="flex items-start gap-2">
                                <div id="detail-desc-display" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <p id="detail-desc" class="text-sm font-medium text-gray-700 leading-relaxed"></p>
                                </div>
                                <textarea id="detail-desc-edit" class="hidden flex-1 px-4 py-2.5 bg-white border border-blue-300 rounded-xl text-sm font-medium resize-none focus:ring-2 focus:ring-blue-500 outline-none" rows="4"></textarea>
                                <button onclick="toggleEditMode('description')" class="px-3 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl transition-all flex-shrink-0 mt-1" title="Edit description">
                                    <i class="fas fa-pen text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Operational Status</label>
                            <div id="detail-status-box" class="px-4 py-3 rounded-xl border flex items-center gap-3">
                                <div id="detail-status-dot" class="w-2.5 h-2.5 rounded-full flex-shrink-0"></div>
                                <span id="detail-status-text" class="text-sm font-bold"></span>
                            </div>
                        </div>

                    </div>

                    <!-- â”€ Tab 2: Owner & Staff â”€ -->
                    <div id="tab-owner" class="p-6 space-y-5 w-full" style="display:none;">

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 flex items-center gap-2">
                                <i class="fas fa-user-tie text-blue-500"></i> Truck Owner / Admin
                            </p>
                            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 space-y-3">
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1 block">Full Name</label>
                                    <p id="detail-owner-name" class="text-sm font-bold text-gray-800"></p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1 block">Email Address</label>
                                    <p id="detail-owner-email" class="text-sm font-medium font-mono text-gray-700"></p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1 block">Phone Number</label>
                                    <p id="detail-owner-phone" class="text-sm font-medium text-gray-700"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 flex items-center gap-2">
                                <i class="fas fa-users text-emerald-500"></i> Workers / Staff
                            </p>
                            <div id="detail-staff-list" class="space-y-3"></div>
                            <div id="detail-staff-empty" class="text-center py-10 bg-gray-50 border border-gray-200 rounded-2xl" style="display:none;">
                                <i class="fas fa-users text-2xl text-gray-300 mb-2 block"></i>
                                <p class="text-xs font-bold text-gray-400">No staff members assigned</p>
                            </div>
                        </div>

                    </div>

                    <!-- â”€ Tab 3: Menu List â”€ -->
                    <div id="tab-menu" class="p-6 w-full" style="display:none;">

                        <!-- Category filter pills -->
                        <div id="menu-category-filters" class="flex flex-wrap gap-2 mb-5"></div>

                        <!-- Cards grid -->
                        <div id="detail-menu-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

                        <!-- Empty state -->
                        <div id="detail-menu-empty" class="text-center py-16 flex flex-col items-center justify-center" style="display:none;">
                            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                <i class="fas fa-utensils text-xl text-gray-300"></i>
                            </div>
                            <p class="text-xs font-bold text-gray-400">No menu items available</p>
                        </div>

                    </div>

                    <!-- â”€ Tab 4: Orders â”€ -->
                    <div id="tab-orders" class="p-6 w-full" style="display:none;">

                        <!-- Loading state -->
                        <div id="orders-loading" class="text-center py-16 flex flex-col items-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-blue-400 mb-3"></i>
                            <p class="text-xs font-bold text-gray-400">Loading ordersâ€¦</p>
                        </div>

                        <!-- Orders list -->
                        <div id="orders-list" class="space-y-3" style="display:none;"></div>

                        <!-- Empty state -->
                        <div id="orders-empty" class="text-center py-16 flex flex-col items-center justify-center" style="display:none;">
                            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                <i class="fas fa-receipt text-xl text-gray-300"></i>
                            </div>
                            <p class="text-xs font-bold text-gray-400">No orders found for this truck</p>
                        </div>

                    </div>

                </div>

                <!-- Modal Footer with Update & Cancel Buttons -->
                <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0 flex items-center justify-end gap-3 bg-gray-50">
                    <button id="modal-close-btn" onclick="closeModal()" class="px-6 py-2.5 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition-all">
                        Close
                    </button>
                    <button id="modal-cancel-btn" onclick="cancelEditMode()" class="hidden px-6 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 hover:border-gray-300 transition-all">
                        Cancel
                    </button>
                    <button id="modal-update-btn" onclick="updateTruckDetails()" class="hidden px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md transition-all flex items-center gap-2">
                        <i class="fas fa-save text-sm"></i>
                        <span>Update</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- END MODAL -->

    </div>

    <!-- GLOBAL LOGOUT FORM -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    @push('css')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            nav[x-data], .min-h-screen > header { display: none !important; }
            .py-12 { padding-top: 0 !important; padding-bottom: 0 !important; }
            ::-webkit-scrollbar { width: 5px; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

            /* Tab bar – professional pill-shaped buttons */
            .modal-tab-btn {
                display: inline-flex;
                align-items: center;
                padding: 0.625rem 1.125rem;
                font-size: 0.875rem;
                font-weight: 600;
                color: #6b7280;
                background: transparent;
                border: 1.5px solid transparent;
                border-radius: 0.75rem;
                transition: all 0.2s ease;
                white-space: nowrap;
                cursor: pointer;
                outline: none;
            }
            .modal-tab-btn:hover {
                color: #374151;
                background: #f3f4f6;
                border-color: #d1d5db;
            }
            .modal-tab-btn.active-tab {
                color: #2563eb;
                background: #eff6ff;
                border-color: #93c5fd;
                font-weight: 700;
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.1);
            }
            .modal-tab-btn i {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            /* Fixed modal size - prevent resize on tab switching */
            #truckModalPanel {
                display: flex;
                flex-direction: column;
                width: 100%;
                max-width: 90rem;
                height: 90vh;
                max-height: 90vh;
            }
            
            /* Modal header - fixed size, no grow/shrink */
            #truckModalPanel > div:nth-child(1) {
                flex-shrink: 0;
                flex-grow: 0;
            }
            
            /* Tab bar - fixed size, no grow/shrink */
            #truckModalPanel > div:nth-child(2) {
                flex-shrink: 0;
                flex-grow: 0;
            }
            
            /* Tab content container - fills remaining space and scrolls */
            #truckModalPanel > div:nth-child(3) {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* Order status badge colours */
            .status-badge-pending        { background:#fef3c7; color:#92400e; }
            .status-badge-accepted       { background:#dbeafe; color:#1e40af; }
            .status-badge-preparing      { background:#ede9fe; color:#6d28d9; }
            .status-badge-prepared       { background:#d1fae5; color:#065f46; }
            .status-badge-ready_for_pickup{ background:#cffafe; color:#155e75; }
            .status-badge-delivery       { background:#fee2e2; color:#991b1b; }
            .status-badge-done           { background:#f0fdf4; color:#166534; }
        </style>
    @endpush

    <script>
        // â”€â”€â”€ Truck data store â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        var _trucksById = {};
        try {
            var _jsonEl = document.getElementById('trucks-json-data');
            if (_jsonEl) { _trucksById = JSON.parse(_jsonEl.textContent); }
        } catch(e) { console.error('Truck JSON parse error:', e); }

        var _currentTruck  = null;
        var _ordersLoaded  = false;   // whether orders have been fetched for active truck

        // â”€â”€â”€ Open / Close â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function openTruckModal(id) {
            _currentTruck = _trucksById[id] || null;
            if (!_currentTruck) { alert('Truck data not found.'); return; }
            _ordersLoaded = false;
            populateTruckTab();
            populateOwnerTab();
            populateMenuTab('');
            switchTab('truck');
            document.getElementById('truckDetailModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('truckDetailModal').style.display = 'none';
        }

        function handleModalBackdropClick(e) {
            if (e.target === document.getElementById('truckDetailModal')) closeModal();
        }

        // â”€â”€â”€ Tab Switching â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function switchTab(tab) {
            ['truck','owner','menu','orders'].forEach(function(t) {
                document.getElementById('tab-' + t).style.display      = (t === tab) ? 'block' : 'none';
                document.getElementById('tab-btn-' + t).classList.toggle('active-tab', t === tab);
            });
            if (tab === 'orders' && !_ordersLoaded) loadOrders();
        }
        // â"€â"€â"€ Edit Mode Functions â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
        var _editMode = { truckName: false, description: false };

        function toggleEditMode(field) {
            _editMode[field] = !_editMode[field];

            if (field === 'truckName') {
                var displayEl = document.getElementById('detail-truck-name-display');
                var inputEl = document.getElementById('detail-truck-name-edit');
                if (_editMode.truckName) {
                    inputEl.value = document.getElementById('detail-truck-name').textContent;
                    displayEl.classList.add('hidden');
                    inputEl.classList.remove('hidden');
                    inputEl.focus();
                } else {
                    displayEl.classList.remove('hidden');
                    inputEl.classList.add('hidden');
                }
            } else if (field === 'description') {
                var displayEl = document.getElementById('detail-desc-display');
                var inputEl = document.getElementById('detail-desc-edit');
                if (_editMode.description) {
                    inputEl.value = document.getElementById('detail-desc').textContent;
                    displayEl.classList.add('hidden');
                    inputEl.classList.remove('hidden');
                    inputEl.focus();
                } else {
                    displayEl.classList.remove('hidden');
                    inputEl.classList.add('hidden');
                }
            }

            // Show/hide action buttons if any field is in edit mode
            var isAnyFieldEditing = _editMode.truckName || _editMode.description;
            var closeBtn = document.getElementById('modal-close-btn');
            var cancelBtn = document.getElementById('modal-cancel-btn');
            var updateBtn = document.getElementById('modal-update-btn');
            if (isAnyFieldEditing) {
                closeBtn.classList.add('hidden');
                cancelBtn.classList.remove('hidden');
                updateBtn.classList.remove('hidden');
            } else {
                closeBtn.classList.remove('hidden');
                cancelBtn.classList.add('hidden');
                updateBtn.classList.add('hidden');
            }
        }

        function cancelEditMode() {
            _editMode.truckName = false;
            _editMode.description = false;
            document.getElementById('detail-truck-name-display').classList.remove('hidden');
            document.getElementById('detail-truck-name-edit').classList.add('hidden');
            document.getElementById('detail-desc-display').classList.remove('hidden');
            document.getElementById('detail-desc-edit').classList.add('hidden');
            document.getElementById('modal-close-btn').classList.remove('hidden');
            document.getElementById('modal-cancel-btn').classList.add('hidden');
            document.getElementById('modal-update-btn').classList.add('hidden');
        }

        function updateTruckDetails() {
            if (!_currentTruck) return;
            var truckName = document.getElementById('detail-truck-name-edit').value || '';
            var description = document.getElementById('detail-desc-edit').value || '';

            if (!truckName.trim()) {
                alert('Please enter a truck name');
                return;
            }

            var updateData = {
                foodtruck_name: truckName,
                foodtruck_desc: description
            };

            fetch('/admin/trucks/' + _currentTruck.id + '/update-details', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(updateData)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    _currentTruck.foodtruck_name = data.truck.foodtruck_name;
                    _currentTruck.foodtruck_desc = data.truck.foodtruck_desc;
                    populateTruckTab();
                    cancelEditMode();
                    alert('Truck details updated successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to update truck details'));
                }
            })
            .catch(function(e) {
                console.error(e);
                alert('Error: Failed to update truck details. Please try again.');
            });
        }
        // â”€â”€â”€ Tab 1: Truck Details â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function populateTruckTab() {
            var t = _currentTruck;
            document.getElementById('modalTruckName').textContent    = t.foodtruck_name || '';
            document.getElementById('detail-truck-name').textContent = t.foodtruck_name || '';
            document.getElementById('detail-license').textContent    = t.business_license_no || '';
            document.getElementById('detail-desc').textContent       = t.foodtruck_desc || 'No description provided';

            var dot = document.getElementById('detail-status-dot');
            var txt = document.getElementById('detail-status-text');
            var box = document.getElementById('detail-status-box');
            if (t.is_operational) {
                dot.className   = 'w-2.5 h-2.5 rounded-full flex-shrink-0 bg-emerald-500 animate-pulse';
                txt.className   = 'text-sm font-bold text-emerald-700';
                txt.textContent = 'Online & Operational';
                box.className   = 'px-4 py-3 rounded-xl border flex items-center gap-3 bg-emerald-50 border-emerald-200';
            } else {
                dot.className   = 'w-2.5 h-2.5 rounded-full flex-shrink-0 bg-red-500';
                txt.className   = 'text-sm font-bold text-red-600';
                txt.textContent = 'Offline';
                box.className   = 'px-4 py-3 rounded-xl border flex items-center gap-3 bg-red-50 border-red-200';
            }
        }

        // â”€â”€â”€ Tab 2: Owner & Staff â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function populateOwnerTab() {
            var t   = _currentTruck;
            var own = t.owner || {};
            document.getElementById('detail-owner-name').textContent  = own.full_name || 'N/A';
            document.getElementById('detail-owner-email').textContent = own.email     || 'N/A';
            document.getElementById('detail-owner-phone').textContent = own.phone_no  || 'Not provided';

            var staffList  = document.getElementById('detail-staff-list');
            var staffEmpty = document.getElementById('detail-staff-empty');
            staffList.innerHTML = '';

            var workers = (t.staff || []).filter(function(w) { return w.role == 3; });
            if (workers.length === 0) {
                staffList.style.display  = 'none';
                staffEmpty.style.display = 'block';
            } else {
                staffList.style.display  = 'block';
                staffEmpty.style.display = 'none';
                workers.forEach(function(w) {
                    var initial = (w.full_name || '?').charAt(0).toUpperCase();
                    var badge   = w.status === 'active' ? 'Active' : (w.status || '');
                    staffList.innerHTML +=
                        '<div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">'
                        + '<div class="flex items-center gap-3 mb-3">'
                        + '<div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center font-black text-sm flex-shrink-0">' + escHtml(initial) + '</div>'
                        + '<div><p class="text-sm font-bold text-gray-800">' + escHtml(w.full_name || '') + '</p>'
                        + '<span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded uppercase">'
                        + '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>' + escHtml(badge) + '</span></div></div>'
                        + '<div class="space-y-1 text-xs">'
                        + '<p class="text-gray-500"><span class="font-bold text-gray-600">Email: </span>' + escHtml(w.email || 'N/A') + '</p>'
                        + '<p class="text-gray-500"><span class="font-bold text-gray-600">Phone: </span>' + escHtml(w.phone_no || 'Not provided') + '</p>'
                        + '</div></div>';
                });
            }
        }

        // â”€â”€â”€ Tab 3: Menu List (card grid, mirrors manage-menus style) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function populateMenuTab(filter) {
            var t     = _currentTruck;
            var menus = t.menus || [];
            var cats  = [];
            menus.forEach(function(m) { if (m.category && cats.indexOf(m.category) === -1) cats.push(m.category); });

            // â”€â”€ Category filter pills â”€â”€
            var bar = document.getElementById('menu-category-filters');
            bar.innerHTML = '';
            var allBtn = makePill('All', !filter);
            allBtn.onclick = function() { populateMenuTab(''); };
            bar.appendChild(allBtn);
            cats.forEach(function(cat) {
                var btn = makePill(cat, filter === cat);
                btn.onclick = (function(c) { return function() { populateMenuTab(c); }; })(cat);
                bar.appendChild(btn);
            });

            // â”€â”€ Filtered list â”€â”€
            var filtered = filter ? menus.filter(function(m) { return m.category === filter; }) : menus;
            var grid     = document.getElementById('detail-menu-grid');
            var empty    = document.getElementById('detail-menu-empty');
            grid.innerHTML = '';

            if (filtered.length === 0) {
                grid.style.display  = 'none';
                empty.style.display = 'flex';
            } else {
                grid.style.display  = 'grid';
                empty.style.display = 'none';

                filtered.forEach(function(menu) {
                    var price     = parseFloat(menu.base_price || 0).toFixed(2);
                    var imgHtml   = menu.image
                        ? '<img src="/storage/' + escHtml(menu.image) + '" alt="' + escHtml(menu.name) + '" class="w-full h-full object-contain">'
                        : '<div class="w-full h-full flex flex-col items-center justify-center text-gray-300"><i class="fas fa-image text-2xl mb-1"></i><span class="text-[10px] font-medium">No Image</span></div>';
                    var isAvail   = (menu.status === 'available');
                    var badge     = isAvail
                        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 shadow-sm"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Available</span>'
                        : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-600 shadow-sm"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Unavailable</span>';
                    var descHtml  = menu.description
                        ? '<p class="text-[11px] text-gray-400 leading-relaxed line-clamp-2 mt-1">' + escHtml(menu.description) + '</p>'
                        : '';
                    var ogCount   = (menu.option_groups || []).length;
                    var ogHtml    = ogCount > 0
                        ? '<div class="border-t border-gray-100 pt-2.5 mt-2.5">'
                          + '<p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Option Groups</p>'
                          + buildOptionGroupsHtml(menu.option_groups)
                          + '</div>'
                        : '';

                    grid.innerHTML +=
                        '<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-lg hover:scale-[1.015] transition-all duration-200">'
                        // Image
                        + '<div class="relative w-full h-36 bg-gray-100 flex-shrink-0">'
                        + imgHtml
                        + '<div class="absolute top-2 right-2">' + badge + '</div>'
                        + '</div>'
                        // Body
                        + '<div class="flex flex-col p-4 space-y-1 flex-1">'
                        + '<div class="flex items-start justify-between">'
                        + '<div class="flex-1 min-w-0">'
                        + '<h3 class="text-sm font-bold text-gray-900 leading-tight line-clamp-1">' + escHtml(menu.name || '') + '</h3>'
                        + '<span class="text-[11px] font-semibold text-blue-500 uppercase tracking-wider">' + escHtml(menu.category || '') + '</span>'
                        + '</div></div>'
                        + '<div class="flex items-baseline gap-0.5 mt-1">'
                        + '<span class="text-xs text-gray-400 font-medium">RM</span>'
                        + '<span class="text-lg font-black text-gray-900">' + escHtml(price) + '</span>'
                        + '</div>'
                        + descHtml
                        + ogHtml
                        + '</div>'
                        + '</div>';
                });
            }
        }

        function buildOptionGroupsHtml(groups) {
            var html = '';
            (groups || []).forEach(function(og) {
                html += '<p class="text-[10px] font-bold text-gray-600 mb-1">' + escHtml(og.name || '') + '</p>'
                     + '<div class="flex flex-wrap gap-1 mb-2">';
                (og.choices || []).forEach(function(ch) {
                    var cp = parseFloat(ch.price || 0) > 0 ? ' +RM ' + parseFloat(ch.price).toFixed(2) : '';
                    html += '<span class="text-[10px] bg-gray-50 border border-gray-200 text-gray-600 font-medium px-2 py-0.5 rounded-full">'
                         + escHtml(ch.name || '') + (cp ? '<span class="text-purple-600 font-bold">' + escHtml(cp) + '</span>' : '') + '</span>';
                });
                html += '</div>';
            });
            return html;
        }

        // â”€â”€â”€ Tab 4: Orders â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        var _orderStatusLabels = {
            'pending':         'Pending',
            'accepted':        'Accepted',
            'preparing':       'Preparing',
            'prepared':        'Prepared',
            'ready_for_pickup':'Ready for Pickup',
            'delivery':        'Delivery',
            'done':            'Done',
        };
        var _orderStatusOptions = Object.keys(_orderStatusLabels);

        function loadOrders() {
            if (!_currentTruck) return;
            _ordersLoaded = true;

            var loading = document.getElementById('orders-loading');
            var list    = document.getElementById('orders-list');
            var empty   = document.getElementById('orders-empty');
            loading.style.display = 'flex';
            list.style.display    = 'none';
            empty.style.display   = 'none';
            list.innerHTML        = '';

            fetch('/admin/trucks/' + _currentTruck.id + '/orders', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(orders) {
                loading.style.display = 'none';
                if (!orders || orders.length === 0) {
                    empty.style.display = 'flex';
                    return;
                }
                list.style.display = 'block';
                orders.forEach(function(order) {
                    list.innerHTML += buildOrderCard(order);
                });
            })
            .catch(function(err) {
                loading.style.display = 'none';
                empty.style.display   = 'flex';
                console.error('Failed to load orders:', err);
            });
        }

        function buildOrderCard(order) {
            var items = [];
            try { items = Array.isArray(order.items) ? order.items : JSON.parse(order.items || '[]'); } catch(e) {}
            var itemsSummary = items.map(function(i) { return (i.quantity ? i.quantity + 'Ã— ' : '') + (i.name || '?'); }).join(', ');
            var statusKey    = order.status || 'pending';
            var statusLabel  = _orderStatusLabels[statusKey] || statusKey;
            var badgeCls     = 'status-badge-' + statusKey;

            var opts = _orderStatusOptions.map(function(s) {
                return '<option value="' + s + '"' + (s === statusKey ? ' selected' : '') + '>' + (_orderStatusLabels[s] || s) + '</option>';
            }).join('');

            var createdAt = order.created_at ? new Date(order.created_at).toLocaleString('en-MY', { dateStyle:'medium', timeStyle:'short' }) : '';

            return '<div class="bg-white border border-gray-200 rounded-2xl p-4 space-y-3" id="order-card-' + order.id + '">'
                + '<div class="flex items-start justify-between gap-3">'
                + '<div class="flex-1 min-w-0">'
                + '<div class="flex items-center gap-2 flex-wrap">'
                + '<span class="text-xs font-black text-gray-700">#' + order.id + '</span>'
                + '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ' + badgeCls + ' order-status-badge-' + order.id + '">' + escHtml(statusLabel) + '</span>'
                + '</div>'
                + '<p class="text-xs font-bold text-gray-800 mt-1">' + escHtml(order.customer_name || 'Customer') + '</p>'
                + '<p class="text-[11px] text-gray-400 mt-0.5 line-clamp-2">' + escHtml(itemsSummary || 'No items') + '</p>'
                + '</div>'
                + '<div class="text-right flex-shrink-0">'
                + '<p class="text-base font-black text-gray-900">RM ' + parseFloat(order.total || 0).toFixed(2) + '</p>'
                + '<p class="text-[10px] text-gray-400 mt-0.5">' + escHtml(createdAt) + '</p>'
                + '</div>'
                + '</div>'
                // Status update row
                + '<div class="flex items-center gap-2 pt-1 border-t border-gray-100">'
                + '<label class="text-[10px] font-black uppercase tracking-widest text-gray-400 flex-shrink-0">Update Status</label>'
                + '<select onchange="updateOrderStatus(' + order.id + ', this.value, this)"'
                + '        class="flex-1 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all appearance-none cursor-pointer">'
                + opts
                + '</select>'
                + '<span id="order-saving-' + order.id + '" class="text-[10px] text-blue-500 font-bold hidden flex-shrink-0">Savingâ€¦</span>'
                + '</div>'
                + '</div>';
        }

        function updateOrderStatus(orderId, newStatus, selectEl) {
            var saving = document.getElementById('order-saving-' + orderId);
            if (saving) { saving.classList.remove('hidden'); }
            if (selectEl) selectEl.disabled = true;

            fetch('/admin/orders/' + orderId + '/status', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (saving)    { saving.classList.add('hidden'); }
                if (selectEl)  { selectEl.disabled = false; }
                if (data.success) {
                    // Update the badge
                    var badge = document.querySelector('.order-status-badge-' + orderId);
                    if (badge) {
                        var label = _orderStatusLabels[data.status] || data.status;
                        badge.textContent = label;
                        badge.className   = 'text-[10px] font-bold px-2 py-0.5 rounded-full status-badge-' + data.status + ' order-status-badge-' + orderId;
                    }
                } else {
                    alert('Failed to update order status.');
                }
            })
            .catch(function(err) {
                if (saving)   { saving.classList.add('hidden'); }
                if (selectEl) { selectEl.disabled = false; }
                console.error('Order status update failed:', err);
                alert('Error updating order status. Please try again.');
            });
        }

        // â”€â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        function makePill(label, isActive) {
            var btn = document.createElement('button');
            btn.type        = 'button';
            btn.textContent = label;
            btn.className   = 'px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wide transition-colors '
                + (isActive ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200');
            return btn;
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // â”€â”€â”€ Sidebar Toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        document.addEventListener('DOMContentLoaded', function () {
            var sidebar  = document.getElementById('sidebar');
            var openBtn  = document.getElementById('openSidebar');
            var closeBtn = document.getElementById('closeSidebar');
            if (openBtn)  openBtn.addEventListener('click',  function() { sidebar.classList.remove('sidebar-hidden'); });
            if (closeBtn) closeBtn.addEventListener('click', function() { sidebar.classList.add('sidebar-hidden'); });
        });
    </script>
</x-app-layout>
