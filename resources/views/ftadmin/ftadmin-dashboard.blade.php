<x-ftadmin-layout>
<x-slot name="header_title">Dashboard</x-slot>

@php
    $user = Auth::user();
    $role = $user->role;
    $adminFoodTruckId = $user->foodtruck_id;
    $workers = $ftworkers ?? [];
@endphp

<div x-data="{ 
        showStaffModal: false, 
        showMenuModal: false,
        showCreateForm: false,
        searchQuery: '',
        workers: {{ json_encode($workers) }},
        resetForm() {
            if(this.$refs.staffForm) this.$refs.staffForm.reset();
            if(this.$refs.modalScrollBody) {
                this.$refs.modalScrollBody.scrollTop = 0;
            }
            this.searchQuery = '';
        },
        matches(worker) {
            if (!this.searchQuery) return true;
            const query = this.searchQuery.toLowerCase();
            return (
                worker.full_name.toLowerCase().includes(query) ||
                worker.email.toLowerCase().includes(query) ||
                (worker.phone_no && worker.phone_no.includes(this.searchQuery))
            );
        },
        get filteredCount() {
            return this.workers.filter(w => this.matches(w)).length;
        }
     }" 
     class="relative min-h-full flex flex-col">

    <!-- Fixed Top Header -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
            <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center text-gray-400 space-x-2">
                <i class="fas fa-home text-sm"></i>
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
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                        @switch($role)
                            @case(6) Super Admin @break
                            @case(2) FT Admin @break
                            @case(3) FT Worker @break
                            @default User
                        @endswitch
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

    <div class="relative flex-1">
        @if($user->foodTruck && $user->foodTruck->status !== 'approved')
            <div class="absolute inset-0 z-40 flex items-center justify-center bg-gray-50/60 backdrop-blur-md">
                <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md text-center border border-gray-100 relative overflow-hidden mx-4">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-yellow-400"></div>
                    <div class="mb-4 flex justify-center">
                        <div class="p-4 bg-yellow-50 rounded-full">
                            <i class="fas fa-hourglass-half text-3xl text-yellow-600 animate-pulse"></i>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Registration Pending</h2>
                    <p class="text-gray-600 mb-6 text-sm leading-relaxed">
                        Your food truck profile is currently under review. You will gain full access once approved.
                    </p>
                </div>
            </div>
        @endif

        <div class="p-6 lg:p-10 overflow-y-auto h-full">
            <div class="w-full max-w-[1400px] mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 tracking-tight">Overview</h1>
                        <p class="text-gray-500 mt-1 font-medium">Welcome back, {{ $user->full_name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-3 w-fit bg-blue-50 text-blue-600 rounded-xl mb-4">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Revenue</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">RM 0.00</p>
                    </div>

                    <button @click="showMenuModal = true" class="text-left bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-purple-300 hover:shadow-md transition-all group outline-none">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                                <i class="fas fa-utensils text-xl"></i>
                            </div>
                            <i class="fas fa-expand-alt text-gray-300 group-hover:text-purple-500 transition-colors"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Menu Items</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">0</p>
                        <span class="text-[10px] font-bold text-blue-500 mt-2 block opacity-0 group-hover:opacity-100 transition-opacity uppercase tracking-widest">Manage Menu</span>
                    </button>

                    <button @click="showStaffModal = true; showCreateForm = false; resetForm()" class="text-left bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-blue-300 hover:shadow-md transition-all group outline-none">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <i class="fas fa-expand-alt text-gray-300 group-hover:text-blue-500"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Active Staff</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">{{ count($workers) }}</p>
                        <span class="text-[10px] font-bold text-blue-500 mt-2 block opacity-0 group-hover:opacity-100 transition-opacity uppercase tracking-widest">Manage Staff</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- STAFF MODAL -->
    <div x-show="showStaffModal" 
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @keydown.escape.window="showStaffModal = false; resetForm()">
        
        <div @click.away="showStaffModal = false; resetForm()" 
             class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[85vh] max-h-[750px] border border-white/20">
            
            <!-- Modal Header (Fixed) -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-100">
                        <i class="fas" :class="showCreateForm ? 'fa-user-plus' : 'fa-users-cog'"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight" x-text="showCreateForm ? 'Register New Staff' : 'Staff Directory'"></h2>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5" x-text="showCreateForm ? 'Fill in the details below' : 'Manage your team members'"></p>
                    </div>
                </div>
                <button @click="showStaffModal = false; resetForm()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body (Dynamic Layout) -->
            <div class="flex-1 overflow-hidden flex flex-col">
                
                <!-- View: Staff Directory (Fixed Search, Scrollable Table) -->
                <div x-show="!showCreateForm" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100"
                     class="flex-1 flex flex-col overflow-hidden">
                    
                    <!-- Search Header (Fixed inside directory view) -->
                    <div class="px-8 py-6 flex-shrink-0 flex items-center justify-between">
                        <div class="relative w-72">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" x-model="searchQuery" placeholder="Search name, email, or phone..." class="w-full pl-11 pr-4 py-2.5 bg-gray-100 border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium outline-none">
                        </div>
                        <button @click="showCreateForm = true; resetForm()" class="inline-flex items-center px-5 py-2.5 bg-slate-900 hover:bg-blue-600 text-white text-sm font-bold rounded-xl shadow-md transition-all active:scale-95 group">
                            <i class="fas fa-plus mr-2.5 text-[10px] group-hover:rotate-90 transition-transform"></i>
                            Add New Staff
                        </button>
                    </div>

                    <!-- Scrollable Table Container -->
                    <div class="flex-1 overflow-y-auto px-8 pb-8">
                        <div class="overflow-hidden border border-gray-100 rounded-2xl">
                            <table class="w-full">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                        <th class="py-4 text-left px-6">Staff Name</th>
                                        <th class="py-4 text-left px-6">Contact Details</th>
                                        <th class="py-4 text-right px-6">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <template x-for="worker in workers" :key="worker.id">
                                        <tr x-show="matches(worker)" class="hover:bg-blue-50/30 transition-colors group">
                                            <td class="py-5 px-6">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 rounded-xl bg-slate-800 text-white flex items-center justify-center font-black text-sm mr-4 shadow-sm group-hover:scale-110 transition-transform" x-text="worker.full_name.charAt(0)"></div>
                                                    <span class="text-sm font-bold text-gray-800" x-text="worker.full_name"></span>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-gray-600" x-text="worker.email"></span>
                                                    <span class="text-[11px] text-gray-400 mt-0.5 font-bold" x-text="worker.phone_no || 'No phone'"></span>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6 text-right">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                    Active
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="searchQuery !== '' && filteredCount === 0">
                                        <td colspan="3" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-user-slash text-2xl text-red-300"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">No User Found</h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">No results match your search query</p>
                                                <button @click="searchQuery = ''" class="mt-4 text-[11px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest">Clear Search</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- View: Register Form (Full area scrollable) -->
                <div x-show="showCreateForm" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     class="flex-1 overflow-y-auto px-8 py-10">
                    <div class="max-w-2xl mx-auto">
                        <form x-ref="staffForm" action="{{ route('ftadmin.register.staff') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            @csrf
                            <input type="hidden" name="role" value="3">
                            <input type="hidden" name="foodtruck_id" value="{{ $adminFoodTruckId }}">

                            <div class="space-y-2 md:col-span-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Full Name <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="text" name="full_name" required placeholder="Ex: Ahmad Junaidi"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Email Address <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="email" name="email" required placeholder="staff@vendor.com"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Phone Number</label>
                                <div class="relative group">
                                    <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="text" name="phone_no" placeholder="012-3456789"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Password <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="password" name="password" required 
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                                           title="Must be at least 8 characters long, include 1 uppercase letter, 1 number, and 1 special symbol."
                                           placeholder="••••••••"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Confirm Password <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <i class="fas fa-shield-check absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors"></i>
                                    <input type="password" name="password_confirmation" required 
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                                           placeholder="••••••••"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>

                            <div class="md:col-span-2 pt-6 flex items-center space-x-4">
                                <button type="button" @click="showCreateForm = false; resetForm()"
                                        class="flex-1 px-8 py-4 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all active:scale-[0.98]">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Back
                                </button>
                                <button type="submit"
                                        class="flex-[2.5] px-8 py-4 bg-slate-900 text-white rounded-2xl text-sm font-black hover:bg-blue-600 shadow-xl shadow-slate-200 hover:shadow-blue-200 transition-all active:scale-[0.98]">
                                    Complete Registration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Fixed) -->
            <div class="px-8 py-6 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Authorized Access Only</p>
                <button @click="showStaffModal = false; resetForm()" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition-colors">
                    Close Management Tools
                </button>
            </div>
        </div>
    </div>

    <!-- MENU MODAL PLACEHOLDER -->
    <div x-show="showMenuModal" 
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         x-transition>
         <div @click.away="showMenuModal = false" class="bg-white p-12 rounded-3xl shadow-2xl max-w-lg text-center">
             <div class="p-4 bg-purple-50 text-purple-600 rounded-2xl w-fit mx-auto mb-4">
                <i class="fas fa-utensils text-3xl"></i>
             </div>
             <h2 class="text-2xl font-black text-gray-800">Menu Management</h2>
             <p class="text-gray-500 mt-2">Menu customization features are currently being prepared.</p>
             <button @click="showMenuModal = false" class="mt-8 px-6 py-3 bg-slate-900 text-white rounded-xl font-bold">Close</button>
         </div>
    </div>

</div>


</x-ftadmin-layout>