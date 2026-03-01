@extends('layouts.customer.customer-layout')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
{{ __('Customer Dashboard') }}
</h2>
@endsection

@section('content')

<div class="py-12">
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
<!-- Welcome Header -->
<div class="mb-8">
<h1 class="text-3xl font-bold text-gray-900">Welcome back, {{ Auth::user()->name }}! 👋</h1>
<p class="text-gray-600">What are you craving today?</p>
</div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
            <div class="flex items-center">
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-shopping-bag text-indigo-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase font-semibold">Active Orders</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-amber-500">
            <div class="flex items-center">
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="fas fa-history text-amber-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase font-semibold">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-emerald-500">
            <div class="flex items-center">
                <div class="p-3 bg-emerald-100 rounded-full">
                    <i class="fas fa-star text-emerald-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 uppercase font-semibold">Reviews Given</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action Section -->
    <div class="bg-indigo-900 rounded-2xl shadow-xl overflow-hidden mb-8">
        <div class="px-8 py-12 md:flex items-center justify-between">
            <div class="mb-6 md:mb-0">
                <h2 class="text-2xl font-bold text-white mb-2">Ready to eat?</h2>
                <p class="text-indigo-200">Browse through the best food trucks in your area and order now.</p>
            </div>
            <a href="#" class="inline-flex items-center px-6 py-3 bg-amber-400 hover:bg-amber-300 text-amber-900 font-bold rounded-lg transition duration-200 shadow-lg">
                <i class="fas fa-search mr-2"></i>
                Explore Food Trucks
            </a>
        </div>
    </div>

    <!-- Recent Activity Placeholder -->
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 uppercase text-sm tracking-wider">Recent Orders</h3>
            <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold">View All</a>
        </div>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-receipt text-5xl mb-4 opacity-20"></i>
            <p>No recent orders found. Time to grab some lunch!</p>
        </div>
    </div>
</div>


</div>
@endsection