<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'FTOS Worker') }}</title>

  <!-- Fonts & Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- Scripts -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
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
<body class="font-sans antialiased text-slate-900 bg-gray-50">
  @php
    $layoutUser = Auth::user();
    $layoutTruck = $layoutUser?->foodTruck;
    $layoutOwner = $layoutTruck?->owner;
    $layoutWorkerBlocked = $layoutUser && in_array($layoutUser->status, ['deactivated', 'fired'], true);
    $layoutOwnerBlockedBySystemAdmin = $layoutOwner
      && in_array($layoutOwner->status, ['deactivated', 'fired'], true)
      && (bool) ($layoutOwner->status_locked_by_system_admin ?? false);

    $layoutCanTrackOperational = $layoutUser
      && (int) $layoutUser->role === 3
      && !$layoutWorkerBlocked
      && !$layoutOwnerBlockedBySystemAdmin
      && $layoutTruck
      && $layoutTruck->status === 'approved';

    $layoutInitialTruckOffline = $layoutCanTrackOperational
      ? !(bool) $layoutTruck->is_operational
      : false;
  @endphp

  @if($layoutCanTrackOperational)
    <div id="truckOperationalOverlay" class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/50 backdrop-blur-md {{ $layoutInitialTruckOffline ? '' : 'hidden' }}">
      <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden">
          <div class="h-1.5 w-full bg-red-500"></div>
          <div class="p-8 text-center">
              <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                  <i class="fas fa-power-off text-2xl text-red-500"></i>
              </div>
              <h2 class="text-xl font-black text-gray-900 mb-2">Truck Is Currently Offline</h2>
              <p class="text-sm text-gray-500 leading-relaxed">
                  Your food truck admin has set the truck to offline. Please wait until the admin turns it back on before you can continue working.
              </p>
          </div>
      </div>
    </div>
  @endif

  <div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    @include('layouts.ftworker.ftworker-left-navbar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto bg-gray-50 relative">
        {{ $slot ?? '' }}
        @yield('content')
      </main>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('sidebar');
      const openBtn = document.getElementById('openSidebar');
      if (openBtn && sidebar) {
        openBtn.addEventListener('click', () => {
          sidebar.classList.toggle('sidebar-hidden');
        });
      }

      const shouldTrackOperational = @json($layoutCanTrackOperational);
      const statusEndpoint = @json(route('ftworker.truck-operational-status'));
      const operationalOverlay = document.getElementById('truckOperationalOverlay');

      const setOperationalOverlay = (isOffline) => {
        if (!operationalOverlay) return;
        operationalOverlay.classList.toggle('hidden', !isOffline);
      };

      if (!shouldTrackOperational || !operationalOverlay) {
        return;
      }

      let fetchingOperationalStatus = false;

      const loadOperationalStatus = async () => {
        if (fetchingOperationalStatus) return;
        fetchingOperationalStatus = true;

        try {
          const response = await fetch(statusEndpoint, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
          });

          const data = await response.json().catch(() => ({}));
          if (!response.ok || !data.success) return;

          const isApproved = String(data.truck_status || '') === 'approved';
          const isOperational = Boolean(data.is_operational);

          setOperationalOverlay(isApproved && !isOperational);
        } catch (error) {
          console.error(error);
        } finally {
          fetchingOperationalStatus = false;
        }
      };

      loadOperationalStatus();
      const operationalPollingTimer = setInterval(loadOperationalStatus, 1000);

      window.addEventListener('beforeunload', () => {
        clearInterval(operationalPollingTimer);
      });
    });
  </script>
</body>
</html>
