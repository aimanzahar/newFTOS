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

    /* Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .animate-fade-in-up { animation: fadeInUp 0.5s ease-out both; }
    .animate-fade-in { animation: fadeIn 0.4s ease-out both; }
    .stagger-children > * { animation: fadeInUp 0.4s ease-out both; }
    .stagger-children > *:nth-child(1) { animation-delay: 0.05s; }
    .stagger-children > *:nth-child(2) { animation-delay: 0.10s; }
    .stagger-children > *:nth-child(3) { animation-delay: 0.15s; }
    .stagger-children > *:nth-child(4) { animation-delay: 0.20s; }
    .stagger-children > *:nth-child(5) { animation-delay: 0.25s; }
    .stagger-children > *:nth-child(6) { animation-delay: 0.30s; }
  </style>
</head>
<body class="font-sans antialiased text-slate-900 bg-gray-50 h-full overflow-hidden">
  <div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    @include('layouts.ftadmin.ftadmin-left-navbar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">

      <!-- Top Header -->
      <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 flex-shrink-0 relative z-10">
        <div class="flex items-center">
          <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 mr-3">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <div class="hidden md:flex items-center text-gray-400 space-x-2">
            <i class="fas fa-truck text-xs"></i>
            <span class="text-gray-300">/</span>
            <span class="text-sm font-semibold text-gray-700">Truck Owner Panel</span>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-right hidden sm:block">
            <p class="text-xs font-bold text-gray-800 leading-none">{{ Auth::user()->full_name }}</p>
            <span class="text-[10px] font-bold uppercase text-blue-600">FT Admin</span>
          </div>
          <div class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center text-white text-sm font-bold shadow-sm">
            {{ substr(Auth::user()->full_name, 0, 1) }}
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-hidden bg-gray-50 relative">
        {{ $slot }}
      </main>
    </div>
  </div>

  @if(Auth::check() && in_array(Auth::user()->status, ['pending', 'rejected', 'deactivated', 'fired'], true))
  @php
    $accountStatus = Auth::user()->status;
    $isPending = $accountStatus === 'pending';
    $isRejected = $accountStatus === 'rejected';
    $isDeactivated = $accountStatus === 'deactivated';
    $isFired = $accountStatus === 'fired';
    $isSystemAdminLock = (bool) (Auth::user()->status_locked_by_system_admin ?? false);
    $logoutButtonClass = $isRejected || $isFired
      ? 'bg-red-500 hover:bg-red-600'
      : ($isDeactivated
          ? 'bg-orange-500 hover:bg-orange-600'
          : 'bg-blue-500 hover:bg-blue-600');
  @endphp
  <!-- Registration Status Overlay -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md mx-4">
      @if($isRejected || $isFired)
        <div class="mb-4">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-circle text-3xl text-red-600"></i>
          </div>
        </div>
        <h2 class="text-2xl font-bold mb-4 text-gray-800">{{ $isFired ? 'Account Fired' : 'Application Rejected' }}</h2>
        <p class="mb-6 text-gray-600">
          @if($isFired)
            {{ $isSystemAdminLock ? 'Your account has been fired by system admin.' : 'Your account has been fired.' }}
          @else
            We're sorry, but your food truck registration application does not meet our requirements at this time. Please contact our support team for more information.
          @endif
        </p>
      @elseif($isDeactivated)
        <div class="mb-4">
          <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-slash text-3xl text-orange-500"></i>
          </div>
        </div>
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Account Deactivated</h2>
        <p class="mb-6 text-gray-600">
          {{ $isSystemAdminLock ? 'Your account has been deactivated by system admin.' : 'Your account is currently deactivated.' }}
        </p>
      @else
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Registration Pending</h2>
        <p class="mb-6 text-gray-600">Your food truck profile is currently under review. You will gain full access once approved.</p>
      @endif
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="{{ $logoutButtonClass }} text-white px-6 py-2 rounded-lg font-semibold transition duration-200">
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
      const closeBtn = document.getElementById('closeSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');

      const openSidebar = () => {
        sidebar.classList.remove('sidebar-hidden');
        if (backdrop) backdrop.classList.remove('hidden');
      };
      const closeSidebar = () => {
        sidebar.classList.add('sidebar-hidden');
        if (backdrop) backdrop.classList.add('hidden');
      };

      if (openBtn) openBtn.addEventListener('click', openSidebar);
      if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
      if (backdrop) backdrop.addEventListener('click', closeSidebar);
    });
  </script>
</body>
</html>