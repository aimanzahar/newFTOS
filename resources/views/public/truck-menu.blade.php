@extends('layouts.public')

@section('content')

<script>
function guestTruckMenuPage() {
    const allItems  = @json($menuItems);
    const truckId   = {{ $truck->id }};
    const truckName = @json($truck->foodtruck_name);

    return {
        allItems,
        truckId,
        truckName,
        activeCategory: 'all',
        isGuest: true,

        /* ── Customise Modal ── */
        showModal:     false,
        selectedItem:  null,
        modalQty:      1,
        selectedChoices: {},

        /* ─────────────── computed ─────────────── */
        get filteredItems() {
            if (this.activeCategory === 'all') return this.allItems;
            return this.allItems.filter(i => i.category === this.activeCategory);
        },

        get categories() {
            return [...new Set(this.allItems.map(i => i.category))];
        },

        get modalChoiceExtra() {
            if (!this.selectedItem) return 0;
            let extra = 0;
            for (const group of this.selectedItem.option_groups) {
                const sel = this.selectedChoices[group.id];
                if (!sel) continue;
                if (group.selection_type === 'single') {
                    const c = group.choices.find(c => c.id == sel);
                    if (c) { const p = parseFloat(c.price); extra += isNaN(p) ? 0 : p; }
                } else {
                    for (const cid of (sel || [])) {
                        const c = group.choices.find(c => c.id == cid);
                        if (c) { const p = parseFloat(c.price); extra += isNaN(p) ? 0 : p; }
                    }
                }
            }
            return extra;
        },

        get modalItemTotal() {
            if (!this.selectedItem) return 0;
            const basePrice = parseFloat(this.selectedItem.base_price);
            return ((isNaN(basePrice) ? 0 : basePrice) + this.modalChoiceExtra) * this.modalQty;
        },

        /* ─────────────── methods ─────────────── */
        openCustomize(item) {
            this.selectedItem    = item;
            this.modalQty        = 1;
            this.selectedChoices = {};
            this.showModal       = true;
        },

        setChoice(group, choiceId) {
            if (group.selection_type === 'single') {
                this.selectedChoices[group.id] = choiceId;
            } else {
                if (!this.selectedChoices[group.id]) this.selectedChoices[group.id] = [];
                const idx = this.selectedChoices[group.id].indexOf(choiceId);
                if (idx === -1) this.selectedChoices[group.id].push(choiceId);
                else            this.selectedChoices[group.id].splice(idx, 1);
            }
        },

        isChoiceSelected(group, choiceId) {
            if (group.selection_type === 'single') return this.selectedChoices[group.id] == choiceId;
            return (this.selectedChoices[group.id] || []).includes(choiceId);
        },

        increaseModalQty() { this.modalQty++; },
        decreaseModalQty() { if (this.modalQty > 1) this.modalQty--; },

        categoryLabel(cat) {
            const map = { food: 'Foods', drinks: 'Drinks', desserts: 'Desserts' };
            return map[cat] ?? (cat.charAt(0).toUpperCase() + cat.slice(1));
        },

        categoryColor(cat) {
            const map = {
                food:     'bg-amber-100 text-amber-700',
                drinks:   'bg-blue-100 text-blue-700',
                desserts: 'bg-pink-100 text-pink-700',
            };
            return map[cat] ?? 'bg-gray-100 text-gray-600';
        },
    };
}
</script>

<div x-data="guestTruckMenuPage()">

    <div class="p-6 space-y-5 max-w-6xl mx-auto">

        <!-- Back link -->
        <a href="/" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors animate-fade-in-up">
            <i class="fas fa-arrow-left"></i> Back to Trucks
        </a>

        <!-- Truck Header -->
        <div class="bg-[#0f172a] rounded-2xl p-6 flex items-center gap-5 relative overflow-hidden animate-fade-in-up">
            <i class="fas fa-truck text-white/10 text-8xl absolute -right-4 -bottom-3"></i>
            <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-utensils text-2xl text-amber-400"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-xl font-black text-white">{{ $truck->foodtruck_name }}</h1>
                    <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wide text-emerald-400 bg-emerald-400/20 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Online
                    </span>
                </div>
                @if($truck->foodtruck_desc)
                    <p class="text-sm text-slate-400">{{ $truck->foodtruck_desc }}</p>
                @endif
            </div>
        </div>

        <!-- Category Filter Tabs -->
        <div class="flex flex-wrap gap-2">
            <button @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-slate-800 text-white' : 'bg-white text-gray-500 hover:bg-gray-100 border border-gray-200'"
                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wide transition-all">
                All
            </button>
            <template x-for="cat in categories" :key="cat">
                <button @click="activeCategory = cat"
                        :class="activeCategory === cat
                            ? (cat === 'food' ? 'bg-amber-500 text-white' : cat === 'drinks' ? 'bg-blue-500 text-white' : 'bg-pink-500 text-white')
                            : 'bg-white text-gray-500 hover:bg-gray-100 border border-gray-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wide transition-all"
                        x-text="categoryLabel(cat)">
                </button>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="filteredItems.length === 0"
             class="bg-white rounded-2xl border border-gray-100 p-14 flex flex-col items-center justify-center text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                <i class="fas fa-utensils text-xl text-gray-300"></i>
            </div>
            <p class="text-sm font-bold text-gray-500">No items in this category.</p>
        </div>

        <!-- Menu Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger-children">
            <template x-for="item in filteredItems" :key="item.id">
                <div @click="openCustomize(item)"
                     class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-200 overflow-hidden flex flex-col cursor-pointer group">

                    <div class="h-36 bg-gray-50 relative overflow-hidden flex-shrink-0">
                        <template x-if="item.image">
                            <img :src="'/storage/' + item.image" :alt="item.name"
                                 class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300">
                        </template>
                        <template x-if="!item.image">
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-utensils text-3xl text-gray-200 group-hover:text-amber-300 transition-colors"></i>
                            </div>
                        </template>
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wide"
                              :class="categoryColor(item.category)"
                              x-text="categoryLabel(item.category)"></span>
                        <div class="absolute inset-0 bg-amber-400/0 group-hover:bg-amber-400/5 transition-colors duration-200 flex items-center justify-center">
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 text-amber-700 font-black text-xs px-3 py-1.5 rounded-full shadow-sm">
                                <i class="fas fa-eye mr-1"></i>View Details
                            </span>
                        </div>
                    </div>

                    <div class="p-4 flex flex-col flex-1">
                        <h3 class="font-black text-gray-900 text-sm leading-tight mb-1" x-text="item.name"></h3>
                        <p class="text-xs text-gray-400 leading-relaxed flex-1 line-clamp-2" x-text="item.description || ''"></p>

                        <template x-if="item.option_groups && item.option_groups.length > 0">
                            <p class="text-[10px] text-blue-500 font-bold mt-2">
                                <i class="fas fa-sliders mr-1"></i>
                                <span x-text="item.option_groups.length + ' option group' + (item.option_groups.length > 1 ? 's' : '')"></span>
                            </p>
                        </template>

                        <div class="flex items-center justify-between mt-3">
                            <template x-if="item.base_price">
                                <span class="text-base font-black text-gray-900"
                                      x-text="'RM ' + parseFloat(item.base_price).toFixed(2)"></span>
                            </template>
                            <template x-if="!item.base_price && item.option_groups?.length > 0">
                                <span class="text-base font-black text-blue-600">From Options</span>
                            </template>
                            <span class="w-8 h-8 rounded-xl bg-gray-200 group-hover:bg-amber-400 flex items-center justify-center transition-colors shadow-sm">
                                <i class="fas fa-eye text-gray-500 group-hover:text-amber-900 text-xs"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- CUSTOMISE MODAL (guest — view only)                -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div x-show="showModal"
         style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="showModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="font-black text-gray-900 text-base"
                        x-text="selectedItem ? selectedItem.name : ''"></h3>
                    <p class="text-xs text-gray-400 font-medium mt-0.5"
                       x-show="selectedItem && (selectedItem.base_price || selectedItem.option_groups?.some(g => g.choices?.some(c => c.price > 0)))"
                       x-text="selectedItem ? (selectedItem.base_price ? 'Base price: RM ' + parseFloat(selectedItem.base_price).toFixed(2) : 'Price from options') : ''"></p>
                </div>
                <button @click="showModal = false"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Two-Column Body -->
            <div class="flex flex-1 min-h-0 overflow-hidden">

                <!-- LEFT: Image -->
                <div class="w-56 flex-shrink-0 bg-gray-50 border-r border-gray-100 flex items-center justify-center overflow-hidden hidden sm:flex">
                    <template x-if="selectedItem && selectedItem.image">
                        <img :src="'/storage/' + selectedItem.image"
                             :alt="selectedItem ? selectedItem.name : ''"
                             class="w-full h-full object-contain p-5">
                    </template>
                    <template x-if="!selectedItem || !selectedItem.image">
                        <div class="flex flex-col items-center justify-center gap-3 p-6 text-center">
                            <i class="fas fa-utensils text-5xl text-gray-200"></i>
                            <span class="text-xs text-gray-300 font-medium leading-snug"
                                  x-text="selectedItem ? selectedItem.name : ''"></span>
                        </div>
                    </template>
                </div>

                <!-- RIGHT: Details -->
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    <template x-if="selectedItem && selectedItem.description">
                        <p class="text-sm text-gray-500 leading-relaxed"
                           x-text="selectedItem.description"></p>
                    </template>

                    <!-- Quantity (view only) -->
                    <div>
                        <p class="text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Quantity</p>
                        <div class="flex items-center gap-3">
                            <button @click="decreaseModalQty()"
                                    :disabled="modalQty <= 1"
                                    :class="modalQty <= 1 ? 'opacity-40 cursor-not-allowed' : ''"
                                    class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-black text-gray-700 transition-all">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <span class="text-xl font-black text-gray-900 w-8 text-center" x-text="modalQty"></span>
                            <button @click="increaseModalQty()"
                                    class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-black text-gray-700 transition-all">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Option Groups -->
                    <template x-if="selectedItem && selectedItem.option_groups.length > 0">
                        <div class="space-y-4">
                            <template x-for="group in selectedItem.option_groups" :key="group.id">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="text-xs font-black text-gray-700 uppercase tracking-widest"
                                           x-text="group.name"></p>
                                        <span class="text-[9px] font-black uppercase tracking-wide px-1.5 py-0.5 rounded-lg"
                                              :class="group.selection_type === 'single' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'"
                                              x-text="group.selection_type === 'single' ? 'Pick 1' : 'Pick any'"></span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <template x-for="choice in group.choices.filter(c => c.status !== 'unavailable')" :key="choice.id">
                                            <button @click="setChoice(group, choice.id)"
                                                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl border transition-all text-left"
                                                    :class="isChoiceSelected(group, choice.id)
                                                        ? 'border-amber-400 bg-amber-50 text-amber-900'
                                                        : 'border-gray-200 bg-white hover:border-gray-300 text-gray-700'">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                                         :class="isChoiceSelected(group, choice.id) ? 'border-amber-500 bg-amber-500' : 'border-gray-300'">
                                                        <div x-show="isChoiceSelected(group, choice.id)"
                                                             class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                                    </div>
                                                    <span class="text-sm font-semibold" x-text="choice.name"></span>
                                                </div>
                                                <span class="text-xs font-black"
                                                      :class="isChoiceSelected(group, choice.id) ? 'text-amber-700' : 'text-gray-400'"
                                                      x-text="parseFloat(choice.price) > 0 ? '+RM ' + parseFloat(choice.price).toFixed(2) : 'Free'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0">
                <template x-if="selectedItem && selectedItem.base_price && modalChoiceExtra > 0">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-2 pb-2 border-b border-gray-100">
                        <span>
                            <span>Base price:</span>
                            <span class="text-gray-700 font-medium ml-1" x-text="'RM ' + parseFloat(selectedItem.base_price).toFixed(2)"></span>
                            <span class="mx-1">+</span>
                            <span>Options:</span>
                            <span class="text-gray-700 font-medium ml-1" x-text="'RM ' + modalChoiceExtra.toFixed(2)"></span>
                        </span>
                    </div>
                </template>

                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Price</span>
                    <span class="text-xl font-black text-gray-900"
                          x-text="'RM ' + modalItemTotal.toFixed(2)"></span>
                </div>

                <a href="{{ route('login') }}"
                   class="w-full py-3.5 font-black text-sm rounded-2xl transition-all shadow-md flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-500 text-amber-900">
                    <i class="fas fa-sign-in-alt mr-1.5"></i>Login to Order
                </a>
            </div>

        </div>
    </div>

</div>

@endsection
