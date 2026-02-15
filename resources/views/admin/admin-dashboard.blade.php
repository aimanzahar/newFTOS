<x-app-layout>
    <!-- Sidebar and Content Wrapper -->
    <div class="flex flex-col md:flex-row min-h-screen bg-gray-50 font-sans leading-normal tracking-normal overflow-x-hidden">
        
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar-transition sidebar-hidden md:transform-none fixed md:relative z-30 w-64 h-screen bg-slate-800 shadow-2xl overflow-y-auto">
            <div class="p-6 text-white text-2xl font-bold flex items-center justify-between border-b border-slate-700">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-blue-400 mr-3 text-xl"></i>
                    <span>Admin Panel</span>
                </div>
                <button id="closeSidebar" class="md:hidden text-gray-400 hover:text-white transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mt-6 px-4 flex flex-col h-[calc(100vh-140px)]">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 px-4">Core</div>
                
                <a href="{{ route('admin.dashboard') }}" class="flex items-center py-3 px-4 rounded-lg transition duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-6"></i> <span>Overview</span>
                </a>
                
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-8 mb-4 px-4">Management</div>

                <a href="{{ route('admin.pending.trucks') }}" class="flex items-center py-3 px-4 rounded-lg transition duration-200 {{ request()->routeIs('admin.pending.trucks') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-slate-700 hover:text-white' }}">
                    <i class="fas fa-truck w-6"></i> <span>Pending Trucks</span>
                </a>

                <a href="#" class="flex items-center py-3 px-4 mt-2 rounded-lg transition duration-200 text-gray-400 hover:bg-slate-700 hover:text-white">
                    <i class="fas fa-users w-6"></i> <span>Manage Users</span>
                </a>

                <a href="#" class="flex items-center py-3 px-4 mt-2 rounded-lg transition duration-200 text-gray-400 hover:bg-slate-700 hover:text-white">
                    <i class="fas fa-chart-bar w-6"></i> <span>Reports</span>
                </a>

                <!-- Spacer -->
                <div class="flex-grow"></div>

                <!-- Logout Button -->
                <form method="POST" action="{{ route('logout') }}" class="mb-8">
                    @csrf
                    <button type="submit" class="w-full flex items-center py-3 px-4 rounded-lg transition duration-200 text-gray-400 hover:bg-red-600 hover:text-white">
                        <i class="fas fa-sign-out-alt w-6"></i> <span>Logout</span>
                    </button>
                </form>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Enhanced Top Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20">
                <!-- Left Side: Mobile Toggle & Page Title -->
                <div class="flex items-center">
                    <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="hidden md:flex items-center text-gray-400">
                        <i class="fas fa-home mr-2 text-sm"></i>
                        <span class="text-gray-300 mx-2">/</span>
                        <span class="text-sm font-medium text-gray-600">Dashboard Overview</span>
                    </div>
                </div>

                <!-- Right Side: Search & User Profile -->
                <div class="flex items-center space-x-6">
                    <!-- Notification Bell (Static) -->
                    <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                    </button>

                    <!-- Divider -->
                    <div class="h-6 w-px bg-gray-200"></div>

                    <!-- User Dropdown Style Profile -->
                    <div class="flex items-center group cursor-pointer">
                        <div class="text-right mr-3 hidden lg:block">
                            <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ Auth::user()->full_name }}</p>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">Super Admin</span>
                        </div>
                        <div class="relative">
                            <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md transform group-hover:scale-105 transition duration-200">
                                {{ substr(Auth::user()->full_name, 0, 1) }}
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <i class="fas fa-chevron-down ml-3 text-xs text-gray-400 group-hover:text-gray-600 transition"></i>
                    </div>
                </div>
            </header>

            <!-- Main Page Content -->
            <main class="p-6 lg:p-10">
                <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">System Overview</h1>
                        <p class="text-gray-500 mt-1 font-medium">Monitoring platform activity and pending approvals.</p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 transition flex items-center">
                            <i class="fas fa-download mr-2"></i> Export Report
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    <!-- Total Trucks -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition duration-300">
                        <div class="flex justify-between items-start">
                            <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                                <i class="fas fa-truck-moving text-2xl"></i>
                            </div>
                            <span class="text-xs font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg">+12%</span>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 font-semibold uppercase tracking-wider">Total Food Trucks</p>
                            <p class="text-3xl font-black text-gray-900 mt-1">{{ $totalTrucks ?? 0 }}</p>
                        </div>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition duration-300">
                        <div class="flex justify-between items-start">
                            <div class="p-3 bg-orange-50 rounded-xl text-orange-600">
                                <i class="fas fa-clock text-2xl"></i>
                            </div>
                            @if(($pendingApprovals ?? 0) > 0)
                                <span class="animate-pulse text-xs font-bold text-red-500 bg-red-50 px-2 py-1 rounded-lg">Action Needed</span>
                            @endif
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 font-semibold uppercase tracking-wider">Pending Approvals</p>
                            <p class="text-3xl font-black text-gray-900 mt-1">{{ $pendingApprovals ?? 0 }}</p>
                        </div>
                        <div class="mt-4 border-t border-gray-50 pt-4">
                            <a href="{{ route('admin.pending.trucks') }}" class="text-sm text-blue-600 hover:text-blue-800 font-bold flex items-center">
                                Review registrations <i class="fas fa-arrow-right ml-2 text-xs"></i>
                            </a>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition duration-300">
                        <div class="flex justify-between items-start">
                            <div class="p-3 bg-green-50 rounded-xl text-green-600">
                                <i class="fas fa-server text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 font-semibold uppercase tracking-wider">System Status</p>
                            <div class="flex items-center mt-2">
                                <div class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2"></div>
                                <p class="text-xl font-bold text-gray-800">Operational</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800">Administrator Toolbox</h3>
                        <i class="fas fa-ellipsis-h text-gray-300"></i>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <a href="{{ route('admin.pending.trucks') }}" class="group flex items-start p-5 bg-gray-50 hover:bg-blue-600 rounded-xl transition duration-300 transform hover:-translate-y-1">
                                <div class="w-12 h-12 bg-white text-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:bg-blue-500 group-hover:text-white transition">
                                    <i class="fas fa-user-check text-xl"></i>
                                </div>
                                <div class="ml-5">
                                    <p class="font-bold text-gray-900 group-hover:text-white transition">Approve Operators</p>
                                    <p class="text-sm text-gray-500 group-hover:text-blue-100 transition">Review and verify documentation for new food truck owners.</p>
                                </div>
                            </a>
                            
                            <a href="#" class="group flex items-start p-5 bg-gray-50 hover:bg-slate-700 rounded-xl transition duration-300 transform hover:-translate-y-1">
                                <div class="w-12 h-12 bg-white text-slate-700 rounded-lg flex items-center justify-center shadow-sm group-hover:bg-slate-600 group-hover:text-white transition">
                                    <i class="fas fa-users-cog text-xl"></i>
                                </div>
                                <div class="ml-5">
                                    <p class="font-bold text-gray-900 group-hover:text-white transition">Manage Platform Users</p>
                                    <p class="text-sm text-gray-500 group-hover:text-slate-200 transition">View, edit, or suspend customer and admin accounts.</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Layout Specific Styles and Scripts -->
    @push('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        @media (max-width: 768px) {
            .sidebar-hidden { transform: translateX(-100%); }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');

            if(openBtn) {
                openBtn.addEventListener('click', () => {
                    sidebar.classList.remove('sidebar-hidden');
                });
            }

            if(closeBtn) {
                closeBtn.addEventListener('click', () => {
                    sidebar.classList.add('sidebar-hidden');
                });
            }
        });
    </script>
    @endpush
</x-app-layout>