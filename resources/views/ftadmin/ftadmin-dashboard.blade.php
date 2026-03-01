<x-ftadmin-layout>
    <div class="p-6 lg:p-10 min-h-full relative">
        
        {{-- THE BLUR OVERLAY (Scoped only to the main content area) --}}
        @if(Auth::user()->foodTruck && Auth::user()->foodTruck->status !== 'approved')
            <div class="absolute inset-0 z-40 flex items-center justify-center bg-gray-50/60 backdrop-blur-md">
                <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md text-center border border-gray-100 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-yellow-400"></div>
                    <div class="mb-4 flex justify-center">
                        <div class="p-4 bg-yellow-50 rounded-full">
                            <i class="fas fa-hourglass-half text-3xl text-yellow-600 animate-pulse"></i>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Registration Pending</h2>
                    <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                        Your food truck profile is currently under review by our administration. 
                        You will gain full access to management tools once approved.
                    </p>
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-widest">Status: Pending Verification</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ACTUAL DASHBOARD CONTENT --}}
        <div class="max-w-7xl mx-auto space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard Overview</h1>
                    <p class="text-sm text-gray-500">Welcome back, {{ Auth::user()->full_name }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="flex items-center px-3 py-1 bg-white border border-gray-200 rounded-lg shadow-sm text-xs font-bold text-gray-600">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        System Online
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Revenue Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Revenue</h3>
                    <p class="text-3xl font-black text-gray-900 mt-1">$0.00</p>
                </div>

                <!-- Menu Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                            <i class="fas fa-utensils text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Menu Items</h3>
                    <p class="text-3xl font-black text-gray-900 mt-1">0</p>
                </div>

                <!-- Staff Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Active Staff</h3>
                    <p class="text-3xl font-black text-gray-900 mt-1">1</p>
                </div>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold mb-6 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i> Quick Actions
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button class="flex flex-col items-center p-4 rounded-xl border border-gray-100 hover:bg-blue-50 hover:border-blue-200 transition group text-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-3 group-hover:bg-blue-600 group-hover:text-white transition">
                            <i class="fas fa-edit"></i>
                        </div>
                        <span class="text-sm font-bold text-gray-700">Edit Profile</span>
                    </button>
                    <!-- Add more buttons as needed -->
                </div>
            </div>
        </div>
    </div>
</x-ftadmin-layout>