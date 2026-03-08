<aside id="sidebar"
class="sidebar-hidden fixed md:static inset-y-0 left-0 z-50 w-64 bg-[#0f172a] shadow-2xl flex flex-col border-r border-slate-800 transition-transform duration-300">

<!-- Branding Header -->
<div class="h-16 flex items-center justify-between px-6 border-b border-slate-800 flex-shrink-0">
    <div class="flex items-center">
        <i class="fas fa-utensils text-orange-400 mr-3 text-xl"></i>
        <span class="text-white font-bold text-lg tracking-tight">Worker Panel</span>
    </div>
    <!-- Mobile Close Button -->
    <button id="closeSidebar" class="md:hidden text-slate-400 hover:text-white">
        <i class="fas fa-times text-xl"></i>
    </button>
</div>

<!-- Navigation Content -->
<div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
    <nav class="flex-1 px-4 py-4 space-y-1">

        <!-- CORE SECTION -->
        <div class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            Core
        </div>

        <a href="{{ route('ftworker.dashboard') }}"
           class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('ftworker.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-chart-line w-6 text-sm"></i>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- ORDERS SECTION -->
        <div class="px-4 py-2 mt-6 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            Orders
        </div>

        <a href="{{ route('ftworker.new-orders') }}"
           class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('ftworker.new-orders') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-clipboard-list w-6 text-sm"></i>
            <span class="text-sm font-medium">New Orders</span>
        </a>

        <!-- SETTINGS SECTION -->
        <div class="px-4 py-2 mt-6 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">
            Settings
        </div>

        <a href="{{ route('profile.edit') }}"
           class="flex items-center py-2.5 px-4 rounded-xl transition duration-200 {{ request()->routeIs('profile.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <i class="fas fa-user-gear w-6 text-sm"></i>
            <span class="text-sm font-medium">My Profile</span>
        </a>
    </nav>
</div>

<!-- Bottom Action Area -->
<div class="p-4 border-t border-slate-800 bg-[#0f172a] flex-shrink-0">
    <form id="logout-form" method="POST" action="{{ route('logout') }}">
        @csrf
    </form>
    <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="group w-full flex items-center py-2.5 px-4 rounded-xl transition duration-200 text-slate-400 hover:bg-red-500/10 hover:text-red-500">
        <i class="fas fa-power-off w-6 text-sm transition-transform group-hover:scale-110"></i>
        <span class="text-sm font-semibold">Logout</span>
    </button>
</div>

</aside>
