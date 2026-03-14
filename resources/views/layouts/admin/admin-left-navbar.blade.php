<aside id="sidebar"
       class="sidebar-transition sidebar-hidden md:translate-x-0 fixed md:sticky top-0 left-0 z-50 w-64 h-screen bg-slate-900 shadow-2xl flex flex-col border-r border-slate-800 flex-shrink-0">

    <!-- Branding -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800 flex-shrink-0">
        <div class="flex items-center">
            <i class="fas fa-shield-alt text-blue-500 mr-3 text-xl"></i>
            <span class="text-white font-bold text-lg tracking-tight">Admin Panel</span>
        </div>

        <button id="closeSidebar" class="md:hidden text-slate-400 hover:text-white transition">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 mt-4 px-4 sidebar-scroll overflow-y-auto space-y-1 pb-4">

        <!-- SECTION: CORE -->
        <div class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            Core
        </div>

        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
            <i class="fas fa-chart-pie w-6 text-sm"></i>
            <span class="text-sm font-medium">Overview</span>
        </a>

        <!-- SECTION: TRUCK OPERATIONS -->
        <div class="px-4 py-2 mt-6 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            Operations
        </div>

        <!-- Handle Truck Registration - Updated Icon to Clock -->
        <a href="{{ route('admin.pending.trucks') }}"
           class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('admin.pending.trucks') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
            <i class="fas fa-clock w-6 text-sm"></i>
            <span class="text-sm font-medium">Pending Approvals</span>
        </a>

        <!-- Handle Truck Accounts -->
        <a href="{{ route('admin.approved.trucks') }}" class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('admin.approved.trucks') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
            <i class="fas fa-truck-loading w-6 text-sm"></i>
            <span class="text-sm font-medium">Approved Trucks</span>
        </a>

        <!-- Directly Handle Truck Menus -->
        <a href="{{ route('admin.global-menus') }}" class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('admin.global-menus') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
            <i class="fas fa-utensils w-6 text-sm"></i>
            <span class="text-sm font-medium">Global Menus</span>
        </a>

        <!-- SECTION: USERS -->
        <div class="px-4 py-2 mt-6 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            User Management
        </div>

        <!-- Handle Customer Accounts -->
        <a href="#" class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 text-slate-400 hover:bg-slate-800 hover:text-slate-100">
            <i class="fas fa-users w-6 text-sm"></i>
            <span class="text-sm font-medium">Customer Database</span>
        </a>

        <!-- View Customer Reviews/Ratings -->
        <a href="#" class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 text-slate-400 hover:bg-slate-800 hover:text-slate-100">
            <i class="fas fa-star w-6 text-sm"></i>
            <span class="text-sm font-medium">Reviews & Moderation</span>
        </a>

        <!-- SECTION: SYSTEM & ANALYTICS -->
        <div class="px-4 py-2 mt-6 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            System Control
        </div>

        <!-- View System Reports & Analytics -->
        <a href="#" class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 text-slate-400 hover:bg-slate-800 hover:text-slate-100">
            <i class="fas fa-chart-line w-6 text-sm"></i>
            <span class="text-sm font-medium">Sales Analytics</span>
        </a>

        <!-- Manage System Configuration -->
        <a href="#" class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 text-slate-400 hover:bg-slate-800 hover:text-slate-100">
            <i class="fas fa-sliders-h w-6 text-sm"></i>
            <span class="text-sm font-medium">Platform Config</span>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('profile.edit') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
            <i class="fas fa-user-shield w-6 text-sm"></i>
            <span class="text-sm font-medium">Profile Settings</span>
        </a>
    </nav>

    <!-- Logout Section -->
    <div class="p-4 border-t border-slate-800 bg-slate-900">
        <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                class="group w-full flex items-center py-2.5 px-4 rounded-xl transition duration-200 text-slate-400 hover:bg-red-500/10 hover:text-red-500">
            <i class="fas fa-power-off w-6 text-sm transition-transform group-hover:scale-110"></i>
            <span class="text-sm font-semibold">Logout</span>
        </button>
    </div>
</aside>
<!-- Sidebar Backdrop -->
<div id="sidebarBackdrop" class="hidden md:hidden fixed inset-0 bg-black/50 z-40 transition-opacity"></div>