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
        <div x-data="{ showModal: false, isOperational: @json($systemOperational), toggling: false }"
            @click="showModal = true"
            class="bg-white p-5 sm:p-8 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition cursor-pointer group">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <div :class="isOperational ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'" class="p-4 w-fit rounded-2xl transition-colors">
                        <i class="fas fa-server text-2xl"></i>
                    </div>
                    <i class="fas fa-expand-alt text-gray-300 text-sm group-hover:text-blue-500 transition-colors flex-shrink-0"></i>
                </div>

                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">
                    System Status
                </p>
                <div class="flex items-center gap-2 mt-1">
                    <div :class="isOperational ? 'bg-green-500 animate-pulse' : 'bg-red-500'" class="w-2.5 h-2.5 rounded-full flex-shrink-0"></div>
                    <p class="text-3xl sm:text-5xl font-black text-gray-900" x-text="isOperational ? 'Operational' : 'Offline'"></p>
                </div>
            </div>

            <div class="mt-4 border-t border-gray-50 pt-4">
                <p class="text-xs font-medium" :class="isOperational ? 'text-gray-400' : 'text-red-400'" x-text="isOperational ? 'All systems running normally' : 'System is currently offline'"></p>
            </div>

            <!-- System Toggle Modal -->
            <template x-teleport="body">
                <div x-show="showModal" x-cloak @click.self="showModal = false"
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div @click.stop class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden"
                        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        <div :class="isOperational ? 'bg-green-500' : 'bg-red-500'" class="h-1.5 w-full transition-colors"></div>
                        <div class="p-8 text-center">
                            <div :class="isOperational ? 'bg-green-50' : 'bg-red-50'" class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i :class="isOperational ? 'text-green-500' : 'text-red-500'" class="fas fa-power-off text-2xl"></i>
                            </div>
                            <h2 class="text-xl font-black text-gray-900 mb-2">System Operational Status</h2>
                            <p class="text-sm text-gray-500 mb-2">
                                The system is currently <span :class="isOperational ? 'text-green-600 font-bold' : 'text-red-600 font-bold'" x-text="isOperational ? 'ONLINE' : 'OFFLINE'"></span>.
                            </p>
                            <p class="text-xs text-gray-400 mb-6">
                                Turning the system off will show a blocking overlay to all Food Truck Admins and Workers, preventing them from using the platform until it is turned back on.
                            </p>

                            <div class="flex gap-3">
                                <button @click="showModal = false" class="flex-1 px-4 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button @click="
                                    toggling = true;
                                    fetch('{{ route('admin.toggle-system-operational') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                    })
                                    .then(r => r.json())
                                    .then(data => { if (data.success) isOperational = data.is_operational; })
                                    .finally(() => { toggling = false; showModal = false; })
                                " :disabled="toggling"
                                :class="isOperational ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'"
                                class="flex-1 px-4 py-3 rounded-xl text-white font-bold text-sm transition disabled:opacity-50">
                                    <span x-show="!toggling" x-text="isOperational ? 'Turn OFF' : 'Turn ON'"></span>
                                    <span x-show="toggling"><i class="fas fa-spinner fa-spin mr-1"></i> Toggling...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
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
