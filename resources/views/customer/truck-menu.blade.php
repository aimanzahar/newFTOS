@extends('layouts.customer.customer-layout')

@section('header_icon_class', 'fas fa-store')
@section('header_title', $truck->foodtruck_name)
@section('back_route', route('customer.browse'))

@section('content')

<script>
function truckMenuPage() {
    const allItems  = @json($menuItems);
    const truckId   = {{ $truck->id }};
    const truckName = @json($truck->foodtruck_name);

    return {
        allItems,
        truckId,
        truckName,
        activeCategory: 'all',

        /* ── Customise Modal ── */
        showModal:     false,
        selectedItem:  null,
        modalQty:      1,
        selectedChoices: {},
        editingCartId: null,   // non-null = edit mode

        /* ── Truck Conflict Dialog ── */
        showConflict: false,
        pendingItem:  null,

        /* ─────────────── lifecycle ─────────────── */
        init() {
            // Listen for Edit requests from the cart sidebar
            window.addEventListener('cart:edit', (e) => {
                const cartItem = this.$store.cart.items.find(i => i.cartId === e.detail.cartId);
                if (cartItem) this.openEdit(cartItem);
            });
        },

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
                    if (c) extra += parseFloat(c.price);
                } else {
                    for (const cid of (sel || [])) {
                        const c = group.choices.find(c => c.id == cid);
                        if (c) extra += parseFloat(c.price);
                    }
                }
            }
            return extra;
        },

        get modalItemTotal() {
            if (!this.selectedItem) return 0;
            return (parseFloat(this.selectedItem.base_price) + this.modalChoiceExtra) * this.modalQty;
        },

        get missingRequiredChoices() {
            if (!this.selectedItem) return true;
            for (const group of this.selectedItem.option_groups) {
                if (group.selection_type === 'single') {
                    if (!this.selectedChoices[group.id]) {
                        return true; // Found a required group without a selection
                    }
                }
            }
            return false; // All required choices are selected
        },

        /* ─────────────── methods ─────────────── */

        /* Open modal for a NEW item — checks truck conflict first */
        openCustomize(item) {
            const store = this.$store.cart;
            if (store.truckId !== null && store.truckId !== this.truckId && store.items.length > 0) {
                this.pendingItem  = item;
                this.showConflict = true;
                return;
            }
            this._openModal(item);
        },

        confirmConflict() {
            this.$store.cart.clearCart();
            this.showConflict = false;
            this._openModal(this.pendingItem);
            this.pendingItem = null;
        },

        cancelConflict() {
            this.showConflict = false;
            this.pendingItem  = null;
        },

        _openModal(item) {
            this.selectedItem    = item;
            this.modalQty        = 1;
            this.selectedChoices = {};
            this.editingCartId   = null;
            this.showModal       = true;
            this.$nextTick(() => { if (this.$refs.modalBody) this.$refs.modalBody.scrollTop = 0; });
        },

        /* Open modal to EDIT an existing cart item */
        openEdit(cartItem) {
            const menuItem = this.allItems.find(i => i.id === cartItem.menu_id);
            if (!menuItem) return;
            this.selectedItem  = menuItem;
            this.modalQty      = cartItem.quantity;
            this.editingCartId = cartItem.cartId;

            this.selectedChoices = {};
            for (const saved of cartItem.selected_choices) {
                for (const group of menuItem.option_groups) {
                    const found = group.choices.find(c => c.id === saved.choice_id);
                    if (!found) continue;
                    if (group.selection_type === 'single') {
                        this.selectedChoices[group.id] = saved.choice_id;
                    } else {
                        if (!this.selectedChoices[group.id]) this.selectedChoices[group.id] = [];
                        this.selectedChoices[group.id].push(saved.choice_id);
                    }
                    break;
                }
            }
            this.showModal = true;
            this.$nextTick(() => { if (this.$refs.modalBody) this.$refs.modalBody.scrollTop = 0; });
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

        collectChoices() {
            if (!this.selectedItem) return [];
            const result = [];
            for (const group of this.selectedItem.option_groups) {
                const sel = this.selectedChoices[group.id];
                if (!sel) continue;
                const ids = group.selection_type === 'single' ? [sel] : (sel || []);
                for (const cid of ids) {
                    const c = group.choices.find(c => c.id == cid);
                    if (c) result.push({ choice_id: c.id, group_name: group.name, choice_name: c.name, price: parseFloat(c.price) });
                }
            }
            return result;
        },

        /* Add new item OR update existing — saves to $store.cart */
        addToCart() {
            if (!this.selectedItem || this.modalQty < 1) return;
            const entry = {
                cartId:           this.editingCartId || (Date.now() + Math.random()),
                menu_id:          this.selectedItem.id,
                name:             this.selectedItem.name,
                image:            this.selectedItem.image,
                base_price:       parseFloat(this.selectedItem.base_price),
                quantity:         this.modalQty,
                selected_choices: this.collectChoices(),
                item_total:       this.modalItemTotal,
            };

            if (this.editingCartId) {
                this.$store.cart.updateItem(this.editingCartId, entry);
            } else {
                this.$store.cart.addItem(this.truckId, this.truckName, entry);
            }

            this.editingCartId = null;
            this.showModal     = false;
        },

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

<div x-data="truckMenuPage()">

    <!-- ═══════════════════════════════════ -->
    <!-- Menu Items                          -->
    <!-- ═══════════════════════════════════ -->
    <div class="p-6 space-y-5">

        <!-- Truck Header -->
        <div class="bg-[#0f172a] rounded-2xl p-6 flex items-center gap-5 relative overflow-hidden">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="item in filteredItems" :key="item.id">
                <div @click="openCustomize(item)"
                     class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-300 transition-all duration-200 overflow-hidden flex flex-col cursor-pointer group">

                    <!-- Item Image -->
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
                        <!-- Category badge -->
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wide"
                              :class="categoryColor(item.category)"
                              x-text="categoryLabel(item.category)"></span>
                        <!-- Tap hint -->
                        <div class="absolute inset-0 bg-amber-400/0 group-hover:bg-amber-400/5 transition-colors duration-200 flex items-center justify-center">
                            <span class="opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 text-amber-700 font-black text-xs px-3 py-1.5 rounded-full shadow-sm">
                                <i class="fas fa-plus mr-1"></i>Customise & Add
                            </span>
                        </div>
                    </div>

                    <!-- Item Info -->
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
                            <span class="text-base font-black text-gray-900"
                                  x-text="'RM ' + parseFloat(item.base_price).toFixed(2)"></span>
                            <span class="w-8 h-8 rounded-xl bg-amber-400 group-hover:bg-amber-500 flex items-center justify-center transition-colors shadow-sm">
                                <i class="fas fa-plus text-amber-900 text-xs"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- TRUCK CONFLICT DIALOG                              -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div x-show="showConflict"
         style="display:none"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-6 text-center"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-triangle-exclamation text-2xl text-amber-500"></i>
            </div>
            <h3 class="font-black text-gray-900 text-base mb-2">Different Food Truck</h3>
            <p class="text-sm text-gray-500 mb-5 leading-relaxed">
                Your cart has items from <span class="font-black text-gray-700" x-text="$store.cart.truckName"></span>.
                Adding from this truck will clear your current cart.
            </p>
            <div class="flex gap-3">
                <button @click="cancelConflict()"
                        class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-black text-gray-600 hover:bg-gray-50 transition-all">
                    Keep Cart
                </button>
                <button @click="confirmConflict()"
                        class="flex-1 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-500 text-amber-900 text-sm font-black transition-all shadow-sm">
                    Clear & Add
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- CUSTOMISE MODAL  (two-column)                      -->
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

        <div @click.away="showModal = false; editingCartId = null"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-black text-gray-900 text-base"
                            x-text="selectedItem ? selectedItem.name : ''"></h3>
                        <span x-show="editingCartId"
                              class="text-[9px] font-black uppercase tracking-wide bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">
                            Editing
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium mt-0.5"
                       x-text="selectedItem ? 'Base price: RM ' + parseFloat(selectedItem.base_price).toFixed(2) : ''"></p>
                </div>
                <button @click="showModal = false; editingCartId = null"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Two-Column Body -->
            <div class="flex flex-1 min-h-0 overflow-hidden">

                <!-- LEFT: Large image (fixed, non-scrollable) -->
                <div class="w-56 flex-shrink-0 bg-gray-50 border-r border-gray-100 flex items-center justify-center overflow-hidden">
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

                <!-- RIGHT: Scrollable customisation area -->
                <div x-ref="modalBody" class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    <!-- Description -->
                    <template x-if="selectedItem && selectedItem.description">
                        <p class="text-sm text-gray-500 leading-relaxed"
                           x-text="selectedItem.description"></p>
                    </template>

                    <!-- Quantity -->
                    <div>
                        <p class="text-xs font-black text-gray-700 uppercase tracking-widest mb-2">Quantity</p>
                        <div class="flex items-center gap-3">
                            <button @click="if(modalQty > 1) modalQty--"
                                    class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-black text-gray-700 transition-all">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <span class="text-xl font-black text-gray-900 w-8 text-center"
                                  x-text="modalQty"></span>
                            <button @click="modalQty++"
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
                <!-- end right scrollable area -->

            </div>
            <!-- end two-column body -->

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Price</span>
                    <span class="text-xl font-black text-gray-900"
                          x-text="'RM ' + modalItemTotal.toFixed(2)"></span>
                </div>
                <button @click="addToCart()"
                        :disabled="missingRequiredChoices"
                        class="w-full py-3.5 font-black text-sm rounded-2xl transition-all shadow-md flex items-center justify-center gap-2"
                        :class="{
                            'bg-gray-300 text-gray-500 cursor-not-allowed': missingRequiredChoices,
                            'bg-blue-600 hover:bg-blue-700 text-white': editingCartId && !missingRequiredChoices,
                            'bg-amber-400 hover:bg-amber-500 text-amber-900': !editingCartId && !missingRequiredChoices
                        }">
                    <template x-if="!editingCartId">
                        <span><i class="fas fa-shopping-bag mr-1.5"></i>Add to Cart</span>
                    </template>
                    <template x-if="editingCartId">
                        <span><i class="fas fa-check mr-1.5"></i>Update Cart</span>
                    </template>
                </button>
                <p x-show="missingRequiredChoices" class="text-red-500 text-xs text-center mt-2">Please make a selection for all required options.</p>
            </div>

        </div>
        <!-- end modal box -->

    </div>
    <!-- end modal overlay -->

</div>{{-- end x-data="truckMenuPage()" --}}

@endsection
