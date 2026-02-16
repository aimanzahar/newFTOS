<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
        /* Force hide Breeze defaults */
        nav, .min-h-screen > header { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        @media (max-width: 768px) { .sidebar-hidden { transform: translateX(-100%); } }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="fixed inset-0 flex h-screen bg-gray-50 overflow-hidden z-50">
        
        <!-- Sidebar -->
        @include('layouts.admin.admin-left-navbar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
            
            <!-- Shared Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
                <div class="flex items-center">
                    <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 mr-3">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="hidden md:flex items-center text-gray-400 space-x-2">
                        <i class="fas fa-home text-sm"></i>
                        <span class="text-gray-300">/</span>
                        <span class="text-sm font-bold text-gray-700">Admin Portal</span>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center group cursor-pointer" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <div class="text-right mr-3 hidden lg:block">
                            <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ Auth::user()->full_name }}</p>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Super Admin</span>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md">
                            {{ substr(Auth::user()->full_name, 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 lg:p-10 scroll-smooth">
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
            if (openBtn) openBtn.addEventListener('click', () => sidebar.classList.remove('sidebar-hidden'));
            if (closeBtn) closeBtn.addEventListener('click', () => sidebar.classList.add('sidebar-hidden'));
        });
    </script>
</body>
</html>