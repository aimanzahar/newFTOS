<x-ftadmin-layout>

@php
    $user = Auth::user();
    $role = $user->role;
@endphp

<div class="flex flex-col h-full">

    <!-- Top Header -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
            <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center text-gray-400 space-x-2">
                <span class="w-5 flex justify-center"><i class="fas fa-star text-sm"></i></span>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-gray-700">Reviews & Ratings</span>
            </div>
        </div>

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

    <!-- Page Body -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto space-y-4">

            <!-- Page Title Row -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight">Reviews & Ratings</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Customer feedback for your food truck.</p>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <!-- Table Header Bar -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-widest text-gray-500">All Reviews</span>
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <i class="fas fa-star text-amber-400"></i>
                        <span class="font-semibold text-gray-700">—</span>
                        <span class="text-xs">Average Rating</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Menu Item</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Rating</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Review</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty State -->
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                                            <i class="fas fa-star text-2xl text-amber-300"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-500">No Reviews Yet</p>
                                        <p class="text-xs text-gray-400 mt-1">Customer reviews will appear here once orders are completed.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

</x-ftadmin-layout>
