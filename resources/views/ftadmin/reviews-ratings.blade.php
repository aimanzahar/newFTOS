<x-ftadmin-layout>

@php
    $user = Auth::user();
    $role = $user->role;
@endphp

<div class="flex flex-col h-full">

    <!-- Page Body -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-6xl mx-auto space-y-4">

            <!-- Page Title Row -->
            <div class="flex items-center justify-between animate-fade-in-up">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight">Reviews & Ratings</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Customer feedback for your food truck.</p>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in">

                <!-- Table Header Bar -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-widest text-gray-500">All Reviews</span>
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <i class="fas fa-star text-amber-400"></i>
                        <span class="font-semibold text-gray-700">—</span>
                        <span class="text-xs">Average Rating</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Menu Item</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Rating</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Review</th>
                                <th class="text-left px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty State -->
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                                            <i class="fas fa-star text-2xl text-amber-300"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-500">No Reviews Yet</p>
                                        <p class="text-xs text-gray-400 mt-1">Customer reviews will appear here once orders are completed.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

</x-ftadmin-layout>
