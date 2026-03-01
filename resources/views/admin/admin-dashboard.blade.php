<x-app-layout>
    {{-- This slot override helps prevent Breeze from injecting headers into the top --}}
    <x-slot name="header"></x-slot>

    <!--
    FIX: Added 'absolute inset-0' and 'z-50' to ensure this dashboard
    covers any unwanted padding or navs injected by the parent x-app-layout.
    -->

    <div class="fixed inset-0 flex h-screen bg-gray-50 font-sans antialiased overflow-hidden z-50">

        <!-- Sidebar Component (Fixed Position) -->
        @include('layouts.admin.admin-left-navbar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

            <!-- Fixed Top Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">

                <!-- Left Side: Mobile Toggle & Breadcrumbs -->
                <div class="flex items-center">
                    <button id="openSidebar"
                        class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="hidden md:flex items-center text-gray-400 space-x-2">
                        <i class="fas fa-home text-sm"></i>
                        <span class="text-gray-300">/</span>
                        <span class="text-sm font-bold text-gray-700">
                            Overview
                        </span>
                    </div>
                </div>

                <!-- Right Side: Notifications & Profile -->
                <div class="flex items-center space-x-6">
                    <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
                        <i class="fas fa-bell"></i>
                        <span
                            class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                    </button>

                    <div class="h-6 w-px bg-gray-200"></div>

                    <div class="flex items-center group cursor-pointer"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

                        <div class="text-right mr-3 hidden lg:block">
                            <p class="text-sm font-bold text-gray-800 leading-none mb-1">
                                {{ Auth::user()->full_name }}
                            </p>
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                Super Admin
                            </span>
                        </div>

                        <div class="relative">
                            <div
                                class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md group-hover:scale-105 transition-all">
                                {{ substr(Auth::user()->full_name, 0, 1) }}
                            </div>
                            <div
                                class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full">
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content Section -->
            <main class="flex-1 overflow-y-auto p-6 lg:p-10 scroll-smooth">
                <div class="max-w-7xl mx-auto">

                    <!-- Page Header -->
                    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">

                        <!-- Approved Food Trucks -->
                        <div
                            class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                                    <i class="fas fa-truck-fast text-2xl"></i>
                                </div>
                                <span
                                    class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg uppercase">
                                    Active
                                </span>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-sm text-gray-500 font-semibold uppercase tracking-wider">
                                    Approved Trucks
                                </p>
                                <p class="text-3xl font-black text-gray-900 mt-1">
                                    {{ $approvedTrucks ?? 0 }}
                                </p>
                            </div>

                            <div class="mt-4 border-t border-gray-50 pt-4">
                                <p class="text-xs text-gray-400">
                                    Currently operating on the platform
                                </p>
                            </div>
                        </div>

                        <!-- Pending Approvals -->
                        <div
                            class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div class="p-3 bg-orange-50 rounded-xl text-orange-600">
                                    <i class="fas fa-clock text-2xl"></i>
                                </div>

                                @if(($pendingApprovals ?? 0) > 0)
                                    <span
                                        class="animate-pulse text-xs font-bold text-red-500 bg-red-50 px-2 py-1 rounded-lg">
                                        Action Needed
                                    </span>
                                @endif
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-sm text-gray-500 font-semibold uppercase tracking-wider">
                                    Pending Approvals
                                </p>
                                <p class="text-3xl font-black text-gray-900 mt-1">
                                    {{ $pendingApprovals ?? 0 }}
                                </p>
                            </div>

                            <div class="mt-4 border-t border-gray-50 pt-4">
                                <a href="{{ route('admin.pending.trucks') }}"
                                    class="text-sm text-blue-600 hover:text-blue-800 font-bold flex items-center">
                                    Review registrations
                                    <i class="fas fa-arrow-right ml-2 text-xs"></i>
                                </a>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div
                            class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                                    <i class="fas fa-server text-2xl"></i>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p
                                    class="text-sm text-gray-500 font-semibold uppercase tracking-wider">
                                    System Status
                                </p>
                                <div class="flex items-center mt-2">
                                    <div class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2"></div>
                                    <p class="text-xl font-bold text-gray-800">
                                        Operational
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Administrator Toolbox -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div
                            class="px-8 py-5 border-b border-gray-50 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800">
                                Administrator Toolbox
                            </h3>
                        </div>

                        <div class="p-8">
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

                </div>
            </main>
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

            ::-webkit-scrollbar-track {
                background: transparent;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            .sidebar-transition {
                transition: all 0.3s ease-in-out;
            }

            @media (max-width: 768px) {
                .sidebar-hidden {
                    transform: translateX(-100%);
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const openBtn = document.getElementById('openSidebar');
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
