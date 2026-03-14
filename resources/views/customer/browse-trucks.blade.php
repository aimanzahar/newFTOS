@extends('layouts.customer.customer-layout')

@section('header_icon_class', 'fas fa-store')
@section('header_title', 'Browse Food Trucks')

@section('content')

@php $user = Auth::user(); @endphp

@if(!$systemOperational)
<div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="h-1.5 w-full bg-red-500"></div>
    <div class="p-8 text-center">
      <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-server text-2xl text-red-500"></i>
      </div>
      <h2 class="text-xl font-black text-gray-900 mb-2">System Temporarily Offline</h2>
      <p class="text-sm text-gray-500 leading-relaxed">
        The system is temporarily offline. Please check back later.
      </p>
      <a href="javascript:history.back()" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-gray-800 transition-colors">
        <i class="fas fa-arrow-left text-xs"></i>
        Go Back
      </a>
    </div>
  </div>
</div>
@else

<div class="p-6">
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Page Heading -->
        <div class="flex items-center justify-between animate-fade-in-up">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Browse Food Trucks</h1>
                <p class="text-gray-500 mt-1 font-medium text-sm">Discover food trucks that are open right now.</p>
            </div>
            <div class="flex items-center gap-2 bg-white border border-gray-100 rounded-2xl px-4 py-2 shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-sm font-bold text-gray-700">{{ $trucks->count() }} Online</span>
            </div>
        </div>

        @if($trucks->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                    <i class="fas fa-store text-2xl text-gray-300"></i>
                </div>
                <h2 class="text-base font-bold text-gray-700 mb-1">No Food Trucks Online</h2>
                <p class="text-sm text-gray-400">All food trucks are currently offline. Please check back later.</p>
            </div>
        @else
            <!-- Truck Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger-children animate-fade-in">
                @foreach($trucks as $truck)
                    <a href="{{ route('customer.truck-menu', $truck->id) }}"
                       class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-200 overflow-hidden flex flex-col">

                        <!-- Truck Illustration Banner -->
                        <div class="h-28 bg-gradient-to-br from-[#0f172a] to-slate-700 flex items-center justify-center relative overflow-hidden">
                            <i class="fas fa-truck text-5xl text-white/20 absolute -right-4 -bottom-2 rotate-0 scale-150"></i>
                            <div class="relative z-10 text-center px-4">
                                <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mx-auto mb-1">
                                    <i class="fas fa-utensils text-2xl text-amber-400"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-black text-gray-900 text-base leading-tight group-hover:text-amber-600 transition-colors">
                                    {{ $truck->foodtruck_name }}
                                </h3>
                                <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wide text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full flex-shrink-0 ml-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Online
                                </span>
                            </div>

                            @if($truck->foodtruck_desc)
                                <p class="text-xs text-gray-500 leading-relaxed flex-1">
                                    {{ Str::limit($truck->foodtruck_desc, 100) }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400 italic flex-1">No description available.</p>
                            @endif

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    <i class="fas fa-utensils mr-1"></i>{{ $truck->menus_count }} Menu Item{{ $truck->menus_count !== 1 ? 's' : '' }}
                                </span>
                                <span class="text-xs font-black text-amber-600 group-hover:translate-x-1 transition-transform">
                                    Order Now <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</div>

@endif

@endsection
