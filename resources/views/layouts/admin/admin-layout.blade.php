<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel Admin') }}</title>

    <!-- Fonts & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Force hide any Breeze defaults that might leak in */
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        
        /* Custom scrollbar for the dark sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #1e293b; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        
        @media (max-width: 768px) { 
            .sidebar-hidden { transform: translateX(-100%); } 
        }
    </style>
</head>
<body class="font-sans antialiased h-full overflow-hidden bg-gray-50 text-gray-900">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar Component -->
        @include('layouts.admin.admin-left-navbar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 flex-shrink-0 relative z-10">
                <div class="flex items-center">
                    <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 mr-3">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="hidden md:flex items-center text-gray-400 space-x-2">
                        <i class="fas fa-home text-xs"></i>
                        <span class="text-gray-300">/</span>
                        <span class="text-sm font-semibold text-gray-700">Admin Portal</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-gray-800 leading-none">{{ Auth::user()->full_name }}</p>
                        <span class="text-[10px] font-bold uppercase text-blue-600">Super Admin</span>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                        {{ substr(Auth::user()->full_name, 0, 1) }}
                    </div>
                </div>
            </header>

            <!-- Main Scrollable Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 md:p-8">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            
            if (openBtn) {
                openBtn.addEventListener('click', () => {
                    sidebar.classList.remove('sidebar-hidden');
                });
            }
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    sidebar.classList.add('sidebar-hidden');
                });
            }
        });
    </script>
</body>
</html>