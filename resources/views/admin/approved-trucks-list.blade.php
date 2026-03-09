<x-app-layout>
    {{-- This slot override helps prevent Breeze from injecting headers into the top --}}
    <x-slot name="header"></x-slot>

    <div class="fixed inset-0 flex h-screen bg-gray-50 font-sans antialiased overflow-hidden z-50" x-data="approvedTrucksPage()">

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
                                Approved Food Trucks
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

                                                    <!-- View Details Button -->
                                                    <button
                                                        type="button"
                                                        x-data="{ truck: @json($truck) }"
                                                        @click="$root.selectedTruck = truck; $root.activeDetailTab = 'truck'; $root.menuFilter = ''; $root.showDetailModal = true"
                                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                                    >
                                                        View Details
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

        <!-- TRUCK DETAILS MODAL -->
        <div x-show="showDetailModal"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

            <div @click.away="showDetailModal = false"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden border border-white/20 flex flex-col h-[85vh] max-h-[750px]">

                <!-- Header with Tabs -->
                <div class="px-8 py-6 border-b border-gray-100 bg-gray-50/50 flex-shrink-0">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="bg-blue-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-100">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-gray-800 tracking-tight" x-text="selectedTruck?.foodtruck_name"></h2>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5">Truck Details & Information</p>
                            </div>
                        </div>
                        <button @click="showDetailModal = false" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex gap-2 border-t border-gray-100 pt-4">
                        <button @click="activeDetailTab = 'truck'"
                                :class="activeDetailTab === 'truck' ? 'bg-blue-50 text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                class="relative px-4 py-2 text-sm font-bold uppercase tracking-widest transition-colors">
                            <i class="fas fa-info-circle mr-2"></i>Truck Details
                        </button>
                        <button @click="activeDetailTab = 'owner'"
                                :class="activeDetailTab === 'owner' ? 'bg-blue-50 text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                class="relative px-4 py-2 text-sm font-bold uppercase tracking-widest transition-colors">
                            <i class="fas fa-user mr-2"></i>Owner & Staff
                        </button>
                        <button @click="activeDetailTab = 'menu'"
                                :class="activeDetailTab === 'menu' ? 'bg-blue-50 text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                class="relative px-4 py-2 text-sm font-bold uppercase tracking-widest transition-colors">
                            <i class="fas fa-utensils mr-2"></i>Menu Lists
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto">

                    <!-- Tab 1: Truck Details -->
                    <div x-show="activeDetailTab === 'truck'" class="p-8">
                        <div class="space-y-6">
                            <!-- Truck Name -->
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Truck Name</label>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-lg font-bold text-gray-800" x-text="selectedTruck?.foodtruck_name"></p>
                                </div>
                            </div>

                            <!-- Business License Number -->
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Business License Number</label>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-sm font-mono text-gray-700" x-text="selectedTruck?.business_license_no"></p>
                                </div>
                            </div>

                            <!-- Operational Status -->
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Operational Status</label>
                                <div class="p-4 rounded-2xl border" :class="selectedTruck?.is_operational ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full flex-shrink-0" :class="selectedTruck?.is_operational ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></div>
                                        <span class="text-sm font-bold" :class="selectedTruck?.is_operational ? 'text-emerald-700' : 'text-red-600'" x-text="selectedTruck?.is_operational ? 'Online & Operational' : 'Offline'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-2">Description</label>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-sm text-gray-700 leading-relaxed" x-text="selectedTruck?.foodtruck_desc || 'No description provided'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Owner & Staff Details -->
                    <div x-show="activeDetailTab === 'owner'" class="p-8">
                        <div class="space-y-6">
                            <!-- Truck Owner Section -->
                            <div>
                                <h3 class="text-base font-black text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-user-tie mr-2 text-blue-600"></i>
                                    Truck Owner / Admin
                                </h3>
                                <div class="bg-blue-50 rounded-2xl border border-blue-100 p-4 space-y-3">
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Full Name</p>
                                        <p class="text-sm font-bold text-gray-800" x-text="selectedTruck?.user?.full_name"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Email Address</p>
                                        <p class="text-sm font-mono text-gray-700" x-text="selectedTruck?.user?.email"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Phone Number</p>
                                        <p class="text-sm text-gray-700" x-text="selectedTruck?.user?.phone_no || 'Not provided'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff / Workers Section -->
                            <div>
                                <h3 class="text-base font-black text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-users mr-2 text-emerald-600"></i>
                                    Food Truck Workers / Staff
                                </h3>
                                <div x-show="selectedTruck?.workers && selectedTruck?.workers?.length > 0" class="space-y-3">
                                    <template x-for="worker in selectedTruck?.workers" :key="worker.id">
                                        <div class="bg-emerald-50 rounded-2xl border border-emerald-100 p-4 space-y-2">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="w-10 h-10 rounded-lg bg-slate-800 text-white flex items-center justify-center font-bold text-sm" x-text="worker.full_name.charAt(0)"></div>
                                                <div>
                                                    <p class="text-sm font-bold text-gray-800" x-text="worker.full_name"></p>
                                                    <span class="text-[10px] font-bold text-emerald-600 bg-white px-2 py-0.5 rounded uppercase" x-text="worker.status === 'active' ? 'Active' : worker.status"></span>
                                                </div>
                                            </div>
                                            <div class="text-xs space-y-1">
                                                <p><span class="font-bold text-gray-600">Email:</span> <span class="text-gray-700" x-text="worker.email"></span></p>
                                                <p><span class="font-bold text-gray-600">Phone:</span> <span class="text-gray-700" x-text="worker.phone_no || 'Not provided'"></span></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="!selectedTruck?.workers || selectedTruck?.workers?.length === 0" class="text-center py-8 bg-gray-50 rounded-2xl border border-gray-100">
                                    <i class="fas fa-users text-3xl text-gray-300 mb-2 block"></i>
                                    <p class="text-sm font-bold text-gray-500">No staff members assigned</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Menu Lists -->
                    <div x-show="activeDetailTab === 'menu'" class="p-8">
                        <div class="space-y-6">
                            <!-- Category Filter -->
                            <div>
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1 block mb-3">Filter by Category</label>
                                <div class="flex flex-wrap gap-2">
                                    <button @click="menuFilter = ''"
                                            :class="!menuFilter ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                            class="px-4 py-2 rounded-lg text-xs font-bold uppercase transition-colors">
                                        All
                                    </button>
                                    <template x-for="category in getMenuCategories()" :key="category">
                                        <button @click="menuFilter = category"
                                                :class="menuFilter === category ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                class="px-4 py-2 rounded-lg text-xs font-bold uppercase transition-colors"
                                                x-text="category">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Menus List -->
                            <div>
                                <div x-show="getFilteredMenus().length > 0" class="space-y-4">
                                    <template x-for="menu in getFilteredMenus()" :key="menu.id">
                                        <div class="bg-purple-50 rounded-2xl border border-purple-100 p-5">
                                            <div class="flex items-start justify-between mb-4">
                                                <div>
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <h4 class="text-base font-black text-gray-800" x-text="menu.name"></h4>
                                                        <span class="text-[10px] font-bold text-purple-600 bg-white px-2 py-0.5 rounded uppercase" x-text="menu.category"></span>
                                                    </div>
                                                    <p class="text-sm text-gray-600" x-text="menu.details || 'No details'"></p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-lg font-black text-gray-900" x-text="'RM ' + parseFloat(menu.price).toFixed(2)"></p>
                                                </div>
                                            </div>

                                            <!-- Menu Choices & Option Groups -->
                                            <div x-show="menu.option_groups && menu.option_groups.length > 0" class="mt-4 pt-4 border-t border-purple-200">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Options & Choices</p>
                                                <template x-for="optionGroup in menu.option_groups" :key="optionGroup.id">
                                                    <div class="mb-3">
                                                        <p class="text-xs font-bold text-gray-700 mb-2" x-text="optionGroup.name"></p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <template x-for="choice in optionGroup.choices" :key="choice.id">
                                                                <span class="text-xs bg-white px-2.5 py-1 rounded-full border border-purple-200 text-gray-700 font-medium">
                                                                    <span x-text="choice.name"></span>
                                                                    <span class="text-purple-600 font-bold ml-1" x-text="'(+RM ' + parseFloat(choice.additional_price).toFixed(2) + ')'"></span>
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="getFilteredMenus().length === 0" class="text-center py-12 bg-gray-50 rounded-2xl border border-gray-100">
                                    <i class="fas fa-utensils text-4xl text-gray-300 mb-3 block"></i>
                                    <p class="text-sm font-bold text-gray-500">No menus available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

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
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script>
            // Define the Alpine component function immediately
            function approvedTrucksPage() {
                return {
                    showDetailModal: false,
                    activeDetailTab: 'truck',
                    selectedTruck: null,
                    menuFilter: '',

                    getMenuCategories() {
                        if (!this.selectedTruck?.menus) return [];
                        const categories = [...new Set(this.selectedTruck.menus.map(m => m.category))];
                        return categories.filter(c => c !== null && c !== undefined);
                    },

                    getFilteredMenus() {
                        if (!this.selectedTruck?.menus) return [];
                        if (!this.menuFilter) return this.selectedTruck.menus;
                        return this.selectedTruck.menus.filter(m => m.category === this.menuFilter);
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const sidebar  = document.getElementById('sidebar');
                const openBtn  = document.getElementById('openSidebar');
                const closeBtn = document.getElementById('closeSidebar');

                if (openBtn)
                    openBtn.addEventListener('click', () =>
                        sidebar.classList.remove('sidebar-hidden')
                    );

                if (closeBtn)
                    closeBtn.addEventListener('click', () =>
                        sidebar.classList.add('sidebar-hidden')
                    );
            });
        </script>
    @endpush
</x-app-layout>
