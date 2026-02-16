<x-app-layout>
{{-- This slot override helps prevent Breeze from injecting headers into the top --}}
<x-slot name="header"></x-slot>

<!-- Main Wrapper (Matching Admin Dashboard Structure) -->
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
                        Account Settings
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

                <div class="flex items-center group cursor-pointer"
                     onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

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

                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">
                        Profile Management
                    </h1>
                    <p class="text-gray-500 mt-1 font-medium">
                        Update your identity details, email address, and phone number.
                    </p>
                </div>

                <!-- Form Sections -->
                <div class="space-y-8">
                    <!-- Profile Information -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    <!-- Password Update -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                    
                    <!-- Delete Account -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
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
        nav, .min-h-screen > header { display: none !important; }
        .py-12 { padding-top: 0 !important; padding-bottom: 0 !important; }
        
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        @media (max-width: 768px) { .sidebar-hidden { transform: translateX(-100%); } }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar  = document.getElementById('sidebar');
            const openBtn  = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            if (openBtn) openBtn.addEventListener('click', () => sidebar.classList.remove('sidebar-hidden'));
            if (closeBtn) closeBtn.addEventListener('click', () => sidebar.classList.add('sidebar-hidden'));
        });
    </script>
@endpush

</x-app-layout>