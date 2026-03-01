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
                            Pending Approvals
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
                                Pending Food Truck Registrations
                            </h1>
                            <p class="text-gray-500 mt-1 font-medium">
                                Review new food truck applications for approval.
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
                                        <th class="px-6 py-4">Applied On</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-50">
                                    @forelse($pendingRegistrations as $truck)
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
                                                {{ $truck->created_at->format('M d, Y') }}
                                                <span class="block text-[10px] text-gray-400 uppercase tracking-tighter">
                                                    {{ $truck->created_at->diffForHumans() }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end space-x-2">

                                                    <!-- Approve Button -->
                                                    <form
                                                        action="{{ route('admin.approve-truck', $truck->id) }}"
                                                        method="POST"
                                                        class="inline-block"
                                                        onsubmit="return confirm('Approve this registration?');"
                                                    >
                                                        @csrf
                                                        <button
                                                            type="submit"
                                                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                                        >
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <!-- Reject Button -->
                                                    <form
                                                        action="{{ route('admin.reject-truck', $truck->id) }}"
                                                        method="POST"
                                                        class="inline-block"
                                                        onsubmit="return confirm('Are you sure you want to reject and delete this registration? This action cannot be undone.');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                                        >
                                                            Reject
                                                        </button>
                                                    </form>

                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-20 text-center text-gray-400 bg-white">
                                                <div class="flex flex-col items-center">
                                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                                                        <i class="fas fa-clipboard-check text-3xl"></i>
                                                    </div>
                                                    <p class="text-lg font-bold text-gray-500">
                                                        No Pending Applications
                                                    </p>
                                                    <p class="text-sm text-gray-400">
                                                        Everything is caught up!
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($pendingRegistrations->hasPages())
                        <div class="mt-8">
                            {{ $pendingRegistrations->links() }}
                        </div>
                    @endif

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

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
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
