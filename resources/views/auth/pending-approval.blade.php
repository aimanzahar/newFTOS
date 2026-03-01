<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg text-center">
            <div class="flex justify-center mb-4">
                <svg class="w-20 h-20 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Registration Pending</h2>
            <p class="text-gray-600 mb-6">
                Your food truck application is currently being reviewed by our System Administrators. 
                Please check back soon!
            </p>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 font-medium">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>