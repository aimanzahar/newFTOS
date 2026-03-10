<x-ftworker-layout>

@php
    $user = Auth::user();
    $owner = $user?->foodTruck?->owner;
    $restrictionOverlay = null;

    if (
        $owner
        && in_array($owner->status, ['deactivated', 'fired'], true)
        && (bool) ($owner->status_locked_by_system_admin ?? false)
    ) {
        $ownerIsFired = $owner->status === 'fired';

        $restrictionOverlay = [
            'barClass' => $ownerIsFired ? 'bg-red-500' : 'bg-orange-400',
            'iconWrapClass' => $ownerIsFired ? 'bg-red-50' : 'bg-orange-50',
            'iconClass' => $ownerIsFired ? 'fas fa-building-circle-xmark text-2xl text-red-500' : 'fas fa-building-circle-exclamation text-2xl text-orange-500',
            'buttonHoverClass' => $ownerIsFired ? 'hover:bg-red-600' : 'hover:bg-orange-500',
            'title' => $ownerIsFired ? 'Truck Access Disabled' : 'Truck Access Restricted',
            'message' => $ownerIsFired
                ? 'Your truck owner account has been disabled by system admin. Worker access for this truck is currently unavailable.'
                : 'Your truck owner account is temporarily deactivated by system admin. Worker access is currently paused.',
        ];
    } elseif ($user && in_array($user->status, ['deactivated', 'fired'], true)) {
        $isFired = $user->status === 'fired';
        $isSystemAdminLock = (bool) ($user->status_locked_by_system_admin ?? false);

        $restrictionOverlay = [
            'barClass' => $isFired ? 'bg-red-500' : 'bg-orange-400',
            'iconWrapClass' => $isFired ? 'bg-red-50' : 'bg-orange-50',
            'iconClass' => $isFired ? 'fas fa-user-times text-2xl text-red-500' : 'fas fa-user-slash text-2xl text-orange-500',
            'buttonHoverClass' => $isFired ? 'hover:bg-red-600' : 'hover:bg-orange-500',
            'title' => $isFired ? 'Account Fired' : 'Account Deactivated',
            'message' => $isSystemAdminLock
                ? ($isFired
                    ? 'Your account has been fired by system admin.'
                    : 'Your account has been deactivated by system admin.')
                : ($isFired
                    ? 'Your account has been fired by truck owner.'
                    : 'Truck owner has deactivated your account.'),
        ];
    }
@endphp

{{-- ACCOUNT RESTRICTION OVERLAY --}}
@if($restrictionOverlay)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-md">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden">
            <div class="h-1.5 w-full {{ $restrictionOverlay['barClass'] }}"></div>
            <div class="p-8 text-center">
                <div class="w-16 h-16 {{ $restrictionOverlay['iconWrapClass'] }} rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="{{ $restrictionOverlay['iconClass'] }}"></i>
                </div>
                <h2 class="text-xl font-black text-gray-900 mb-2">{{ $restrictionOverlay['title'] }}</h2>
                <p class="text-sm text-gray-500 leading-relaxed mb-6">
                    {{ $restrictionOverlay['message'] }}
                </p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 px-6 bg-slate-900 {{ $restrictionOverlay['buttonHoverClass'] }} text-white font-black rounded-2xl transition-all text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-power-off"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

{{-- APPROVAL PENDING OVERLAY --}}
@elseif($user->foodTruck && $user->foodTruck->status !== 'approved')
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden">
            <div class="h-1.5 w-full bg-yellow-400"></div>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-yellow-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clock text-2xl text-yellow-500"></i>
                </div>
                <h2 class="text-xl font-black text-gray-900 mb-2">Truck Not Active</h2>
                <p class="text-sm text-gray-500 leading-relaxed mb-6">
                    The food truck you are assigned to is currently pending approval. You will be able to access the system once the truck is activated.
                </p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 px-6 bg-slate-900 hover:bg-yellow-500 text-white font-black rounded-2xl transition-all text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-power-off"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

{{-- TRUCK OFFLINE OVERLAY --}}
@elseif($user->foodTruck && !$user->foodTruck->is_operational)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-md">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden">
            <div class="h-1.5 w-full bg-red-500"></div>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-power-off text-2xl text-red-500"></i>
                </div>
                <h2 class="text-xl font-black text-gray-900 mb-2">Truck Is Currently Offline</h2>
                <p class="text-sm text-gray-500 leading-relaxed mb-6">
                    Your food truck admin has set the truck to offline. Please wait until the admin turns it back on before you can start working.
                </p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 px-6 bg-slate-900 hover:bg-red-600 text-white font-black rounded-2xl transition-all text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-power-off"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="flex flex-col h-full">

    <!-- Top Header -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
            <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center text-gray-400 space-x-2">
                <span class="w-5 flex justify-center"><i class="fas fa-chart-line text-sm"></i></span>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-gray-700">Dashboard</span>
            </div>
        </div>

        <div class="flex items-center space-x-6">
            <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-bell"></i>
                <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            </button>
            <div class="h-6 w-px bg-gray-200"></div>
            <div class="flex items-center group cursor-pointer" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <div class="text-right mr-3 hidden lg:block">
                    <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ $user->full_name }}</p>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-orange-600 bg-orange-50 px-2 py-0.5 rounded">
                        FT Worker
                    </span>
                </div>
                <div class="relative">
                    <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md group-hover:scale-105 transition-all">
                        {{ substr($user->full_name, 0, 1) }}
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Body -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-full mx-auto space-y-5">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard</h1>
                    <p class="text-gray-500 mt-1 font-medium text-sm">Welcome back, {{ $user->full_name }}</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl flex-shrink-0">
                        <i class="fas fa-clipboard-list text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">New Orders</p>
                        <p class="text-3xl font-black text-gray-900">0</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl flex-shrink-0">
                        <i class="fas fa-flag-checkered text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Completed Today</p>
                        <p class="text-3xl font-black text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <!-- Shift Status -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></span>
                <div>
                    <p class="text-sm font-black text-gray-800">Shift Active</p>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">You are currently clocked in. Head to New Orders to manage incoming orders.</p>
                </div>
                <a href="{{ route('ftworker.new-orders') }}"
                   class="ml-auto flex-shrink-0 px-4 py-2 bg-slate-900 hover:bg-blue-600 text-white text-xs font-black rounded-xl transition-all">
                    View Orders
                </a>
            </div>

        </div>
    </div>

</div>

</x-ftworker-layout>
