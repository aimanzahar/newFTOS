<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-hidden">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'FTOS Owner') }}</title>

  <!-- Fonts & Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- Scripts -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    /* Hide default Breeze nav if it exists */
    nav[x-data] { display: none !important; }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .sidebar-hidden { transform: translateX(-100%); }
    @media (min-width: 768px) {
      .sidebar-hidden { transform: translateX(0); }
    }
  </style>
</head>
<body class="font-sans antialiased text-slate-900 bg-gray-50 h-full overflow-hidden">
  <div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    @include('layouts.ftadmin.ftadmin-left-navbar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">

      

      <!-- Page Content -->
      <main class="flex-1 overflow-hidden bg-gray-50 relative">
        {{ $slot }}
      </main>
    </div>
  </div>

  @if(Auth::check() && (Auth::user()->status == 'pending' || Auth::user()->status == 'rejected'))
  <!-- Registration Status Overlay -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md mx-4">
      @if(Auth::user()->status == 'rejected')
        <!-- Rejection Message -->
        <div class="mb-4">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-circle text-3xl text-red-600"></i>
          </div>
        </div>
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Application Rejected</h2>
        <p class="mb-6 text-gray-600">We're sorry, but your food truck registration application does not meet our requirements at this time. Please contact our support team for more information.</p>
      @else
        <!-- Pending Message -->
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Registration Pending</h2>
        <p class="mb-6 text-gray-600">Your food truck profile is currently under review. You will gain full access once approved.</p>
      @endif
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="@if(Auth::user()->status == 'rejected') bg-red-500 hover:bg-red-600 @else bg-blue-500 hover:bg-blue-600 @endif text-white px-6 py-2 rounded-lg font-semibold transition duration-200">
          Logout
        </button>
      </form>
    </div>
  </div>
  @endif

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('sidebar');
      const openBtn = document.getElementById('openSidebar');
      if (openBtn && sidebar) {
        openBtn.addEventListener('click', () => {
          sidebar.classList.toggle('sidebar-hidden');
        });
      }
    });
  </script>
</body>
</html>