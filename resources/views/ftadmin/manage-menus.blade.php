<x-ftadmin-layout>

@php
    $user = Auth::user();
    $role = $user->role;
@endphp

<script>
function manageMenusPage() {
    return {
        menuItems: @json($menuItems),
        searchQuery: '',
        categoryFilter: '',
        showCategoryFilter: false,
        
        // Category management
        categories: [],
        showCreateCategoryModal: false,
        newCategoryName: '',
        newCategoryColor: 'purple',
        createCategoryLoading: false,
        activeCategoryActionMenu: null,
        
        // Category rename/edit modal
        showEditCategoryModal: false,
        editingCategory: null,
        editCategoryName: '',
        editCategoryColor: 'purple',
        editCategoryLoading: false,
        
        colorOptions: [
            { name: 'Purple', value: 'purple', class: 'bg-purple-500' },
            { name: 'Blue', value: 'blue', class: 'bg-blue-500' },
            { name: 'Green', value: 'green', class: 'bg-green-500' },
            { name: 'Red', value: 'red', class: 'bg-red-500' },
            { name: 'Pink', value: 'pink', class: 'bg-pink-500' },
            { name: 'Amber', value: 'amber', class: 'bg-amber-500' },
            { name: 'Cyan', value: 'cyan', class: 'bg-cyan-500' },
            { name: 'Indigo', value: 'indigo', class: 'bg-indigo-500' },
        ],

        get filteredItems() {
            return this.menuItems.filter(item => {
                const q = this.searchQuery.toLowerCase();
                const matchSearch = !q || item.name.toLowerCase().includes(q) || item.category.toLowerCase().includes(q);
                const matchCat = !this.categoryFilter || item.category.toLowerCase().includes(this.categoryFilter.toLowerCase());
                return matchSearch && matchCat;
            });
        },

        /* ── Details edit modal ── */
        showDetailsModal: false,
        detailsItem: null,
        editName: '',
        editCategory: '',
        editPrice: '',
        editDescription: '',
        detailsSaving: false,
        openDetailsModal(item) {
            this.detailsItem = item;
            this.editName        = item.name;
            this.editCategory    = item.category;
            this.editPrice       = item.base_price !== null && item.base_price !== ''
                ? parseFloat(item.base_price).toFixed(2)
                : '';
            this.editDescription = item.description || '';
            this.showDetailsModal = true;
            this.$nextTick(() => { if (this.$refs.detailsNameInput) this.$refs.detailsNameInput.focus(); });
        },
        closeDetailsModal() { this.showDetailsModal = false; this.detailsItem = null; },
        async saveDetails() {
            if (this.detailsSaving || !this.detailsItem) return;
            // Validate pricing: either edit price (base_price) OR existing choices must have prices
            const hasValidPricing = this.hasValidPricing(this.editPrice, this.detailsItem.option_groups || []);
            if (!hasValidPricing) {
                alert('Please provide pricing:\n- Fill the Base Price in Section 1, OR\n- Ensure all choices in Section 2 have prices filled');
                return;
            }
            this.detailsSaving = true;
            try {
                const res = await fetch('/ftadmin/menu/' + this.detailsItem.id + '/details', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ name: this.editName, category: this.editCategory, base_price: this.editPrice === '' ? null : this.editPrice, description: this.editDescription })
                });
                const data = await res.json();
                if (data.success) {
                    const item = this.menuItems.find(i => i.id === this.detailsItem.id);
                    if (item) { item.name = data.item.name; item.category = data.item.category; item.base_price = data.item.base_price; item.description = data.item.description; }
                    this.closeDetailsModal();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save menu details.'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to save menu details. Please try again.');
            }
            this.detailsSaving = false;
        },
        hasMissingChoiceQuantities(groups) {
            return (groups || []).some(group =>
                (group.choices || []).some(choice => {
                    if (!choice.name || choice.name.trim() === '') return false;
                    return choice.quantity === '' || choice.quantity === null || choice.quantity === undefined || isNaN(Number(choice.quantity));
                })
            );
        },
        hasValidPricing(basePrice, optionGroups) {
            // Check if base_price is filled
            const hasBasePrice = basePrice && basePrice !== '' && !isNaN(Number(basePrice));
            
            // Check if all named choices have prices filled
            const hasPricesInChoices = (optionGroups || []).every(group => {
                return (group.choices || []).every(choice => {
                    // If choice has no name, it's not required
                    if (!choice.name || choice.name.trim() === '') return true;
                    // If choice has a name, it must have a price
                    return choice.price && choice.price !== '' && !isNaN(Number(choice.price));
                });
            });
            
            // Valid if either base_price is filled OR all choice prices are filled
            return hasBasePrice || hasPricesInChoices;
        },

        /* ── Quantity inline edit ── */
        editingQtyId: null,
        editQtyValue: 0,
        qtySaving: false,
        openQtyEdit(item) {
            this.editingQtyId = item.id;
            this.editQtyValue = item.quantity;
            this.$nextTick(() => {
                const el = document.querySelector('[data-qty-input="' + item.id + '"]');
                if (el) { el.focus(); el.select(); }
            });
        },
        closeQtyEdit() { this.editingQtyId = null; },
        async saveQty(item) {
            if (this.qtySaving) return;
            this.qtySaving = true;
            try {
                const res = await fetch('/ftadmin/menu/' + item.id + '/quantity', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ quantity: parseInt(this.editQtyValue) || 0 })
                });
                const data = await res.json();
                if (data.success) { 
                    item.quantity = data.quantity; 
                    this.editingQtyId = null; 
                } else {
                    alert('Error: ' + (data.message || 'Failed to update quantity.'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to update quantity. Please try again.');
            }
            this.qtySaving = false;
        },

        /* ── Options modal ── */
        showOptionsModal: false,
        optionsItem: null,
        editOptionGroups: [],
        _editGroupIdCounter: 0,
        _editChoiceIdCounter: 0,
        optionsSaving: false,
        _newChoice(id) { return { _id: id, name: '', price: '', quantity: '', status: 'available', openMenu: false }; },
        openOptionsModal(item) {
            this.optionsItem = item;
            this._editGroupIdCounter = 0;
            this._editChoiceIdCounter = 0;
            this.editOptionGroups = (item.option_groups || []).map(group => ({
                _id: ++this._editGroupIdCounter,
                name: group.name,
                selectionType: group.selection_type,
                choices: (group.choices || []).map(choice => ({
                    _id: ++this._editChoiceIdCounter,
                    name: choice.name, price: choice.price, quantity: choice.quantity,
                    status: choice.status || 'available', openMenu: false
                }))
            }));
            this.showOptionsModal = true;
            this.$nextTick(() => { const b = this.$refs.optionsModalBody; if (b) b.scrollTop = 0; });
        },
        closeOptionsModal() { this.showOptionsModal = false; this.optionsItem = null; this.editOptionGroups = []; },
        addEditOptionGroup() {
            this._editGroupIdCounter++;
            this.editOptionGroups.push({ _id: this._editGroupIdCounter, name: '', selectionType: 'single', choices: [this._newChoice(++this._editChoiceIdCounter)] });
        },
        removeEditOptionGroup(gi) { this.editOptionGroups.splice(gi, 1); },
        addEditChoice(gi) {
            this._editChoiceIdCounter++;
            this.editOptionGroups[gi].choices.push(this._newChoice(this._editChoiceIdCounter));
            this.$nextTick(() => { const els = document.querySelectorAll('.options-modal-choice-name'); if (els.length) els[els.length-1].focus(); });
        },
        removeEditChoice(gi, ci) { this.editOptionGroups[gi].choices.splice(ci, 1); },
        async saveOptions() {
            if (this.optionsSaving || !this.optionsItem) return;
            // Validate pricing: either base_price OR all choice prices must be filled
            const basePrice = this.optionsItem.base_price;
            const allChoices = this.editOptionGroups.flatMap(g => g.choices);
            const hasValidPricing = this.hasValidPricing(basePrice, this.editOptionGroups);
            if (!hasValidPricing) {
                alert('Please provide pricing:\n- Fill the Base Price in Section 1, OR\n- Fill the Price for all choices in Section 2');
                return;
            }
            this.optionsSaving = true;
            try {
                const res = await fetch('/ftadmin/menu/' + this.optionsItem.id + '/options', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ option_groups: this.editOptionGroups })
                });
                const data = await res.json();
                if (data.success) {
                    const item = this.menuItems.find(i => i.id === this.optionsItem.id);
                    if (item) item.option_groups = data.option_groups;
                    this.closeOptionsModal();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save menu options.'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to save menu options. Please try again.');
            }
            this.optionsSaving = false;
        },

        /* ── Status toggle ── */
        async setItemStatus(item, targetStatus) {
            if (item.status === targetStatus) return;
            try {
                const res = await fetch('/ftadmin/menu/' + item.id + '/toggle-status', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) item.status = data.status;
            } catch(e) { console.error(e); }
        },

        /* ── Delete item ── */
        async deleteItem(item) {
            if (!confirm('Delete "' + item.name + '"? This cannot be undone.')) return;
            try {
                const res = await fetch('/ftadmin/menu/' + item.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) this.menuItems = this.menuItems.filter(i => i.id !== item.id);
            } catch(e) { console.error(e); }
        },

        /* ── Category Management ── */
        async loadCategories() {
            try {
                const res = await fetch('/ftadmin/menu-category/list', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) this.categories = data.categories;
            } catch(e) { console.error(e); }
        },

        openCreateCategoryModal() {
            this.showCreateCategoryModal = true;
            this.newCategoryName = '';
            this.newCategoryColor = 'purple';
        },

        closeCreateCategoryModal() {
            this.showCreateCategoryModal = false;
            this.newCategoryName = '';
            this.newCategoryColor = 'purple';
        },

        async createCategory() {
            if (!this.newCategoryName.trim()) {
                alert('Please enter a category name');
                return;
            }
            if (this.categories.some(c => c.name.toLowerCase() === this.newCategoryName.toLowerCase())) {
                alert('This category already exists');
                return;
            }
            this.createCategoryLoading = true;
            try {
                const res = await fetch('/ftadmin/menu-category/create', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ name: this.newCategoryName, color: this.newCategoryColor })
                });
                const data = await res.json();
                if (data.success) {
                    this.categories.push(data.category);
                    this.closeCreateCategoryModal();
                } else {
                    alert('Error: ' + (data.message || 'Failed to create category'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to create category. Please try again.');
            }
            this.createCategoryLoading = false;
        },

        openEditCategoryModal(category) {
            this.editingCategory = category;
            this.editCategoryName = category.name;
            this.editCategoryColor = category.color;
            this.showEditCategoryModal = true;
        },

        closeEditCategoryModal() {
            this.showEditCategoryModal = false;
            this.editingCategory = null;
            this.editCategoryName = '';
            this.editCategoryColor = 'purple';
        },

        async updateCategory() {
            if (!this.editingCategory) return;
            if (!this.editCategoryName.trim()) {
                alert('Please enter a category name');
                return;
            }
            
            // Check if name already exists (excluding current category)
            if (this.categories.some(c => c.id !== this.editingCategory.id && c.name.toLowerCase() === this.editCategoryName.toLowerCase())) {
                alert('A category with this name already exists');
                return;
            }

            this.editCategoryLoading = true;
            try {
                const res = await fetch('/ftadmin/menu-category/' + this.editingCategory.id, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ name: this.editCategoryName, color: this.editCategoryColor })
                });
                const data = await res.json();
                if (data.success) {
                    // Update local array
                    const idx = this.categories.findIndex(c => c.id === this.editingCategory.id);
                    if (idx >= 0) this.categories[idx] = data.category;
                    this.closeEditCategoryModal();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update category'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to update category. Please try again.');
            }
            this.editCategoryLoading = false;
        },

        async deleteCategory(category) {
            if (!confirm('Delete category "' + category.name + '"? Menu items in this category will be moved to Uncategorized.')) return;

            try {
                const res = await fetch('/ftadmin/menu-category/' + category.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) {
                    // Remove from local array
                    this.categories = this.categories.filter(c => c.id !== category.id);
                    // If we were filtering by this category, clear the filter
                    if (this.categoryFilter === category.name) this.categoryFilter = '';
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete category'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to delete category. Please try again.');
            }
        },

        getColorClass(color) {
            const colors = {
                'purple': 'bg-purple-500',
                'blue': 'bg-blue-500',
                'green': 'bg-green-500',
                'red': 'bg-red-500',
                'pink': 'bg-pink-500',
                'amber': 'bg-amber-500',
                'cyan': 'bg-cyan-500',
                'indigo': 'bg-indigo-500',
            };
            return colors[color] || 'bg-gray-500';
        },

        getBgColorClass(color) {
            const colors = {
                'purple': 'rgb(243, 232, 255)',
                'blue': 'rgb(239, 246, 255)',
                'green': 'rgb(240, 253, 250)',
                'red': 'rgb(254, 242, 242)',
                'pink': 'rgb(252, 240, 247)',
                'amber': 'rgb(255, 251, 235)',
                'cyan': 'rgb(236, 254, 255)',
                'indigo': 'rgb(238, 242, 255)',
            };
            return colors[color] || 'rgb(245, 245, 245)';
        },

        getTextColorClass(color) {
            const colors = {
                'purple': 'rgb(147, 51, 234)',
                'blue': 'rgb(37, 99, 235)',
                'green': 'rgb(16, 185, 129)',
                'red': 'rgb(239, 68, 68)',
                'pink': 'rgb(236, 72, 153)',
                'amber': 'rgb(217, 119, 6)',
                'cyan': 'rgb(34, 211, 238)',
                'indigo': 'rgb(79, 70, 229)',
            };
            return colors[color] || 'rgb(107, 114, 128)';
        },

        init() {
            this.loadCategories();
        }
    };
}
</script>

<div class="flex flex-col h-full" x-data="manageMenusPage()">

    <!-- Top Header -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
            <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center text-gray-400 space-x-2">
                <span class="w-5 flex justify-center"><i class="fas fa-utensils text-sm"></i></span>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-bold text-gray-700">Manage Menus</span>
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

    <!-- Page Body -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Page Title -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight">Manage Menus</h1>
                    <p class="text-sm text-gray-500 mt-0.5">All menu items available on your food truck.</p>
                </div>
                <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-3 py-1.5 rounded-full"
                      x-text="filteredItems.length + ' ' + (filteredItems.length === 1 ? 'item' : 'items')"></span>
            </div>

            <!-- Search + Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <!-- Search -->
                <div class="relative flex-1 w-full">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-sm pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search menu name or category…"
                           class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all shadow-sm">
                </div>

                <!-- Category Filter Dropdown -->
                <div class="relative flex-shrink-0">
                    <button type="button" @click.stop="showCategoryFilter = !showCategoryFilter"
                            :class="categoryFilter ? 'bg-purple-50 text-purple-600 border border-purple-200' : 'bg-gray-100 text-gray-500 border border-transparent hover:bg-gray-200'"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap">
                        <i class="fas fa-filter text-xs"></i>
                        <span x-text="categoryFilter ? (categoryFilter === 'food' ? 'Foods' : categoryFilter === 'drink' ? 'Drinks' : categoryFilter === 'dessert' ? 'Desserts' : categoryFilter) : 'Filter'"></span>
                        <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="showCategoryFilter ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="showCategoryFilter"
                         @click.away="showCategoryFilter = false; activeCategoryActionMenu = null"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         style="display:none;"
                         class="absolute right-0 top-full mt-1 bg-white rounded-2xl shadow-xl border border-gray-100 py-1.5 w-40 z-50">

                        <!-- All (clear filter) -->
                        <button type="button" @click.stop="categoryFilter = ''; showCategoryFilter = false"
                                :class="!categoryFilter ? 'bg-gray-50 font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50'"
                                class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                            <span class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0"></span>
                            All
                        </button>
                        <div class="border-t border-gray-50 mx-3 my-0.5"></div>

                        <!-- Foods -->
                        <button type="button" @click.stop="categoryFilter = 'food'; showCategoryFilter = false"
                                :class="categoryFilter === 'food' ? 'bg-purple-50 font-black text-purple-600' : 'text-gray-500 hover:bg-purple-50 hover:text-purple-600'"
                                class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                            <span class="w-2 h-2 rounded-full bg-purple-500 flex-shrink-0"></span>
                            Foods
                        </button>

                        <!-- Drinks -->
                        <button type="button" @click.stop="categoryFilter = 'drink'; showCategoryFilter = false"
                                :class="categoryFilter === 'drink' ? 'bg-blue-50 font-black text-blue-600' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-600'"
                                class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                            <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                            Drinks
                        </button>

                        <!-- Desserts -->
                        <button type="button" @click.stop="categoryFilter = 'dessert'; showCategoryFilter = false"
                                :class="categoryFilter === 'dessert' ? 'bg-pink-50 font-black text-pink-600' : 'text-gray-500 hover:bg-pink-50 hover:text-pink-600'"
                                class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                            <span class="w-2 h-2 rounded-full bg-pink-500 flex-shrink-0"></span>
                            Desserts
                        </button>

                        <!-- Divider (show only if custom categories exist) -->
                        <div x-show="categories.length > 0" class="border-t border-gray-50 mx-3 my-0.5"></div>

                        <!-- Custom Categories with Action Menu -->
                        <template x-for="cat in categories" :key="cat.id">
                            <div class="relative flex items-center">
                                <!-- Category filter button -->
                                <button type="button" @click.stop="categoryFilter = cat.name; showCategoryFilter = false"
                                        :class="categoryFilter === cat.name ? 'font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50'"
                                        class="flex-1 text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors"
                                        :style="categoryFilter === cat.name ? `background-color: ${getBgColorClass(cat.color)}; color: ${getTextColorClass(cat.color)};` : ''">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0" :class="getColorClass(cat.color)"></span>
                                    <span x-text="cat.name"></span>
                                </button>

                                <!-- Action button (3 dots) -->
                                <div class="relative">
                                    <button type="button" @click.stop="activeCategoryActionMenu = activeCategoryActionMenu === cat.id ? null : cat.id"
                                            class="px-3 py-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors flex-shrink-0">
                                        <i class="fas fa-ellipsis-v text-xs"></i>
                                    </button>

                                    <!-- Action Context Menu -->
                                    <div x-show="activeCategoryActionMenu === cat.id" 
                                         @click.away="activeCategoryActionMenu = null"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         style="display:none;"
                                         class="absolute right-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 w-40 z-50">
                                        
                                        <!-- Rename option -->
                                        <button type="button" @click.stop="openEditCategoryModal(cat); activeCategoryActionMenu = null"
                                                class="w-full text-left px-4 py-2.5 text-xs font-bold text-blue-600 hover:bg-blue-50 flex items-center gap-3 transition-colors">
                                            <i class="fas fa-pen-to-square w-3 text-center"></i>
                                            <span>Rename & Color</span>
                                        </button>

                                        <div class="border-t border-gray-100 my-1"></div>

                                        <!-- Delete option -->
                                        <button type="button" @click.stop="deleteCategory(cat); activeCategoryActionMenu = null"
                                                class="w-full text-left px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 flex items-center gap-3 transition-colors">
                                            <i class="fas fa-trash w-3 text-center"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Divider before Uncategorized -->
                        <div class="border-t border-gray-50 mx-3 my-0.5"></div>

                        <!-- Uncategorized (always at bottom) -->
                        <button type="button" @click.stop="categoryFilter = 'Uncategorized'; showCategoryFilter = false"
                                :class="categoryFilter === 'Uncategorized' ? 'bg-gray-100 font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-600'"
                                class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                            <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                            Uncategorized
                        </button>
                    </div>
                </div>

                <!-- Create Category Button -->
                <button type="button" @click="openCreateCategoryModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-blue-600 text-white hover:bg-blue-700 transition-all whitespace-nowrap shadow-sm">
                    <i class="fas fa-plus text-xs"></i>
                    <span>New Category</span>
                </button>
            </div>

            <!-- Empty State — no items at all -->
            <div x-show="menuItems.length === 0"
                 class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                    <i class="fas fa-utensils text-2xl text-gray-300"></i>
                </div>
                <p class="text-sm font-bold text-gray-500">No Menu Items Yet</p>
                <p class="text-xs text-gray-400 mt-1">Add menu items from the Dashboard to see them here.</p>
            </div>

            <!-- Empty State — filtered no results -->
            <div x-show="menuItems.length > 0 && filteredItems.length === 0"
                 class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 flex flex-col items-center justify-center text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                    <i class="fas fa-search text-xl text-gray-300"></i>
                </div>
                <p class="text-sm font-bold text-gray-500">No Results Found</p>
                <p class="text-xs text-gray-400 mt-1">Try a different search term or filter.</p>
            </div>

            <!-- Cards Grid -->
            <div x-show="filteredItems.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <template x-for="item in filteredItems" :key="item.id">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-visible flex flex-col
                                hover:shadow-lg hover:scale-[1.025] transition-all duration-200">

                        <!-- Image -->
                        <div class="relative w-full h-44 bg-gray-100 rounded-t-2xl overflow-hidden flex-shrink-0">
                            <template x-if="item.image">
                                <img :src="'/storage/' + item.image" :alt="item.name"
                                     class="w-full h-full object-contain">
                            </template>
                            <template x-if="!item.image">
                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                                    <i class="fas fa-image text-3xl mb-1"></i>
                                    <span class="text-xs font-medium">No Image</span>
                                </div>
                            </template>
                            <!-- Status Badge -->
                            <div class="absolute top-2.5 right-2.5">
                                <span x-show="item.status === 'available'"
                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>Available
                                </span>
                                <span x-show="item.status !== 'available'"
                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-600 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>Unavailable
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="flex flex-col flex-1 p-4 space-y-3">

                            <!-- Name, Category, Price — click to edit -->
                            <button @click.stop="openDetailsModal(item)"
                                    class="w-full text-left group rounded-xl p-2 -mx-2 hover:bg-gray-50 transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-bold text-gray-900 leading-tight line-clamp-1" x-text="item.name"></h3>
                                        <span class="text-[11px] font-semibold text-blue-500 uppercase tracking-wider" x-text="item.category"></span>
                                    </div>
                                    <i class="fas fa-pen text-[10px] text-gray-300 group-hover:text-blue-400 transition-colors mt-0.5 ml-2 flex-shrink-0"></i>
                                </div>
                                <div class="flex items-baseline space-x-1 mt-2">
                                    <span class="text-xs text-gray-400 font-medium">RM</span>
                                    <span class="text-lg font-black text-gray-900" x-text="item.base_price !== null && item.base_price !== '' ? parseFloat(item.base_price).toFixed(2) : '0.00'"></span>
                                </div>
                                <p x-show="item.description" x-text="item.description"
                                   class="text-[11px] text-gray-400 leading-relaxed line-clamp-2 mt-1"></p>
                            </button>

                            <div class="border-t border-gray-100"></div>

                            <!-- Quantity Button -->
                            <div class="relative">
                                <button @click.stop="openQtyEdit(item)"
                                        :class="editingQtyId === item.id ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-600'"
                                        class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all duration-150 group">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-box text-sm"
                                           :class="editingQtyId === item.id ? 'text-blue-200' : 'text-gray-400 group-hover:text-blue-400'"></i>
                                        <span class="text-sm font-bold"
                                              x-text="item.quantity === null || item.quantity === '' || item.quantity === undefined ? '-' : (item.quantity > 0 ? item.quantity + ' Left' : 'Out of Stock')"></span>
                                    </div>
                                    <i class="fas fa-pen text-xs opacity-40"></i>
                                </button>

                                <!-- Qty Popover -->
                                <div x-show="editingQtyId === item.id"
                                     @click.away="closeQtyEdit()"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     style="display:none;"
                                     class="absolute bottom-full mb-2 left-0 right-0 bg-white border border-gray-200 rounded-2xl shadow-2xl p-3 z-30">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Update Quantity</p>
                                    <div class="flex items-center gap-2">
                                        <input type="number" min="0" inputmode="numeric" pattern="[0-9]*"
                                               :data-qty-input="item.id"
                                               x-model="editQtyValue"
                                               @keydown="if(!/^[0-9]$/.test($event.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Enter','Escape'].includes($event.key)) $event.preventDefault()"
                                               @keydown.enter.stop="saveQty(item)"
                                               @keydown.escape.stop="closeQtyEdit()"
                                               class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                                        <button @click.stop="saveQty(item)" :disabled="qtySaving"
                                                class="w-9 h-9 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center transition-all disabled:opacity-60 flex-shrink-0">
                                            <i class="fas text-xs" :class="qtySaving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Options Button -->
                            <button @click.stop="openOptionsModal(item)"
                                    class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-100 hover:bg-purple-50 hover:text-purple-600 text-gray-700 rounded-xl transition-all duration-150 group">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-list text-sm text-gray-400 group-hover:text-purple-400"></i>
                                    <span class="text-sm font-bold"
                                          x-text="(item.option_groups ? item.option_groups.length : 0) + ' ' + ((item.option_groups ? item.option_groups.length : 0) === 1 ? 'Option Group' : 'Option Groups')"></span>
                                </div>
                                <i class="fas fa-pen text-xs opacity-40"></i>
                            </button>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-3 gap-1.5 pt-1">
                                <button @click.stop="setItemStatus(item, 'available')"
                                        :disabled="item.status === 'available'"
                                        :class="item.status === 'available'
                                            ? 'bg-emerald-500 text-white cursor-default shadow-sm'
                                            : 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600'"
                                        class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 disabled:opacity-80">
                                    <i class="fas fa-check text-xs mb-0.5"></i>
                                    <span class="text-[10px] font-black uppercase tracking-wide leading-none">Available</span>
                                </button>
                                <button @click.stop="setItemStatus(item, 'unavailable')"
                                        :disabled="item.status === 'unavailable'"
                                        :class="item.status === 'unavailable'
                                            ? 'bg-orange-400 text-white cursor-default shadow-sm'
                                            : 'bg-gray-100 text-gray-500 hover:bg-orange-50 hover:text-orange-500'"
                                        class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 disabled:opacity-80">
                                    <i class="fas fa-ban text-xs mb-0.5"></i>
                                    <span class="text-[10px] font-black uppercase tracking-wide leading-none">Unavailable</span>
                                </button>
                                <button @click.stop="deleteItem(item)"
                                        class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-500 transition-all duration-150">
                                    <i class="fas fa-trash-alt text-xs mb-0.5"></i>
                                    <span class="text-[10px] font-black uppercase tracking-wide leading-none">Delete</span>
                                </button>
                            </div>

                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- ── Details Edit Modal ── -->
    <div x-show="showDetailsModal"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">

        <div @click.away="closeDetailsModal()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-md flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-black text-gray-900">Edit Details</h2>
                    <p class="text-xs text-gray-400 font-medium mt-0.5" x-text="detailsItem ? detailsItem.name : ''"></p>
                </div>
                <button @click="closeDetailsModal()" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Menu Name</label>
                    <input type="text" x-ref="detailsNameInput" x-model="editName" placeholder="e.g. Nasi Lemak"
                           @keydown.enter="$refs.detailsCategoryInput.focus()"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                </div>
                <!-- Category -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Category</label>
                    <div class="relative group">
                        <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                        <select x-ref="detailsCategoryInput" x-model="editCategory"
                                class="w-full pl-11 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all outline-none text-sm font-bold text-gray-700 appearance-none cursor-pointer">
                            <option value="" disabled>Select category</option>
                            <option value="Foods">Foods</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Desserts">Desserts</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <!-- Price -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Base Price (RM)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400 pointer-events-none">RM</span>
                        <input type="text" x-ref="detailsPriceInput" x-model="editPrice" placeholder="0.00" inputmode="decimal"
                               @input="editPrice = $event.target.value.replace(/[^0-9.]/g,'').replace(/(\..*?)\..*/g,'$1'); $event.target.value = editPrice"
                               class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                    </div>
                </div>
                <!-- Description -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5 block">Description <span class="normal-case font-medium text-gray-400">(optional)</span></label>
                    <textarea x-model="editDescription" placeholder="Describe the menu item…" rows="3"
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all resize-none"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
                <button @click="closeDetailsModal()" class="flex-1 px-6 py-3 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all">
                    Cancel
                </button>
                <button @click="saveDetails()" :disabled="detailsSaving"
                        class="flex-[2] px-6 py-3 bg-slate-900 hover:bg-blue-600 text-white rounded-2xl text-sm font-black shadow-lg transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fas" :class="detailsSaving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span x-text="detailsSaving ? 'Saving...' : 'Save Details'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Options Editor Modal ── -->
    <div x-show="showOptionsModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">

        <div @click.away="closeOptionsModal()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[85vh]">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h2 class="text-base font-black text-gray-900">Edit Options</h2>
                    <p class="text-xs text-gray-400 font-medium mt-0.5" x-text="optionsItem ? optionsItem.name : ''"></p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="addEditOptionGroup()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-600 text-xs font-black rounded-xl border border-purple-200 transition-all">
                        <i class="fas fa-plus text-[10px]"></i>
                        Add Group
                    </button>
                    <button @click="closeOptionsModal()"
                            class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4" x-ref="optionsModalBody">

                <div x-show="editOptionGroups.length === 0"
                     class="border-2 border-dashed border-gray-100 rounded-2xl py-10 text-center">
                    <i class="fas fa-list text-2xl text-gray-200 mb-3 block"></i>
                    <p class="text-xs font-bold text-gray-300 uppercase tracking-wider">No option groups yet</p>
                    <p class="text-[11px] text-gray-300 mt-1">Click "Add Group" to create one</p>
                </div>

                <template x-for="(group, gi) in editOptionGroups" :key="group._id">
                    <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/30">

                        <!-- Group header -->
                        <div class="flex items-end gap-3 mb-4">
                            <div class="flex-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-purple-500 mb-1.5 block">Group Name</label>
                                <input type="text" x-model="group.name" placeholder="e.g. Sugar Level"
                                       class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                            </div>
                            <div class="flex-shrink-0">
                                <label class="text-[10px] font-black uppercase tracking-widest text-purple-500 mb-1.5 block">Selection Type</label>
                                <div class="flex rounded-xl border border-gray-200 overflow-hidden bg-white">
                                    <button type="button" @click="group.selectionType = 'single'"
                                            :class="group.selectionType === 'single' ? 'bg-purple-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                                            class="px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">Single</button>
                                    <div class="w-px bg-gray-200"></div>
                                    <button type="button" @click="group.selectionType = 'multiple'"
                                            :class="group.selectionType === 'multiple' ? 'bg-purple-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                                            class="px-4 py-2.5 text-xs font-black uppercase tracking-wider transition-all">Multiple</button>
                                </div>
                            </div>
                            <button type="button" @click="removeEditOptionGroup(gi)"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all flex-shrink-0">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Column headers -->
                        <div class="grid grid-cols-12 gap-2 px-1 mb-2">
                            <div class="col-span-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Choice Name</div>
                            <div class="col-span-2 text-[10px] font-black uppercase tracking-widest text-purple-400">Price</div>
                            <div class="col-span-2 text-[10px] font-black uppercase tracking-widest text-purple-400">Qty</div>
                            <div class="col-span-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</div>
                            <div class="col-span-1"></div>
                        </div>

                        <!-- Choices -->
                        <div class="space-y-2">
                            <template x-for="(choice, ci) in group.choices" :key="choice._id">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <div class="col-span-4">
                                        <input type="text" x-model="choice.name" placeholder="Choice name"
                                               class="options-modal-choice-name w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                                    </div>
                                    <div class="col-span-2 relative">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400 pointer-events-none">RM</span>
                                        <input type="text" x-model="choice.price" placeholder="0.00" inputmode="decimal"
                                               @input="choice.price = $event.target.value.replace(/[^0-9.]/g,'').replace(/(\..*?)\..*/g,'$1'); $event.target.value = choice.price"
                                               class="w-full pl-7 pr-1 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                                    </div>
                                    <div class="col-span-2">
                                             <input type="text" x-model="choice.quantity" placeholder="Qty" inputmode="numeric"
                                                 @input="choice.quantity = $event.target.value.replace(/[^0-9]/g,''); $event.target.value = choice.quantity; if (choice.quantity === '0') choice.status = 'unavailable'"
                                               class="w-full px-2 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all text-center">
                                    </div>
                                    <div class="col-span-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-black uppercase border whitespace-nowrap"
                                              :class="choice.status === 'unavailable' ? 'bg-orange-50 text-orange-500 border-orange-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'"
                                              x-text="choice.status === 'unavailable' ? 'Unavailable' : 'Available'">
                                        </span>
                                    </div>
                                    <div class="col-span-1 flex items-center justify-center gap-1">
                                        <div class="relative">
                                            <button type="button" @click.stop="choice.openMenu = !choice.openMenu"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-purple-100 text-gray-400 hover:text-purple-600 transition-all">
                                                <i class="fas fa-ellipsis-v text-xs"></i>
                                            </button>
                                            <div x-show="choice.openMenu" @click.away="choice.openMenu = false"
                                                 style="display:none;"
                                                 class="absolute right-0 bottom-full mb-1 bg-white rounded-xl shadow-xl border border-gray-100 py-1 w-36 z-50">
                                                <button type="button"
                                                        @click.stop="choice.status = choice.status === 'unavailable' ? 'available' : 'unavailable'; choice.openMenu = false"
                                                        :class="choice.status === 'unavailable' ? 'text-emerald-600 hover:bg-emerald-50' : 'text-orange-500 hover:bg-orange-50'"
                                                        class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                                    <i class="fas w-3 text-center" :class="choice.status === 'unavailable' ? 'fa-check' : 'fa-ban'"></i>
                                                    <span x-text="choice.status === 'unavailable' ? 'Set Available' : 'Unavailable'"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeEditChoice(gi, ci)"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addEditChoice(gi)"
                                class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-purple-500 hover:text-purple-700 transition-colors">
                            <i class="fas fa-plus text-[10px]"></i>
                            Add Choice
                        </button>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3 flex-shrink-0">
                <button @click="closeOptionsModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all">
                    Cancel
                </button>
                <button @click="saveOptions()" :disabled="optionsSaving"
                        class="flex-[2] px-6 py-3 bg-slate-900 hover:bg-purple-600 text-white rounded-2xl text-sm font-black shadow-lg transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fas" :class="optionsSaving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span x-text="optionsSaving ? 'Saving...' : 'Save Options'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Create Category Modal ── -->
    <div x-show="showCreateCategoryModal"
         @click.away="closeCreateCategoryModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">

        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-md flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-black text-gray-900">Create New Category</h2>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">Add a custom category for your menu</p>
                </div>
                <button @click="closeCreateCategoryModal()" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Category Name -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Category Name</label>
                    <input type="text" x-model="newCategoryName" placeholder="e.g. Alacarte, Set, Promotions"
                           @keydown.enter="createCategory()"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                </div>

                <!-- Color Picker -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 block">Category Color</label>
                    <div class="flex flex-wrap gap-3">
                        <template x-for="color in colorOptions" :key="color.value">
                            <button type="button"
                                    @click="newCategoryColor = color.value"
                                    :class="newCategoryColor === color.value ? 'ring-2 ring-offset-2 ring-blue-600 scale-110' : 'hover:scale-105'"
                                    class="flex flex-col items-center gap-1.5 transition-all">
                                <div :class="color.class + ' w-10 h-10 rounded-full border-2 border-white shadow-md transition-all'"></div>
                                <span class="text-[10px] font-bold text-gray-600 text-center" x-text="color.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
                <button @click="closeCreateCategoryModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all">
                    Cancel
                </button>
                <button @click="createCategory()" :disabled="createCategoryLoading"
                        class="flex-[2] px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-black shadow-lg transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fas" :class="createCategoryLoading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="createCategoryLoading ? 'Creating...' : 'Create Category'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Edit Category Modal ── -->
    <div x-show="showEditCategoryModal"
         @click.away="closeEditCategoryModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">

        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-md flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-black text-gray-900">Edit Category</h2>
                    <p class="text-xs text-gray-400 font-medium mt-0.5" x-text="editingCategory ? editingCategory.name : ''"></p>
                </div>
                <button @click="closeEditCategoryModal()" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Category Name -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Category Name</label>
                    <input type="text" x-model="editCategoryName" placeholder="Category name"
                           @keydown.enter="updateCategory()"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                </div>

                <!-- Color Picker -->
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 block">Category Color</label>
                    <div class="flex flex-wrap gap-3">
                        <template x-for="color in colorOptions" :key="color.value">
                            <button type="button"
                                    @click="editCategoryColor = color.value"
                                    :class="editCategoryColor === color.value ? 'ring-2 ring-offset-2 ring-blue-600 scale-110' : 'hover:scale-105'"
                                    class="flex flex-col items-center gap-1.5 transition-all">
                                <div :class="color.class + ' w-10 h-10 rounded-full border-2 border-white shadow-md transition-all'"></div>
                                <span class="text-[10px] font-bold text-gray-600 text-center" x-text="color.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
                <button @click="closeEditCategoryModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all">
                    Cancel
                </button>
                <button @click="updateCategory()" :disabled="editCategoryLoading"
                        class="flex-[2] px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-black shadow-lg transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fas" :class="editCategoryLoading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span x-text="editCategoryLoading ? 'Saving...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

</x-ftadmin-layout>
