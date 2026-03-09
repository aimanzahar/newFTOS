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
                            <table class="min-w-full leading-normal">
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
                                                <div class="flex justify-end space-x-2">

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

        <!-- =============================================
             TRUCK DETAILS MODAL (Pure Vanilla JS)
             ============================================= -->
        <div id="truckDetailModal"
             style="display:none;"
             class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             onclick="handleModalBackdropClick(event)">

            <div id="truckModalPanel"
                 class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden border border-white/20 flex flex-col h-[85vh] max-h-[750px]">

                <!-- Modal Header with Tabs -->
                <div class="px-8 py-6 border-b border-gray-100 bg-gray-50/50 flex-shrink-0">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="bg-blue-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-100">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <h2 id="modalTruckName" class="text-xl font-black text-gray-800 tracking-tight"></h2>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5">Truck Details & Information</p>
                            </div>
                        </div>
                        <button onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex gap-2 border-t border-gray-100 pt-4">
                        <button id="tab-btn-truck" onclick="switchTab('truck')"
                                class="modal-tab-btn active-tab px-4 py-2 text-sm font-bold uppercase tracking-widest transition-colors">
                            <i class="fas fa-info-circle mr-2"></i>Truck Details
                        </button>
                        <button id="tab-btn-owner" onclick="switchTab('owner')"
                                class="modal-tab-btn px-4 py-2 text-sm font-bold uppercase tracking-widest transition-colors">
                            <i class="fas fa-user mr-2"></i>Owner & Staff
                        </button>
                        <button id="tab-btn-menu" onclick="switchTab('menu')"
                                class="modal-tab-btn px-4 py-2 text-sm font-bold uppercase tracking-widest transition-colors">
                            <i class="fas fa-utensils mr-2"></i>Menu Lists
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto">

                    <!-- Tab 1: Truck Details -->
                    <div id="tab-truck" class="modal-tab-panel p-8">
                        <div class="space-y-6">
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Truck Name</label>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p id="detail-truck-name" class="text-lg font-bold text-gray-800"></p>
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Business License Number</label>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p id="detail-license" class="text-sm font-mono text-gray-700"></p>
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Operational Status</label>
                                <div id="detail-status-box" class="p-4 rounded-2xl border">
                                    <div class="flex items-center gap-3">
                                        <div id="detail-status-dot" class="w-3 h-3 rounded-full flex-shrink-0"></div>
                                        <span id="detail-status-text" class="text-sm font-bold"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Description</label>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p id="detail-desc" class="text-sm text-gray-700 leading-relaxed"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Owner & Staff -->
                    <div id="tab-owner" class="modal-tab-panel p-8" style="display:none;">
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-base font-black text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-user-tie mr-2 text-blue-600"></i>Truck Owner / Admin
                                </h3>
                                <div class="bg-blue-50 rounded-2xl border border-blue-100 p-4 space-y-3">
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Full Name</p>
                                        <p id="detail-owner-name" class="text-sm font-bold text-gray-800"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Email Address</p>
                                        <p id="detail-owner-email" class="text-sm font-mono text-gray-700"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Phone Number</p>
                                        <p id="detail-owner-phone" class="text-sm text-gray-700"></p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-base font-black text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-users mr-2 text-emerald-600"></i>Food Truck Workers / Staff
                                </h3>
                                <div id="detail-staff-list" class="space-y-3"></div>
                                <div id="detail-staff-empty" class="text-center py-8 bg-gray-50 rounded-2xl border border-gray-100" style="display:none;">
                                    <i class="fas fa-users text-3xl text-gray-300 mb-2 block"></i>
                                    <p class="text-sm font-bold text-gray-500">No staff members assigned</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Menu Lists -->
                    <div id="tab-menu" class="modal-tab-panel p-8" style="display:none;">
                        <div class="space-y-6">
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-3">Filter by Category</label>
                                <div id="menu-category-filters" class="flex flex-wrap gap-2"></div>
                            </div>
                            <div id="detail-menu-list" class="space-y-4"></div>
                            <div id="detail-menu-empty" class="text-center py-12 bg-gray-50 rounded-2xl border border-gray-100" style="display:none;">
                                <i class="fas fa-utensils text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-sm font-bold text-gray-500">No menus available</p>
                            </div>
                        </div>
                    </div>

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
            nav,
            .min-h-screen > header {
                display: none !important;
            }

            .py-12 {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            ::-webkit-scrollbar {
                width: 5px;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .modal-tab-btn {
                color: #6b7280;
            }
            .modal-tab-btn:hover {
                color: #374151;
            }
            .modal-tab-btn.active-tab {
                background-color: #eff6ff;
                color: #2563eb;
                border-bottom: 2px solid #2563eb;
            }
        </style>
    @endpush

    <script>
        // ─── Truck data store (keyed by ID) — read from safe JSON script tag ────
        var _trucksById = {};
        try {
            var _jsonEl = document.getElementById('trucks-json-data');
            if (_jsonEl) { _trucksById = JSON.parse(_jsonEl.textContent); }
        } catch(e) { console.error('Truck JSON parse error:', e); }

        // ─── State ────────────────────────────────────────────────────────────────
        var _currentTruck = null;
        var _menuFilter   = '';

        // ─── Open / Close ─────────────────────────────────────────────────────────
        function openTruckModal(id) {
            _currentTruck = _trucksById[id] || null;
            if (!_currentTruck) { alert('Truck data not found.'); return; }
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

        // ─── Tab Switching ────────────────────────────────────────────────────────
        function switchTab(tab) {
            ['truck', 'owner', 'menu'].forEach(function(t) {
                document.getElementById('tab-' + t).style.display       = (t === tab) ? 'block' : 'none';
                document.getElementById('tab-btn-' + t).classList.toggle('active-tab', t === tab);
            });
        }

        // ─── Tab 1: Truck Details ─────────────────────────────────────────────────
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
                dot.className   = 'w-3 h-3 rounded-full flex-shrink-0 bg-emerald-500 animate-pulse';
                txt.className   = 'text-sm font-bold text-emerald-700';
                txt.textContent = 'Online & Operational';
                box.className   = 'p-4 rounded-2xl border bg-emerald-50 border-emerald-200';
            } else {
                dot.className   = 'w-3 h-3 rounded-full flex-shrink-0 bg-red-500';
                txt.className   = 'text-sm font-bold text-red-600';
                txt.textContent = 'Offline';
                box.className   = 'p-4 rounded-2xl border bg-red-50 border-red-200';
            }
        }

        // ─── Tab 2: Owner & Staff ─────────────────────────────────────────────────
        function populateOwnerTab() {
            var t   = _currentTruck;
            var own = t.owner || {};
            document.getElementById('detail-owner-name').textContent  = own.full_name || 'N/A';
            document.getElementById('detail-owner-email').textContent = own.email     || 'N/A';
            document.getElementById('detail-owner-phone').textContent = own.phone_no  || 'Not provided';

            var staffList  = document.getElementById('detail-staff-list');
            var staffEmpty = document.getElementById('detail-staff-empty');
            staffList.innerHTML  = '';

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
                    staffList.innerHTML += '<div class="bg-emerald-50 rounded-2xl border border-emerald-100 p-4 space-y-2">'
                        + '<div class="flex items-center gap-3 mb-3">'
                        + '<div class="w-10 h-10 rounded-lg bg-slate-800 text-white flex items-center justify-center font-bold text-sm">' + escHtml(initial) + '</div>'
                        + '<div>'
                        + '<p class="text-sm font-bold text-gray-800">' + escHtml(w.full_name || '') + '</p>'
                        + '<span class="text-[10px] font-bold text-emerald-600 bg-white px-2 py-0.5 rounded uppercase">' + escHtml(badge) + '</span>'
                        + '</div></div>'
                        + '<div class="text-xs space-y-1">'
                        + '<p><span class="font-bold text-gray-600">Email:</span> <span class="text-gray-700">' + escHtml(w.email || '') + '</span></p>'
                        + '<p><span class="font-bold text-gray-600">Phone:</span> <span class="text-gray-700">' + escHtml(w.phone_no || 'Not provided') + '</span></p>'
                        + '</div></div>';
                });
            }
        }

        // ─── Tab 3: Menu Lists ────────────────────────────────────────────────────
        function populateMenuTab(filter) {
            _menuFilter = filter;
            var t      = _currentTruck;
            var menus  = t.menus || [];
            var cats   = [];
            menus.forEach(function(m) { if (m.category && cats.indexOf(m.category) === -1) cats.push(m.category); });

            // Category filter buttons
            var filterBar = document.getElementById('menu-category-filters');
            filterBar.innerHTML = '';

            var allBtn = document.createElement('button');
            allBtn.type        = 'button';
            allBtn.textContent = 'All';
            allBtn.className   = 'px-4 py-2 rounded-lg text-xs font-bold uppercase transition-colors ' + (!filter ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
            allBtn.onclick     = function() { populateMenuTab(''); };
            filterBar.appendChild(allBtn);

            cats.forEach(function(cat) {
                var btn = document.createElement('button');
                btn.type        = 'button';
                btn.textContent = cat;
                btn.className   = 'px-4 py-2 rounded-lg text-xs font-bold uppercase transition-colors ' + (filter === cat ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
                btn.onclick     = (function(c) { return function() { populateMenuTab(c); }; })(cat);
                filterBar.appendChild(btn);
            });

            // Filtered menus
            var filtered  = filter ? menus.filter(function(m) { return m.category === filter; }) : menus;
            var menuList  = document.getElementById('detail-menu-list');
            var menuEmpty = document.getElementById('detail-menu-empty');
            menuList.innerHTML = '';

            if (filtered.length === 0) {
                menuList.style.display  = 'none';
                menuEmpty.style.display = 'block';
            } else {
                menuList.style.display  = 'block';
                menuEmpty.style.display = 'none';
                filtered.forEach(function(menu) {
                    var price     = parseFloat(menu.base_price || 0).toFixed(2);
                    var optGroups = menu.option_groups || [];
                    var optHtml   = '';
                    if (optGroups.length > 0) {
                        optHtml += '<div class="mt-4 pt-4 border-t border-purple-200"><p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Options & Choices</p>';
                        optGroups.forEach(function(og) {
                            optHtml += '<div class="mb-3"><p class="text-xs font-bold text-gray-700 mb-2">' + escHtml(og.name || '') + '</p><div class="flex flex-wrap gap-2">';
                            (og.choices || []).forEach(function(ch) {
                                var chPrice = parseFloat(ch.price || 0) > 0
                                    ? '(+RM ' + parseFloat(ch.price).toFixed(2) + ')'
                                    : '(Free)';
                                optHtml += '<span class="text-xs bg-white px-2.5 py-1 rounded-full border border-purple-200 text-gray-700 font-medium">'
                                    + escHtml(ch.name || '') + ' <span class="text-purple-600 font-bold ml-1">' + escHtml(chPrice) + '</span></span>';
                            });
                            optHtml += '</div></div>';
                        });
                        optHtml += '</div>';
                    }
                    menuList.innerHTML +=
                        '<div class="bg-purple-50 rounded-2xl border border-purple-100 p-5">'
                        + '<div class="flex items-start justify-between mb-4">'
                        + '<div>'
                        + '<div class="flex items-center gap-2 mb-2">'
                        + '<h4 class="text-base font-black text-gray-800">' + escHtml(menu.name || '') + '</h4>'
                        + '<span class="text-[10px] font-bold text-purple-600 bg-white px-2 py-0.5 rounded uppercase">' + escHtml(menu.category || '') + '</span>'
                        + '</div>'
                        + '<p class="text-sm text-gray-600">' + escHtml(menu.description || 'No description') + '</p>'
                        + '</div>'
                        + '<div class="text-right"><p class="text-lg font-black text-gray-900">RM ' + escHtml(price) + '</p></div>'
                        + '</div>'
                        + optHtml
                        + '</div>';
                });
            }
        }

        // ─── XSS-safe HTML escape ─────────────────────────────────────────────────
        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // ─── Sidebar Toggle ───────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            var sidebar  = document.getElementById('sidebar');
            var openBtn  = document.getElementById('openSidebar');
            var closeBtn = document.getElementById('closeSidebar');
            if (openBtn)  openBtn.addEventListener('click',  function() { sidebar.classList.remove('sidebar-hidden'); });
            if (closeBtn) closeBtn.addEventListener('click', function() { sidebar.classList.add('sidebar-hidden'); });
        });
    </script>
</x-app-layout>
