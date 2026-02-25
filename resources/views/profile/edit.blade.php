<x-app-layout>
{{-- Hide default navigation --}}
<x-slot name="header"></x-slot>

@php
    $user = Auth::user();
    $role = (int)$user->role;

    /** * Logic based on folder structure:
     * Role 6: admin/admin-left-navbar
     * Role 2: ftadmin/ftadmin-left-navbar
     * Role 3: ftworker/ftworker-left-navbar
     * Role 1: customer/customer-left-navbar
     */
@endphp

<!-- Main Wrapper -->
<div class="fixed inset-0 flex h-screen bg-[#F8FAFC] font-sans antialiased overflow-hidden z-50">

    <!-- Sidebar Selection based ons folder structure -->
    <aside class="flex-shrink-0">
        @if($role === 6)
            @include('layouts.admin.admin-left-navbar')
        @elseif($role === 2)
            @include('layouts.ftadmin.ftadmin-left-navbar')
        @elseif($role === 3)
            @include('layouts.ftworker.ftworker-left-navbar')
        @elseif($role === 1)
            @include('layouts.customer.customer-left-navbar')
        @else
            @include('layouts.navigation')
        @endif
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

        <!-- Top Header -->
        <header class="bg-white border-b border-gray-100 h-16 flex items-center justify-between px-8 z-20 flex-shrink-0">
            <div class="flex items-center text-sm font-medium">
                <span class="text-gray-400">/</span>
                <span class="ml-2 text-gray-700">Account Settings</span>
            </div>

            <div class="flex items-center space-x-6">
                <div class="flex items-center space-x-3 border-l pl-6 border-gray-100">
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-900">{{ $user->full_name }}</p>
                        <span class="text-[10px] font-black uppercase tracking-tighter text-blue-500 bg-blue-50 px-2 py-0.5 rounded">
                            @switch($role)
                                @case(6) SYSTEM ADMIN @break
                                @case(2) FT ADMIN @break
                                @case(3) FT WORKER @break
                                @default CUSTOMER
                            @endswitch
                        </span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-[#2D3748] flex items-center justify-center text-white font-bold text-sm relative">
                        {{ strtoupper(substr($user->full_name, 0, 1)) }}
                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-8 bg-gray-50/50 scroll-smooth">
            <div class="max-w-5xl">
                
                <div class="mb-10">
                    <h1 class="text-2xl font-bold text-gray-900">Profile Management</h1>
                    <p class="text-gray-500 text-sm mt-1">Update your identity details, email address, and phone number.</p>
                </div>

                <div class="space-y-8">
                    <!-- Update Profile Form -->
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    <!-- Update Password Form -->
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <!-- Delete Account -->
                    <div class="bg-white rounded-2xl p-8 border border-red-50 shadow-sm">
                        <div class="max-w-2xl text-red-600">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@push('css')
<style>
    /* Hide default breeze nav/header */
    nav, .bg-white.shadow { display: none !important; }
    
    /* Modern Input Styling */
    input[type="text"], input[type="email"], input[type="tel"], input[type="password"] {
        border: 1px solid #E2E8F0 !important;
        border-radius: 8px !important;
        padding: 10px 14px !important;
        width: 100% !important;
        font-size: 14px !important;
        color: #1A202C !important;
    }

    input:focus {
        border-color: #3B82F6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
    }

    /* Modern Primary Button */
    button[type="submit"], .inline-flex.items-center.px-4.py-2.bg-gray-800 {
        background-color: #2563EB !important;
        color: white !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 11px !important;
        letter-spacing: 0.05em !important;
        padding: 12px 24px !important;
        border: none !important;
        transition: opacity 0.2s;
    }

    button:hover { opacity: 0.9; }
</style>
@endpush


</x-app-layout>