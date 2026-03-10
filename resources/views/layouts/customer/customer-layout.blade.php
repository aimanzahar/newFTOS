<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'FTOS') }}</title>

  <!-- Fonts & Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- Alpine.js store + cart sidebar — must register BEFORE Alpine initialises -->
  <script>
  document.addEventListener('alpine:init', () => {

    /* ═══════════════════════════════════════════
       Global Cart Store  (localStorage-backed)
    ═══════════════════════════════════════════ */
    Alpine.store('cart', {
      truckId:   null,
      truckName: '',
      items:     [],   // { cartId, truckId, truckName, menu_id, name, image, base_price, quantity, selected_choices, item_total, checked }

      init() {
        try {
          const saved = JSON.parse(localStorage.getItem('ftos_cart') || 'null');
          if (saved) {
            const fallbackTruckId = saved.truckId ?? null;
            const fallbackTruckName = saved.truckName || 'Food Truck';

            this.items = (saved.items || []).map(i => ({
              ...i,
              truckId: i.truckId ?? fallbackTruckId,
              truckName: i.truckName || fallbackTruckName,
              checked: i.checked !== false,
            }));

            this.syncTruckMeta();
          }
        } catch (e) {}
      },

      syncTruckMeta() {
        if (this.items.length === 0) {
          this.truckId = null;
          this.truckName = '';
          return;
        }

        this.truckId = this.items[0].truckId ?? null;
        this.truckName = this.items[0].truckName || '';
      },

      save() {
        this.syncTruckMeta();

        localStorage.setItem('ftos_cart', JSON.stringify({
          truckId:   this.truckId,
          truckName: this.truckName,
          items:     this.items,
        }));
      },

      addItem(truckId, truckName, entry) {
        this.items.push({
          ...entry,
          truckId,
          truckName,
          checked: true,
        });

        this.save();
      },

      updateItem(cartId, entry) {
        const idx = this.items.findIndex(i => i.cartId === cartId);
        if (idx !== -1) {
          const truckId = this.items[idx].truckId ?? null;
          const truckName = this.items[idx].truckName || 'Food Truck';
          const checked = this.items[idx].checked;
          this.items.splice(idx, 1, { ...entry, truckId, truckName, checked });
          this.save();
        }
      },

      removeItem(cartId) {
        this.items = this.items.filter(i => i.cartId !== cartId);
        this.save();
      },

      toggleItem(cartId) {
        const item = this.items.find(i => i.cartId === cartId);
        if (item) { item.checked = !item.checked; this.save(); }
      },

      toggleAll(val) {
        this.items.forEach(i => { i.checked = val; });
        this.save();
      },

      toggleTruckItems(truckId, val) {
        this.items
          .filter(i => String(i.truckId) === String(truckId))
          .forEach(i => { i.checked = val; });
        this.save();
      },

      areAllItemsCheckedForTruck(truckId) {
        const truckItems = this.items.filter(i => String(i.truckId) === String(truckId));
        return truckItems.length > 0 && truckItems.every(i => i.checked);
      },

      clearCart() {
        this.items     = [];
        this.truckId   = null;
        this.truckName = '';
        localStorage.removeItem('ftos_cart');
      },

      get allChecked() {
        return this.items.length > 0 && this.items.every(i => i.checked);
      },

      get groupedItems() {
        const groups = new Map();

        this.items.forEach(item => {
          const key = String(item.truckId ?? '__unknown__');
          if (!groups.has(key)) {
            groups.set(key, {
              truckId: item.truckId ?? null,
              truckName: item.truckName || 'Food Truck',
              items: [],
            });
          }

          groups.get(key).items.push(item);
        });

        return Array.from(groups.values());
      },

      get checkedTruckIds() {
        return [...new Set(
          this.items
            .filter(i => i.checked)
            .map(i => i.truckId)
            .filter(id => id !== null && id !== undefined && id !== '')
        )];
      },

      get truckCount() {
        return this.groupedItems.length;
      },

      get truckSummary() {
        if (this.truckCount === 0) return '';
        if (this.truckCount === 1) return this.groupedItems[0].truckName || 'Food Truck';
        return `${this.truckCount} food trucks in cart`;
      },

      get checkedTotal() {
        return this.items.filter(i => i.checked).reduce((s, i) => s + i.item_total, 0);
      },

      get itemCount() {
        return this.items.reduce((s, i) => s + i.quantity, 0);
      },
    });

    /* ═══════════════════════════════════════════
       Cart Sidebar Component
    ═══════════════════════════════════════════ */
    Alpine.data('cartSidebar', () => ({
      orderType:        'self_pickup',
      tableNumber:      '',
      tableError:       false,
      placing:          false,
      orderSuccess:     false,
      lastOrderId:      null,
      orderError:       null,
      showReceiptModal: false,
      lastOrder:        null,

      /* Payment modal */
      showPaymentModal: false,
      paymentStep:      'choose',   // 'choose' | 'online_banking' | 'cash'
      selectedPayment:  null,

      get canCheckout() {
        return this.$store.cart.items.some(i => i.checked);
      },

      getCheckedTruckIds(checkedItems = null) {
        const items = checkedItems || this.$store.cart.items.filter(i => i.checked);

        return [...new Set(
          items
            .map(i => i.truckId)
            .filter(id => id !== null && id !== undefined && id !== '')
        )];
      },

      formatCurrency(amount) {
        return 'RM ' + parseFloat(amount || 0).toFixed(2);
      },

      formatDateTime(value) {
        if (!value) return '-';
        const dt = new Date(value);
        if (Number.isNaN(dt.getTime())) return value;
        return dt.toLocaleString();
      },

      paymentMethodLabel(method) {
        if (!method) return '-';
        return method === 'cash' ? 'Cash' : method;
      },

      statusLabel(status) {
        if (!status) return 'Pending';
        return status
          .toString()
          .replace(/_/g, ' ')
          .replace(/\b\w/g, (s) => s.toUpperCase());
      },

      statusClass(status) {
        const map = {
          pending: 'bg-amber-100 text-amber-700',
          accepted: 'bg-blue-100 text-blue-700',
          preparing: 'bg-indigo-100 text-indigo-700',
          prepared: 'bg-purple-100 text-purple-700',
          ready_for_pickup: 'bg-emerald-100 text-emerald-700',
          delivery: 'bg-cyan-100 text-cyan-700',
          rejected: 'bg-rose-100 text-rose-700',
          done: 'bg-gray-100 text-gray-700',
        };
        return map[status] || 'bg-gray-100 text-gray-700';
      },

      editItem(cartId) {
        window.dispatchEvent(new CustomEvent('cart:edit', { detail: { cartId } }));
      },

      openPaymentModal() {
        if (this.orderType === 'table' && !this.tableNumber) {
          this.tableError = true;
          return;
        }
        this.tableError      = false;
        if (!this.canCheckout) return;

        const checkedTruckIds = this.getCheckedTruckIds();
        if (checkedTruckIds.length > 1) {
          this.orderError = 'Please select items from one food truck only before checkout.';
          return;
        }

        this.paymentStep     = 'choose';
        this.selectedPayment = null;
        this.orderError      = null;
        this.showPaymentModal = true;
      },

      selectPayment(type) {
        this.paymentStep    = type;   // 'online_banking' or 'cash'
        this.selectedPayment = type;
      },

      selectBank(bank) {
        this.selectedPayment  = bank;
        this.placeOrder();
        this.showPaymentModal = false;
      },

      confirmCash() {
        this.selectedPayment  = 'cash';
        this.showPaymentModal = false;
        this.placeOrder();
      },

      async placeOrder() {
        if (this.orderType === 'table' && !this.tableNumber) {
          this.tableError = true;
          return;
        }
        this.tableError = false;
        if (!this.canCheckout || this.placing) return;
        if (!this.selectedPayment) {
          this.orderError = 'Please choose a payment method.';
          return;
        }
        this.placing    = true;
        this.orderError = null;

        const store        = this.$store.cart;
        const checkedItems = store.items.filter(i => i.checked);
        const checkedTruckIds = this.getCheckedTruckIds(checkedItems);

        if (checkedTruckIds.length !== 1) {
          this.orderError = 'Please select items from one food truck only before checkout.';
          this.placing = false;
          return;
        }

        const checkoutTruckId = checkedTruckIds[0];
        const checkoutItems = checkedItems.filter(i => String(i.truckId) === String(checkoutTruckId));

        try {
          const res = await fetch('/customer/orders', {
            method:  'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept':       'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({
              foodtruck_id: checkoutTruckId,
              order_type:   this.orderType,
              table_number: this.orderType === 'table' ? parseInt(this.tableNumber) : null,
              payment_method: this.selectedPayment,
              items: checkoutItems.map(i => ({
                menu_id:          i.menu_id,
                quantity:         i.quantity,
                selected_choices: i.selected_choices.map(c => c.choice_id),
              })),
            }),
          });
          const data = await res.json();
          if (data.success) {
            this.lastOrder   = data.order || null;
            this.lastOrderId = data.order ? data.order.id : null;
            this.showReceiptModal = false;
            checkoutItems.forEach(i => store.removeItem(i.cartId));
            this.orderType   = 'self_pickup';
            this.tableNumber = '';
            this.selectedPayment = null;
            this.paymentStep = 'choose';
            this.orderSuccess = true;
          } else {
            this.orderError = data.message || 'Failed to place order.';
          }
        } catch (e) {
          this.orderError = 'Network error. Please try again.';
        }
        this.placing = false;
      },
    }));

  });
  </script>

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
<body class="font-sans antialiased text-slate-900 bg-gray-50">
  <div class="flex h-screen overflow-hidden">

    <!-- Left Nav Sidebar -->
    @include('layouts.customer.customer-left-navbar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden relative">

      <!-- Top Header -->
      <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
          <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <div class="hidden md:flex items-center gap-3 text-gray-400">
            <div class="flex items-center space-x-2">
              <span class="w-5 flex justify-center"><i class="@yield('header_icon_class', 'fas fa-home') text-sm"></i></span>
              <span class="text-gray-300">/</span>
              <span class="text-sm font-bold text-gray-700">@yield('header_title', 'Dashboard')</span>
            </div>
            @hasSection('back_route')
              <a href="@yield('back_route')"
                 class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 text-xs font-bold transition-all">
                <i class="fas fa-arrow-left text-xs"></i>
                Back
              </a>
            @endif
          </div>
        </div>

        <div class="flex items-center space-x-6">
          <button class="relative p-2 text-gray-400 hover:text-blue-600 transition">
            <i class="fas fa-bell"></i>
            <span class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
          </button>
          <div class="h-6 w-px bg-gray-200"></div>
          <div class="flex items-center group cursor-pointer" onclick="event.preventDefault(); document.getElementById('customer-logout-form').submit();">
            <div class="text-right mr-3 hidden lg:block">
              <p class="text-sm font-bold text-gray-800 leading-none mb-1">{{ Auth::user()->full_name }}</p>
              <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                Customer
              </span>
            </div>
            <div class="relative">
              <div class="w-10 h-10 rounded-lg bg-slate-800 flex items-center justify-center text-white font-bold shadow-md group-hover:scale-105 transition-all">
                {{ substr(Auth::user()->full_name, 0, 1) }}
              </div>
              <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page + Cart row -->
      <div class="flex flex-1 min-h-0 overflow-hidden">

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto bg-gray-50 relative">
          {{ $slot ?? '' }}
          @yield('content')
        </main>

        <!-- ═══════════════════════════════════════════
             Persistent Cart Sidebar
             Visible on ALL customer pages when cart has items
        ═══════════════════════════════════════════ -->
        <div x-data="cartSidebar()"
             x-show="{{ request()->routeIs('profile.edit') ? 'false' : (request()->routeIs('customer.dashboard', 'customer.browse', 'customer.truck-menu') ? 'true' : '$store.cart.items.length > 0') }}"
             style="display:none"
             class="w-80 flex-shrink-0 border-l border-gray-200 bg-white flex flex-col h-full overflow-hidden">

          <!-- Cart Header -->
          <div class="px-5 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-center justify-between">
              <h2 class="font-black text-gray-900 text-base">Your Cart</h2>
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span class="text-xs font-black text-gray-500"
                      x-text="$store.cart.itemCount + ' item' + ($store.cart.itemCount !== 1 ? 's' : '')"></span>
              </div>
            </div>
            <p class="text-xs text-gray-400 font-medium mt-0.5" x-text="$store.cart.truckSummary"></p>
          </div>

          <!-- Order Success State -->
          <div x-show="orderSuccess" x-transition class="flex-1 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mb-4">
              <i class="fas fa-check-circle text-3xl text-emerald-500"></i>
            </div>
            <h3 class="font-black text-gray-900 text-base mb-1">Order Placed!</h3>
            <p class="text-xs text-gray-400 mb-1">Your order has been sent to the kitchen.</p>
            <p class="text-[10px] font-black text-gray-500 bg-gray-100 px-3 py-1 rounded-xl mb-5"
               x-text="'Order #' + String(lastOrderId).padStart(4, '0')"></p>
            <div class="grid grid-cols-2 gap-2 w-full mb-2">
              <button @click="showReceiptModal = true"
                      class="py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-700 font-black text-xs rounded-xl transition-all">
                <i class="fas fa-receipt mr-1.5"></i>Show Receipt
              </button>
              <button @click="orderSuccess = false; showReceiptModal = false"
                      class="py-2.5 bg-slate-900 hover:bg-amber-500 text-white font-black text-xs rounded-xl transition-all">
                Order More
              </button>
            </div>
            <button @click="orderSuccess = false"
                    class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 font-black text-xs rounded-xl transition-all">
              Close This Message
            </button>
          </div>

          <!-- Cart Content -->
          <div x-show="!orderSuccess" class="flex-1 flex flex-col min-h-0 overflow-hidden">

            <!-- Empty state -->
            <div x-show="$store.cart.items.length === 0"
                 class="flex-1 flex flex-col items-center justify-center text-center px-4">
              <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                <i class="fas fa-shopping-cart text-2xl text-gray-300"></i>
              </div>
              <p class="text-sm font-black text-gray-400">Your cart is empty</p>
              <p class="text-[11px] text-gray-300 mt-1">Add some items to get started!</p>
            </div>

            <!-- Scrollable items list -->
            <div x-show="$store.cart.items.length > 0" class="flex-1 overflow-y-auto px-4 py-3 space-y-2">

              <!-- Select All -->
              <label class="flex items-center gap-2 px-1 py-1.5 cursor-pointer select-none">
                <input type="checkbox"
                       :checked="$store.cart.allChecked"
                       @change="$store.cart.toggleAll($event.target.checked)"
                       class="w-4 h-4 rounded accent-amber-400 cursor-pointer">
                <span class="text-xs font-black text-gray-600 uppercase tracking-wide">Select All</span>
              </label>

              <!-- Cart Items Grouped by Truck -->
              <template x-for="group in $store.cart.groupedItems" :key="'truck-' + String(group.truckId ?? 'unknown')">
                <div class="space-y-2">
                  <div class="flex items-center justify-between px-1 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                      <input type="checkbox"
                             :checked="$store.cart.areAllItemsCheckedForTruck(group.truckId)"
                             @change="$store.cart.toggleTruckItems(group.truckId, $event.target.checked)"
                             class="w-4 h-4 rounded accent-amber-400 cursor-pointer">
                      <span class="text-[11px] font-black text-gray-700 uppercase tracking-wide"
                            x-text="group.truckName || 'Food Truck'"></span>
                    </label>
                    <span class="text-[10px] text-gray-400 font-bold"
                          x-text="group.items.reduce((sum, i) => sum + (parseInt(i.quantity, 10) || 0), 0) + ' item' + (group.items.reduce((sum, i) => sum + (parseInt(i.quantity, 10) || 0), 0) !== 1 ? 's' : '')"></span>
                  </div>

                  <template x-for="item in group.items" :key="item.cartId">
                    <div class="bg-gray-50 rounded-xl p-3 border transition-colors"
                         :class="item.checked ? 'border-amber-200' : 'border-transparent hover:border-gray-200'">
                      <div class="flex items-start gap-2">
                        <input type="checkbox"
                               :checked="item.checked"
                               @change="$store.cart.toggleItem(item.cartId)"
                               class="w-4 h-4 rounded accent-amber-400 cursor-pointer mt-0.5 flex-shrink-0">

                        <div class="flex-1 min-w-0">
                          <div class="flex items-start justify-between gap-1 mb-1">
                            <p class="text-xs font-black text-gray-800 leading-tight"
                               x-text="item.quantity + '× ' + item.name"></p>
                            <span class="text-xs font-black flex-shrink-0 transition-colors"
                                  :class="item.checked ? 'text-gray-900' : 'text-gray-400'"
                                  x-text="'RM ' + item.item_total.toFixed(2)"></span>
                          </div>

                          <template x-if="item.selected_choices && item.selected_choices.length > 0">
                            <div class="mb-2 space-y-0.5 pl-2 border-l-2 border-amber-200">
                              <template x-for="(c, ci) in item.selected_choices" :key="ci">
                                <p class="text-[10px] text-gray-400 leading-tight"
                                   x-text="c.choice_name + (parseFloat(c.price) > 0 ? ' (+RM ' + parseFloat(c.price).toFixed(2) + ')' : '')"></p>
                              </template>
                            </div>
                          </template>

                          <div class="flex items-center gap-1.5">
                            <button @click="editItem(item.cartId)"
                                    class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 text-[10px] font-black transition-colors">
                              <i class="fas fa-pen text-[9px]"></i>Edit
                            </button>
                            <button @click="$store.cart.removeItem(item.cartId)"
                                    class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 text-[10px] font-black transition-colors">
                              <i class="fas fa-times text-[9px]"></i>Remove
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </template>
            </div>

            <!-- Checkout Footer -->
            <div class="border-t border-gray-100 px-4 py-4 space-y-3 flex-shrink-0">

              <!-- Total (checked items only) -->
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total</span>
                <span class="text-lg font-black text-gray-900"
                      x-text="'RM ' + $store.cart.checkedTotal.toFixed(2)"></span>
              </div>

              <!-- Order Type Toggle -->
              <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Order Type</p>
                <div class="grid grid-cols-2 gap-2">
                  <button @click="orderType = 'self_pickup'; tableNumber = ''; tableError = false"
                          :class="orderType === 'self_pickup' ? 'bg-slate-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                          class="flex flex-col items-center justify-center py-3 px-2 rounded-xl transition-all">
                    <i class="fas fa-person-walking text-sm mb-1"></i>
                    <span class="text-[10px] font-black uppercase tracking-wide leading-none">Self Pickup</span>
                  </button>
                  <button @click="orderType = 'table'"
                          :class="orderType === 'table' ? 'bg-slate-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                          class="flex flex-col items-center justify-center py-3 px-2 rounded-xl transition-all">
                    <i class="fas fa-chair text-sm mb-1"></i>
                    <span class="text-[10px] font-black uppercase tracking-wide leading-none">Table No.</span>
                  </button>
                </div>
              </div>

              <!-- Table Number Input (number-only) -->
              <div x-show="orderType === 'table'" x-transition>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1.5">Table Number</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                    <i class="fas fa-hashtag"></i>
                  </span>
                  <input type="number"
                         x-model.number="tableNumber"
                         min="1"
                         inputmode="numeric"
                         @keydown="!/^\d$/.test($event.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Tab','Home','End'].includes($event.key) && $event.preventDefault()"
                         @paste="$event.preventDefault(); const n = parseInt($event.clipboardData.getData('text').replace(/\D/g,'')); if(n > 0) tableNumber = n"
                         @input="tableError = false"
                         placeholder="Enter table number"
                         class="w-full pl-9 pr-3 py-2.5 rounded-xl border outline-none text-sm font-bold text-gray-900 transition-all focus:ring-2"
                         :class="tableError
                           ? 'border-red-400 focus:border-red-400 focus:ring-red-100'
                           : 'border-gray-200 focus:border-amber-400 focus:ring-amber-100'">
                </div>
                <p x-show="tableError" class="text-[10px] text-red-500 font-bold mt-1.5">
                  <i class="fas fa-exclamation-circle mr-1"></i>Please enter your table number.
                </p>
              </div>

              <!-- Error -->
              <p x-show="orderError" x-text="orderError"
                 class="text-xs text-red-500 font-medium text-center"></p>

              <!-- Checkout Button -->
              <button @click="openPaymentModal()"
                      :disabled="!canCheckout || placing"
                      class="w-full py-3 rounded-xl font-black text-sm transition-all flex items-center justify-center gap-2"
                      :class="canCheckout && !placing
                        ? 'bg-amber-400 hover:bg-amber-500 text-amber-900 shadow-md hover:shadow-lg'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                <template x-if="!placing">
                  <span><i class="fas fa-credit-card mr-1.5"></i>Checkout</span>
                </template>
                <template x-if="placing">
                  <span><i class="fas fa-spinner fa-spin mr-1.5"></i>Placing...</span>
                </template>
              </button>

            </div>
          </div>
          <!-- ════════════════════════════════════════
               Payment Method Modal
          ════════════════════════════════════════ -->
          <div x-show="showPaymentModal"
               style="display:none"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100"
               x-transition:leave-end="opacity-0"
               class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

            <div @click.away="showPaymentModal = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden">

              <!-- ── Step: Choose ── -->
              <div x-show="paymentStep === 'choose'">
                <div class="px-6 pt-6 pb-2 text-center">
                  <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-credit-card text-xl text-amber-500"></i>
                  </div>
                  <h3 class="font-black text-gray-900 text-base mb-1">Choose Payment Method</h3>
                  <p class="text-xs text-gray-400 font-medium">How would you like to pay?</p>
                </div>
                <div class="px-6 pb-6 pt-4 space-y-3">
                  <!-- Online Banking -->
                  <button @click="selectPayment('online_banking')"
                          class="w-full flex items-center gap-4 px-4 py-4 rounded-2xl border-2 border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-200 transition-colors">
                      <i class="fas fa-building-columns text-blue-600"></i>
                    </div>
                    <div class="text-left">
                      <p class="text-sm font-black text-gray-800">Online Banking</p>
                      <p class="text-[10px] text-gray-400 font-medium">FPX, e-Wallets & more</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 ml-auto group-hover:text-blue-400 transition-colors"></i>
                  </button>
                  <!-- Cash -->
                  <button @click="selectPayment('cash')"
                          class="w-full flex items-center gap-4 px-4 py-4 rounded-2xl border-2 border-gray-100 hover:border-emerald-300 hover:bg-emerald-50 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-200 transition-colors">
                      <i class="fas fa-money-bill-wave text-emerald-600"></i>
                    </div>
                    <div class="text-left">
                      <p class="text-sm font-black text-gray-800">Cash</p>
                      <p class="text-[10px] text-gray-400 font-medium">Pay at the food truck</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 ml-auto group-hover:text-emerald-400 transition-colors"></i>
                  </button>
                  <!-- Cancel -->
                  <button @click="showPaymentModal = false"
                          class="w-full py-2.5 text-xs font-black text-gray-400 hover:text-gray-600 transition-colors">
                    Cancel
                  </button>
                </div>
              </div>

              <!-- ── Step: Online Banking ── -->
              <div x-show="paymentStep === 'online_banking'">
                <!-- Header -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                  <button @click="paymentStep = 'choose'"
                          class="w-8 h-8 rounded-xl hover:bg-gray-100 flex items-center justify-center text-gray-400 transition-colors">
                    <i class="fas fa-arrow-left text-sm"></i>
                  </button>
                  <h3 class="font-black text-gray-900 text-sm">Online Banking</h3>
                </div>

                <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                  <!-- eWallets -->
                  <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">e-Wallets</p>
                    <div class="grid grid-cols-2 gap-2">
                      <button @click="selectBank('Touch \'n Go')"
                              class="flex items-center gap-2.5 px-3 py-3 rounded-xl border border-gray-100 hover:border-teal-300 hover:bg-teal-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg bg-teal-500 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-mobile-screen-button text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700 leading-tight">Touch 'n Go</span>
                      </button>
                      <button @click="selectBank('GrabPay')"
                              class="flex items-center gap-2.5 px-3 py-3 rounded-xl border border-gray-100 hover:border-green-300 hover:bg-green-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-car text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700 leading-tight">GrabPay</span>
                      </button>
                      <button @click="selectBank('Boost')"
                              class="flex items-center gap-2.5 px-3 py-3 rounded-xl border border-gray-100 hover:border-red-300 hover:bg-red-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-bolt text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700 leading-tight">Boost</span>
                      </button>
                      <button @click="selectBank('ShopeePay')"
                              class="flex items-center gap-2.5 px-3 py-3 rounded-xl border border-gray-100 hover:border-orange-300 hover:bg-orange-50 transition-all group">
                        <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-bag-shopping text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700 leading-tight">ShopeePay</span>
                      </button>
                    </div>
                  </div>

                  <!-- FPX Internet Banking -->
                  <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">FPX Internet Banking</p>
                    <div class="space-y-1.5">
                      <button @click="selectBank('Maybank2u')"
                              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:border-yellow-300 hover:bg-yellow-50 transition-all">
                        <div class="w-8 h-8 rounded-lg bg-yellow-400 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-university text-yellow-900 text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700">Maybank2u</span>
                        <i class="fas fa-chevron-right text-gray-200 ml-auto text-xs"></i>
                      </button>
                      <button @click="selectBank('CIMB Clicks')"
                              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:border-red-300 hover:bg-red-50 transition-all">
                        <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-university text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700">CIMB Clicks</span>
                        <i class="fas fa-chevron-right text-gray-200 ml-auto text-xs"></i>
                      </button>
                      <button @click="selectBank('RHB Now')"
                              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:border-purple-300 hover:bg-purple-50 transition-all">
                        <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-university text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700">RHB Now</span>
                        <i class="fas fa-chevron-right text-gray-200 ml-auto text-xs"></i>
                      </button>
                      <button @click="selectBank('Public Bank')"
                              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition-all">
                        <div class="w-8 h-8 rounded-lg bg-blue-700 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-university text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700">Public Bank</span>
                        <i class="fas fa-chevron-right text-gray-200 ml-auto text-xs"></i>
                      </button>
                      <button @click="selectBank('Hong Leong Bank')"
                              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:border-green-300 hover:bg-green-50 transition-all">
                        <div class="w-8 h-8 rounded-lg bg-green-700 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-university text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700">Hong Leong Bank</span>
                        <i class="fas fa-chevron-right text-gray-200 ml-auto text-xs"></i>
                      </button>
                      <button @click="selectBank('Bank Islam')"
                              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100 hover:border-teal-300 hover:bg-teal-50 transition-all">
                        <div class="w-8 h-8 rounded-lg bg-teal-700 flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-university text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-black text-gray-700">Bank Islam</span>
                        <i class="fas fa-chevron-right text-gray-200 ml-auto text-xs"></i>
                      </button>
                    </div>
                  </div>

                </div>
              </div>

              <!-- ── Step: Cash ── -->
              <div x-show="paymentStep === 'cash'">
                <!-- Header -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                  <button @click="paymentStep = 'choose'"
                          class="w-8 h-8 rounded-xl hover:bg-gray-100 flex items-center justify-center text-gray-400 transition-colors">
                    <i class="fas fa-arrow-left text-sm"></i>
                  </button>
                  <h3 class="font-black text-gray-900 text-sm">Cash Payment</h3>
                </div>
                <div class="px-6 py-6 text-center">
                  <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-money-bill-wave text-3xl text-emerald-500"></i>
                  </div>
                  <p class="text-sm font-bold text-gray-700 leading-relaxed mb-6">
                    Please make a cash payment at our food truck to proceed with your orders.
                  </p>
                  <button @click="confirmCash()"
                          class="w-full py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-sm transition-all shadow-md">
                    <i class="fas fa-check mr-2"></i>Confirm Order
                  </button>
                </div>
              </div>

            </div>
            <!-- end modal box -->

          </div>
          <!-- end payment modal overlay -->

          <!-- ════════════════════════════════════════
               Receipt Modal
          ════════════════════════════════════════ -->
          <div x-show="showReceiptModal"
               style="display:none"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100"
               x-transition:leave-end="opacity-0"
               class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

            <div @click.away="showReceiptModal = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden">

              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                  <h3 class="font-black text-gray-900 text-base">Order Receipt</h3>
                  <p class="text-xs text-gray-400 mt-0.5">Complete summary of your submitted order.</p>
                </div>
                <button @click="showReceiptModal = false"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 transition-all">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <div class="px-6 py-5 max-h-[75vh] overflow-y-auto space-y-4">
                <template x-if="lastOrder">
                  <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Truck Name</p>
                        <p class="text-sm font-black text-gray-800"
                           x-text="lastOrder.truck_name || 'Food Truck'"></p>
                      </div>
                      <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Order Number</p>
                        <p class="text-sm font-black text-gray-800"
                           x-text="'#' + String(lastOrder.id || 0).padStart(4, '0')"></p>
                      </div>
                      <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Payment Time</p>
                        <p class="text-sm font-black text-gray-800"
                           x-text="formatDateTime(lastOrder.payment_time || lastOrder.created_at)"></p>
                      </div>
                      <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Payment Method</p>
                        <p class="text-sm font-black text-gray-800"
                           x-text="paymentMethodLabel(lastOrder.payment_method)"></p>
                      </div>
                      <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Order Type</p>
                        <p class="text-sm font-black text-gray-800"
                           x-text="lastOrder.order_type === 'table'
                            ? 'Table #' + (lastOrder.table_number || '-')
                            : 'Self Pickup'"></p>
                      </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-gray-100">
                      <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide">
                          <tr>
                            <th class="px-3 py-3 text-left font-black">Menu Name</th>
                            <th class="px-3 py-3 text-left font-black">Base Price</th>
                            <th class="px-3 py-3 text-left font-black">Qty</th>
                            <th class="px-3 py-3 text-left font-black">Selected Choices</th>
                            <th class="px-3 py-3 text-left font-black">Final Price</th>
                            <th class="px-3 py-3 text-left font-black">Status</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                          <template x-for="(item, idx) in (lastOrder.items || [])" :key="idx">
                            <tr class="bg-white">
                              <td class="px-3 py-3 text-gray-800 font-bold" x-text="item.name || 'Menu Item'"></td>
                              <td class="px-3 py-3 text-gray-700 whitespace-nowrap" x-text="formatCurrency(item.base_price)"></td>
                              <td class="px-3 py-3 text-gray-700" x-text="item.quantity || 1"></td>
                              <td class="px-3 py-3 text-gray-600">
                                <template x-if="item.selected_choices && item.selected_choices.length > 0">
                                  <div class="space-y-0.5">
                                    <template x-for="(choice, cIdx) in item.selected_choices" :key="cIdx">
                                      <p x-text="choice.choice_name + (parseFloat(choice.price || 0) > 0 ? ' (+RM ' + parseFloat(choice.price).toFixed(2) + ')' : '')"></p>
                                    </template>
                                  </div>
                                </template>
                                <template x-if="!item.selected_choices || item.selected_choices.length === 0">
                                  <span class="text-gray-400">-</span>
                                </template>
                              </td>
                              <td class="px-3 py-3 text-gray-700 whitespace-nowrap" x-text="formatCurrency(item.item_total)"></td>
                              <td class="px-3 py-3">
                                <span class="text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full whitespace-nowrap"
                                      :class="statusClass(item.status || lastOrder.status || 'pending')"
                                      x-text="statusLabel(item.status || lastOrder.status || 'pending')"></span>
                              </td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>

                    <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                      <span class="text-xs font-black text-gray-500 uppercase tracking-widest">Total Price</span>
                      <span class="text-lg font-black text-gray-900" x-text="formatCurrency(lastOrder.total)"></span>
                    </div>
                  </div>
                </template>
              </div>

              <div class="px-6 py-4 border-t border-gray-100 text-right">
                <button @click="showReceiptModal = false"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-amber-500 text-white font-black text-xs rounded-xl transition-all">
                  Close
                </button>
              </div>

            </div>
          </div>

        </div>
        <!-- end persistent cart sidebar -->

      </div>
      <!-- end page + cart row -->

    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('sidebar');
      const openBtn = document.getElementById('openSidebar');
      const closeBtn = document.getElementById('closeSidebar');
      if (openBtn && sidebar) {
        openBtn.addEventListener('click', () => sidebar.classList.toggle('sidebar-hidden'));
      }
      if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => sidebar.classList.add('sidebar-hidden'));
      }
    });
  </script>
</body>
</html>
