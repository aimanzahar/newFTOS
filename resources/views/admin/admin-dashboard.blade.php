<x-app-layout>
    <x-slot name="header"></x-slot>

    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between animate-fade-in-up">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">
                System Overview
            </h1>
            <p class="text-gray-500 mt-1 font-medium">
                Monitoring platform activity and pending approvals.
            </p>
        </div>

        <div class="mt-4 sm:mt-0">
            <button
                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 transition flex items-center">
                <i class="fas fa-download mr-2 text-blue-500"></i>
                Export Report
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10 stagger-children">

        <!-- Approved Food Trucks -->
        <button onclick="window.location.href='{{ route('admin.approved.trucks') }}'"
            class="text-left bg-white p-5 sm:p-8 rounded-3xl shadow-sm border border-gray-100 hover:border-emerald-300 hover:shadow-md transition-all group outline-none w-full cursor-pointer">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <div class="p-4 w-fit bg-emerald-50 text-emerald-600 rounded-2xl group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                        <i class="fas fa-truck-fast text-2xl"></i>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg uppercase">
                            Active
                        </span>
                        <i class="fas fa-expand-alt text-gray-300 text-sm group-hover:text-emerald-500 transition-colors flex-shrink-0"></i>
                    </div>
                </div>

                <p
                    class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">
                    Approved Trucks
                </p>
                <p class="text-5xl font-black text-gray-900 mt-1">
                    {{ $approvedTrucks ?? 0 }}
                </p>
            </div>

            <div class="mt-4 border-t border-gray-50 pt-4">
                <span class="text-xs font-bold text-emerald-500 uppercase tracking-widest">
                    View All Trucks
                </span>
            </div>
        </button>

        <!-- Pending Approvals -->
        <button onclick="window.location.href='{{ route('admin.pending.trucks') }}'"
            class="text-left bg-white p-5 sm:p-8 rounded-3xl shadow-sm border border-gray-100 hover:border-orange-300 hover:shadow-md transition-all group outline-none w-full cursor-pointer">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <div class="p-4 w-fit bg-orange-50 text-orange-600 rounded-2xl group-hover:bg-orange-600 group-hover:text-white transition-all duration-300">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>

                    <div class="flex items-center gap-3">
                        @if(($pendingApprovals ?? 0) > 0)
                            <span
                                class="animate-pulse text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 rounded-lg uppercase">
                                Action Needed
                            </span>
                        @endif
                        <i class="fas fa-expand-alt text-gray-300 text-sm group-hover:text-orange-500 transition-colors flex-shrink-0"></i>
                    </div>
                </div>

                <p
                    class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">
                    Pending Approvals
                </p>
                <p class="text-5xl font-black text-gray-900 mt-1">
                    {{ $pendingApprovals ?? 0 }}
                </p>
            </div>

            <div class="mt-4 border-t border-gray-50 pt-4">
                <span class="text-xs font-bold text-orange-500 uppercase tracking-widest">
                    Review Registrations
                </span>
            </div>
        </button>

        <!-- System Status -->
        <div
            class="bg-white p-5 sm:p-8 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <div class="p-4 w-fit bg-blue-50 text-blue-600 rounded-2xl">
                        <i class="fas fa-server text-2xl"></i>
                    </div>
                </div>

                <p
                    class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">
                    System Status
                </p>
                <div class="flex items-center gap-2 mt-1">
                    <div class="w-2.5 h-2.5 bg-green-500 rounded-full flex-shrink-0 animate-pulse"></div>
                    <p class="text-3xl sm:text-5xl font-black text-gray-900">
                        Operational
                    </p>
                </div>
            </div>

            <div class="mt-4 border-t border-gray-50 pt-4">
                <p class="text-xs text-gray-400 font-medium">
                    All systems running normally
                </p>
            </div>
        </div>

    </div>

    <!-- Administrator Toolbox -->
    <div
        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up">
        <div
            class="px-8 py-5 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">
                Administrator Toolbox
            </h3>
        </div>

        <div class="p-4 sm:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <a href="{{ route('admin.pending.trucks') }}"
                    class="group flex items-start p-5 bg-gray-50 hover:bg-blue-600 rounded-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div
                        class="w-12 h-12 bg-white text-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:bg-blue-500 group-hover:text-white transition">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <p
                            class="font-bold text-gray-900 group-hover:text-white transition">
                            Approve Operators //change this button name and page//
                        </p>
                        <p
                            class="text-sm text-gray-500 group-hover:text-blue-100 transition">
                            Review and verify documentation.
                        </p>
                    </div>
                </a>

                <a href="#"
                    class="group flex items-start p-5 bg-gray-50 hover:bg-slate-700 rounded-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div
                        class="w-12 h-12 bg-white text-slate-700 rounded-lg flex items-center justify-center shadow-sm group-hover:bg-slate-600 group-hover:text-white transition">
                        <i class="fas fa-users-cog text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <p
                            class="font-bold text-gray-900 group-hover:text-white transition">
                            Manage Platform Users
                        </p>
                        <p
                            class="text-sm text-gray-500 group-hover:text-slate-200 transition font-medium">
                            View or edit user accounts.
                        </p>
                    </div>
                </a>

            </div>
        </div>
    </div>

</x-app-layout>
