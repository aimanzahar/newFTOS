<div id="sidebar"
class="sidebar-transition sidebar-hidden md:transform-none fixed md:relative z-30 w-64 h-screen bg-slate-800 shadow-2xl flex flex-col">

<!-- Branding -->
<div class="p-6 text-white text-2xl font-bold flex items-center justify-between border-b border-slate-700 flex-shrink-0">
    <div class="flex items-center">
        <i class="fas fa-shield-alt text-blue-400 mr-3 text-xl"></i>
        <span>Admin Panel</span>
    </div>

    <button id="closeSidebar"
            class="md:hidden text-gray-400 hover:text-white transition">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Navigation Links -->
<nav class="mt-6 px-4 flex-grow overflow-y-auto">

    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 px-4">
        Core
    </div>

    <a href="{{ route('admin.dashboard') }}"
       class="flex items-center py-3 px-4 rounded-lg transition duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-slate-700 hover:text-white' }}">
        <i class="fas fa-tachometer-alt w-6"></i>
        <span>Overview</span>
    </a>

    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-8 mb-4 px-4">
        Management
    </div>

    <a href="{{ route('admin.pending.trucks') }}"
       class="flex items-center py-3 px-4 rounded-lg transition duration-200 {{ request()->routeIs('admin.pending.trucks') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-slate-700 hover:text-white' }}">
        <i class="fas fa-truck w-6"></i>
        <span>Pending Trucks</span>
    </a>

    <a href="#"
       class="flex items-center py-3 px-4 mt-2 rounded-lg transition duration-200 text-gray-400 hover:bg-slate-700 hover:text-white">
        <i class="fas fa-users w-6"></i>
        <span>Manage Users</span>
    </a>

    <a href="#"
       class="flex items-center py-3 px-4 mt-2 rounded-lg transition duration-200 text-gray-400 hover:bg-slate-700 hover:text-white">
        <i class="fas fa-chart-bar w-6"></i>
        <span>Reports</span>
    </a>

    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-8 mb-4 px-4">
        Account
    </div>

    <a href="{{ route('profile.edit') }}"
       class="flex items-center py-3 px-4 mt-2 rounded-lg transition duration-200 {{ request()->routeIs('profile.edit') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-slate-700 hover:text-white' }}">
        <i class="fas fa-user-cog w-6"></i>
        <span>Settings</span>
    </a>
</nav>

<!-- Logout Section (Pinned to Bottom) -->
<div class="p-4 border-t border-slate-700 bg-slate-800">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="group w-full flex items-center py-3 px-4 rounded-lg transition duration-200 text-gray-400 hover:bg-red-600/10 hover:text-red-500 border border-transparent hover:border-red-500/20">
            <i class="fas fa-sign-out-alt w-6 group-hover:transform group-hover:-translate-x-1 transition-transform"></i>
            <span class="font-semibold">Logout</span>
        </button>
    </form>
</div>


</div>