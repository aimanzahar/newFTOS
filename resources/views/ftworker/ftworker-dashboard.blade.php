<x-ftworker-layout>

@php
    $user = Auth::user();
    $owner = $user?->foodTruck?->owner;
    $pendingOrdersCount = $pendingOrdersCount ?? 0;
    $completedTodayCount = $completedTodayCount ?? 0;
    $activePunchCard = $activePunchCard ?? null;
    $latestPunchCard = $latestPunchCard ?? null;
    $isShiftActive = (bool) $activePunchCard;
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
@endif

<div class="flex flex-col h-full">

    <!-- Page Body -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-full mx-auto space-y-5">

            <div class="flex items-center justify-between animate-fade-in-up">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Dashboard</h1>
                    <p class="text-gray-500 mt-1 font-medium text-sm">Welcome back, {{ $user->full_name }}</p>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-2xl text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-100 text-red-700 px-4 py-3 rounded-2xl text-sm font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 stagger-children">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl flex-shrink-0">
                        <i class="fas fa-clipboard-list text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">New Orders</p>
                        <p class="text-3xl font-black text-gray-900">{{ $pendingOrdersCount }}</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl flex-shrink-0">
                        <i class="fas fa-flag-checkered text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Completed Today</p>
                        <p class="text-3xl font-black text-gray-900">{{ $completedTodayCount }}</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-4 {{ $isShiftActive ? 'bg-emerald-50 text-emerald-600' : 'bg-orange-50 text-orange-600' }} rounded-2xl flex-shrink-0">
                        <i class="fas fa-id-card text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Punch Card</p>
                        <p class="text-lg font-black {{ $isShiftActive ? 'text-emerald-600' : 'text-orange-500' }}">
                            {{ $isShiftActive ? 'Punched In' : 'Punched Out' }}
                        </p>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5 truncate">
                            @if($isShiftActive)
                                In: {{ $activePunchCard?->punched_in_at?->format('d M Y, h:i A') }}
                            @elseif($latestPunchCard && $latestPunchCard->punched_out_at)
                                Last Out: {{ $latestPunchCard->punched_out_at->format('d M Y, h:i A') }}
                            @else
                                No punch record yet
                            @endif
                        </p>

                        @if($isShiftActive)
                            <form method="POST" action="{{ route('ftworker.punch-card.out') }}" class="mt-2">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-[10px] font-black rounded-lg transition-all uppercase tracking-wide">
                                    Punch Out
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('ftworker.punch-card.in') }}" class="mt-2">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black rounded-lg transition-all uppercase tracking-wide">
                                    Punch In
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Shift Status -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                <span class="w-3 h-3 rounded-full flex-shrink-0 {{ $isShiftActive ? 'bg-emerald-500 animate-pulse' : 'bg-orange-400' }}"></span>
                <div>
                    <p class="text-sm font-black text-gray-800">{{ $isShiftActive ? 'Shift Active' : 'Shift Not Started' }}</p>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">
                        {{ $isShiftActive
                            ? 'You are currently punched in. Head to New Orders to manage incoming orders.'
                            : 'Please punch in using Punch Card before accepting orders.' }}
                    </p>
                </div>

                @if($isShiftActive)
                    <a href="{{ route('ftworker.new-orders') }}"
                       class="ml-auto flex-shrink-0 px-4 py-2 bg-slate-900 hover:bg-blue-600 text-white text-xs font-black rounded-xl transition-all">
                        View Orders
                    </a>
                @else
                    <form method="POST" action="{{ route('ftworker.punch-card.in') }}" class="ml-auto flex-shrink-0">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl transition-all">
                            Punch In
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </div>

</div>

</x-ftworker-layout>
