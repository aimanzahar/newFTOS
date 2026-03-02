<x-ftadmin-layout>
<x-slot name="header_title">Dashboard</x-slot>

@php
    $user = Auth::user();
    $role = $user->role; 
@endphp

<div x-data="{ showStaffModal: false }" class="relative min-h-full flex flex-col">
    
    <!-- Fixed Top Header -->
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

    {{-- Main Dashboard Content --}}
    <div class="relative flex-1">
        {{-- THE BLUR OVERLAY (Scoped only to the main content area) --}}
        @if($user->foodTruck && $user->foodTruck->status !== 'approved')
            <div class="absolute inset-0 z-40 flex items-center justify-center bg-gray-50/60 backdrop-blur-md">
                <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md text-center border border-gray-100 relative overflow-hidden mx-4">
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
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-widest">Status: {{ ucfirst($user->foodTruck->status) }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="p-6 lg:p-10 overflow-y-auto h-full">
            <div class="w-full max-w-[1400px] mx-auto space-y-8">
                
                <!-- Content Header Section -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 tracking-tight">Overview</h1>
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
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group">
                        <div class="p-3 w-fit bg-blue-50 text-blue-600 rounded-xl mb-4">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Revenue</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">RM 0.00</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group">
                        <div class="p-3 w-fit bg-purple-50 text-purple-600 rounded-xl mb-4">
                            <i class="fas fa-utensils text-xl"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Menu Items</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">0</p>
                    </div>

                    <!-- Active Staff Card -->
                    <button @click="showStaffModal = true" class="text-left bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-blue-300 hover:shadow-md transition-all group outline-none">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <i class="fas fa-expand-alt text-gray-300 group-hover:text-blue-500"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Active Staff</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">{{ count($ftworkers ?? []) }}</p>
                        <span class="text-[10px] font-bold text-blue-500 mt-2 block opacity-0 group-hover:opacity-100 transition-opacity uppercase text-xs">View Details</span>
                    </button>
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
    </div>

    <!-- STAFF LIST MODAL -->
    <div x-show="showStaffModal" 
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
         style="display: none;"
         x-transition
         @keydown.escape.window="showStaffModal = false">
        
        <div @click.away="showStaffModal = false" 
             class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-orange-100 text-orange-600 p-2 rounded-lg">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">Staff Management</h2>
                </div>
                <button @click="showStaffModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <!-- Modal Inner Header with Create Button -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest">Active Members</h3>
                    <a href="#" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-sm transition-all active:scale-95">
                        <i class="fas fa-plus mr-2 text-xs"></i>
                        Create Staff Member
                    </a>
                </div>

                <table class="w-full">
                    <thead>
                        <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
                            <th class="pb-4 text-left px-2">Staff Name</th>
                            <th class="pb-4 text-left px-2">Email</th>
                            <th class="pb-4 text-left px-2">Username</th>
                            <th class="pb-4 text-right px-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($ftworkers ?? [] as $worker)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="py-4 px-2">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs mr-3">
                                            {{ substr($worker->full_name, 0, 1) }}
                                        </div>
                                        <span class="text-sm font-bold text-gray-700">{{ $worker->full_name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-2 text-sm text-gray-500 font-medium">{{ $worker->email }}</td>
                                <td class="py-4 px-2 text-xs font-mono text-gray-400">
                                    <span class="bg-gray-100 px-2 py-1 rounded">{{ $worker->username }}</span>
                                </td>
                                <td class="py-4 px-2 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-green-50 text-green-600 border border-green-100">
                                        <span class="w-1 h-1 rounded-full bg-green-500 mr-1.5"></span>
                                        Active
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-user-slash text-4xl text-gray-200 mb-4"></i>
                                        <h3 class="text-gray-900 font-bold">No Staff Found</h3>
                                        <p class="text-gray-400 text-sm mt-1">Start by creating your first staff member.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button @click="showStaffModal = false" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 hover:border-gray-300 transition shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


</x-ftadmin-layout>