<x-ftadmin-layout>
    {{-- This slot override helps prevent Breeze from injecting headers into the top --}}
    <x-slot name="header"></x-slot>

    @php
        $user = Auth::user();
        $role = (int)$user->role;
    @endphp

    {{-- 
        We do NOT wrap this in the 'fixed inset-0' div because x-ftadmin-layout 
        likely already handles the sidebar and main layout container.
    --}}
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

        <!-- Fixed Top Header (Matched exactly with edit.blade.php) -->
        <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
            <!-- Left Side: Mobile Toggle & Breadcrumbs -->
            <div class="flex items-center">
                <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden md:flex items-center text-gray-400 space-x-2">
                    <i class="fas fa-home text-sm"></i>
                    <span class="text-gray-300">/</span>
                    <span class="text-sm font-bold text-gray-700">Dashboard</span>
                </div>
            </div>

            <!-- Right Side: Notifications & Profile -->
            <div class="flex items-center space-x-6">
                <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                </button>
                <div class="h-6 w-px bg-gray-200"></div>
                <div class="flex items-center group cursor-pointer" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <div class="text-right mr-3 hidden lg:block">
                        <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ $user->full_name }}</p>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                            @switch($role)
                                @case(6) Super Admin @break
                                @case(2) FT Admin @break
                                @case(3) FT Worker @break
                                @default User
                            @endswitch
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

        <!-- Scrollable Content Section -->
        <main class="flex-1 overflow-y-auto relative scroll-smooth bg-gray-50">
            
            {{-- THE BLUR OVERLAY (Scoped only to the main content area) --}}
            @if($user->foodTruck && $user->foodTruck->status !== 'approved')
                <div class="absolute inset-0 z-40 flex items-center justify-center bg-gray-50/60 backdrop-blur-md">
                    <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md text-center border border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-yellow-400"></div>
                        <div class="mb-4 flex justify-center">
                            <div class="p-4 bg-yellow-50 rounded-full">
                                <i class="fas fa-hourglass-half text-3xl text-yellow-600 animate-pulse"></i>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Registration Pending</h2>
                        <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                            Your food truck profile is currently under review by our administration. 
                            You will gain full access to management tools once approved.
                        </p>
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-[11px] text-gray-400 uppercase font-bold tracking-widest">Status: Pending Verification</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="p-6 lg:p-10">
                <div class="w-full max-w-[1400px] mx-auto space-y-8">
                    
                    <!-- Page Header Section -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard Overview</h1>
                            <p class="text-gray-500 mt-1 font-medium">Welcome back, {{ $user->full_name }}</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="flex items-center px-3 py-1 bg-white border border-gray-200 rounded-lg shadow-sm text-xs font-bold text-gray-600">
                                <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                                System Online
                            </span>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                                    <i class="fas fa-dollar-sign text-xl"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Revenue</h3>
                            <p class="text-3xl font-black text-gray-900 mt-1">RM 0.00</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                                    <i class="fas fa-utensils text-xl"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Menu Items</h3>
                            <p class="text-3xl font-black text-gray-900 mt-1">0</p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Active Staff</h3>
                            <p class="text-3xl font-black text-gray-900 mt-1">1</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold mb-6 flex items-center">
                            <i class="fas fa-bolt text-yellow-500 mr-2"></i> Quick Actions
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center p-4 rounded-xl border border-gray-100 hover:bg-blue-50 hover:border-blue-200 transition group text-center">
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-3 group-hover:bg-blue-600 group-hover:text-white transition">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <span class="text-sm font-bold text-gray-700">Edit Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- GLOBAL LOGOUT FORM -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    @push('css')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            /* Prevents the default Breeze navigation from appearing if it's in the layout */
            nav, .min-h-screen > header { display: none !important; }
            ::-webkit-scrollbar { width: 5px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        </style>
    @endpush
</x-ftadmin-layout>