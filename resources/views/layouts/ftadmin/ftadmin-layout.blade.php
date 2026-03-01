<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FTOS Owner') }}</title>

    <!-- Fonts & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Hide default Breeze nav if it exists */
        nav[x-data] { display: none !important; }
        
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .sidebar-hidden { transform: translateX(-100%); }
        @media (min-width: 768px) {
            .sidebar-hidden { transform: translateX(0); }
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-900 bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        @include('layouts.ftadmin.ftadmin-left-navbar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">
            
            <!-- Shared Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 flex-shrink-0 z-30">
                <div class="flex items-center">
                    <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 mr-3">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="flex items-center text-gray-400 space-x-2">
                        <i class="fas fa-truck text-sm"></i>
                        <span class="text-gray-300">/</span>
                        <span class="text-sm font-bold text-gray-700 uppercase tracking-wider">Owner Portal</span>
                    </div>
                </div>

                <div class="flex items-center">
                    <div class="flex items-center space-x-3 bg-gray-50 p-1.5 pr-4 rounded-full border border-gray-100">
                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ substr(Auth::user()->full_name, 0, 1) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-bold text-gray-800 leading-none">{{ Auth::user()->full_name }}</p>
                            <span class="text-[9px] font-black text-green-600 uppercase">Authorized Owner</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 relative">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            if (openBtn) {
                openBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('sidebar-hidden');
                });
            }
        });
    </script>
</body>
</html>