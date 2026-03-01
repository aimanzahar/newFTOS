<x-app-layout>
    <div class="py-12 relative">
        
        {{-- 1. THE BLUR OVERLAY (Same logic for worker) --}}
        @if(Auth::user()->foodTruck && Auth::user()->foodTruck->status !== 'approved')
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-40 backdrop-blur-md">
                <div class="bg-white p-8 rounded-lg shadow-2xl max-w-md text-center border-t-4 border-yellow-500">
                    <div class="mb-4 text-yellow-500">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Truck Not Active</h2>
                    <p class="text-gray-600 mb-6">
                        The food truck you are assigned to is currently pending approval. You will be able to access the kitchen display once the truck is activated.
                    </p>
                    <div class="flex flex-col space-y-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:underline">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. WORKER ACTUAL CONTENT --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold">Kitchen Display System</h1>
                        <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded">FT WORKER</span>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <p class="text-blue-700 font-medium">Shift Status: Active</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Pending Orders Section -->
                        <div class="border rounded-lg p-4">
                            <h2 class="font-bold border-b pb-2 mb-4">Incoming Orders</h2>
                            <div class="text-center py-10 text-gray-400 italic">
                                No pending orders right now.
                            </div>
                        </div>

                        <!-- Completed/History -->
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <h2 class="font-bold border-b pb-2 mb-4 text-gray-600">Recently Completed</h2>
                            <ul class="space-y-2">
                                <li class="text-sm text-gray-500 italic text-center">No recent history</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>