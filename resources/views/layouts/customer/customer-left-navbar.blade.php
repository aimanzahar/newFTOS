<div id="sidebar"
class="sidebar-transition sidebar-hidden md:transform-none fixed md:relative z-30 w-64 h-screen bg-indigo-950 shadow-2xl flex flex-col">

<!-- Branding -->
<div class="p-6 text-white text-2xl font-bold flex items-center justify-between border-b border-indigo-900/50 flex-shrink-0">
    <div class="flex items-center">
        <i class="fas fa-utensils text-amber-400 mr-3 text-xl"></i>
        <span>FoodieApp</span>
    </div>

    <button id="closeSidebar"
            class="md:hidden text-indigo-300 hover:text-white transition">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Navigation Links -->
<nav class="mt-6 px-4 flex-grow overflow-y-auto custom-scrollbar">

    <!-- EXPLORE SECTION -->
    <div class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-4 px-4">
        Hungry?
    </div>

    <a href="#"
       class="flex items-center py-3 px-4 rounded-lg transition duration-200 text-indigo-100 hover:bg-indigo-800/50 hover:text-white">
        <i class="fas fa-search w-6"></i>
        <span>Browse Trucks</span>
    </a>

    <a href="#"
       class="flex items-center py-3 px-4 mt-1 rounded-lg transition duration-200 text-indigo-100 hover:bg-indigo-800/50 hover:text-white">
        <i class="fas fa-qrcode w-6"></i>
        <span>Scan QR Code</span>
    </a>

    <!-- MY ORDERS -->
    <div class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mt-8 mb-4 px-4">
        My Activity
    </div>

    <a href="#"
       class="flex items-center py-3 px-4 rounded-lg transition duration-200 text-indigo-100 hover:bg-indigo-800/50 hover:text-white">
        <i class="fas fa-shopping-bag w-6"></i>
        <span>Current Order</span>
    </a>

    <a href="#"
       class="flex items-center py-3 px-4 mt-1 rounded-lg transition duration-200 text-indigo-100 hover:bg-indigo-800/50 hover:text-white">
        <i class="fas fa-history w-6"></i>
        <span>Order History</span>
    </a>

    <a href="#"
       class="flex items-center py-3 px-4 mt-1 rounded-lg transition duration-200 text-indigo-100 hover:bg-indigo-800/50 hover:text-white">
        <i class="fas fa-star w-6"></i>
        <span>My Reviews</span>
    </a>

    <!-- WALLET & PAYMENTS -->
    <div class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mt-8 mb-4 px-4">
        Finance
    </div>

    <a href="#"
       class="flex items-center py-3 px-4 rounded-lg transition duration-200 text-indigo-100 hover:bg-indigo-800/50 hover:text-white">
        <i class="fas fa-wallet w-6"></i>
        <span>Payment Methods</span>
    </a>

    <!-- ACCOUNT SETTINGS -->
    <div class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mt-8 mb-4 px-4">
        Personal
    </div>

    <a href="{{ route('profile.edit') }}"
       class="flex items-center py-3 px-4 rounded-lg transition duration-200 {{ request()->routeIs('profile.edit') ? 'bg-indigo-600 text-white shadow-lg' : 'text-indigo-100 hover:bg-indigo-800/50 hover:text-white' }}">
        <i class="fas fa-user-circle w-6"></i>
        <span>Edit Profile</span>
    </a>
</nav>

<!-- Logout Section -->
<div class="p-4 border-t border-indigo-900/50 bg-indigo-950">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="group w-full flex items-center py-3 px-4 rounded-lg transition duration-200 text-indigo-300 hover:bg-rose-600/10 hover:text-rose-400 border border-transparent hover:border-rose-500/20">
            <i class="fas fa-power-off w-6 group-hover:transform group-hover:scale-110 transition-transform"></i>
            <span class="font-semibold">Sign Out</span>
        </button>
    </form>
</div>


</div>

<style>
/* Subtle scrollbar for the nav */
.custom-scrollbar::-webkit-scrollbar {
width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
background: rgba(129, 140, 248, 0.2);
border-radius: 10px;
}
</style>