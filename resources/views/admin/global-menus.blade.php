<x-app-layout>
    <x-slot name="header"></x-slot>

                    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-black text-gray-900 tracking-tight">
                                Global Menus
                            </h1>
                            <p class="text-gray-500 mt-1 font-medium">
                                View and filter menu items from all approved food trucks.
                            </p>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-3">
                        @forelse($approvedRegistrations as $truck)
                            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm cursor-pointer active:bg-gray-50 transition"
                                onclick="openTruckMenusModal({{ $truck->id }})">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="min-w-0 flex-1 mr-3">
                                        <p class="text-sm font-bold text-gray-800 truncate">{{ $truck->foodtruck_name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $truck->business_license_no }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400 flex-shrink-0">#{{ $truck->id }}</span>
                                </div>
                                <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ $truck->foodtruck_desc ?? 'No description provided.' }}</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ $truck->updated_at->format('M d, Y') }}</span>
                                    <span class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">View Menus <i class="fas fa-chevron-right text-[8px] ml-1"></i></span>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 mx-auto text-gray-200">
                                    <i class="fas fa-truck text-3xl"></i>
                                </div>
                                <p class="text-lg font-bold text-gray-500">No Approved Trucks</p>
                                <p class="text-sm text-gray-400">No approved truck records found.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full leading-normal table-fixed">
                                <colgroup>
                                    <col class="w-[10%]">
                                    <col class="w-[24%]">
                                    <col class="w-[20%]">
                                    <col class="w-[30%]">
                                    <col class="w-[16%]">
                                </colgroup>
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                        <th class="px-6 py-4">ID</th>
                                        <th class="px-6 py-4">Truck Name</th>
                                        <th class="px-6 py-4">License No.</th>
                                        <th class="px-6 py-4">Description</th>
                                        <th class="px-6 py-4">Approved On</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-50">
                                    @forelse($approvedRegistrations as $truck)
                                        <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer" onclick="openTruckMenusModal({{ $truck->id }})">
                                            <td class="px-6 py-4 text-xs font-bold text-gray-400">
                                                #{{ $truck->id }}
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-gray-800">
                                                        {{ $truck->foodtruck_name }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                                                {{ $truck->business_license_no }}
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="max-w-xs">
                                                    <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed" title="{{ $truck->foodtruck_desc }}">
                                                        {{ $truck->foodtruck_desc ?? 'No description provided.' }}
                                                    </p>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                                {{ $truck->updated_at->format('M d, Y') }}
                                                <span class="block text-[10px] text-gray-400 uppercase tracking-tighter">
                                                    {{ $truck->updated_at->diffForHumans() }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-20 text-center text-gray-400 bg-white">
                                                <div class="flex flex-col items-center">
                                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                                                        <i class="fas fa-truck text-3xl"></i>
                                                    </div>
                                                    <p class="text-lg font-bold text-gray-500">
                                                        No Approved Trucks
                                                    </p>
                                                    <p class="text-sm text-gray-400">
                                                        No approved truck records found.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($approvedRegistrations->hasPages())
                        <div class="mt-8">
                            {{ $approvedRegistrations->links() }}
                        </div>
                    @endif

                    <script id="global-menus-json-data" type="application/json">{!! json_encode($menusByTruck, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

                    <div
                        id="truckMenusModal"
                        style="display:none;"
                        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[80] flex items-center justify-center p-4"
                        onclick="handleTruckMenusBackdropClick(event)"
                    >
                        <div id="truckMenusPanel" class="bg-white rounded-3xl shadow-2xl w-full max-w-6xl flex flex-col max-h-[90vh] overflow-hidden mx-2 sm:mx-4">
                            <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                                <div>
                                    <h3 id="truckMenusModalTitle" class="text-base font-black text-gray-900">Truck Menus</h3>
                                    <p id="truckMenusModalSubtitle" class="text-xs text-gray-400 font-medium mt-0.5">All created menu items for this truck.</p>
                                </div>
                                <button
                                    type="button"
                                    onclick="closeTruckMenusModal()"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                                <div class="relative flex-shrink-0" id="truckCategoryFilterWrap">
                                    <button type="button" id="truckCategoryFilterBtn" onclick="toggleTruckCategoryDropdown()"
                                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all bg-gray-100 text-gray-500 border border-transparent hover:bg-gray-200">
                                        <i class="fas fa-filter text-xs"></i>
                                        <span id="truckCategoryFilterLabel">Filter</span>
                                        <i id="truckCategoryFilterChevron" class="fas fa-chevron-down text-[10px] transition-transform duration-200"></i>
                                    </button>

                                    <div id="truckCategoryDropdown"
                                         style="display:none;"
                                         class="absolute left-0 top-full mt-1 bg-white rounded-2xl shadow-xl border border-gray-100 py-1.5 w-44 z-[85]">
                                    </div>
                                </div>

                                <span id="truckMenusCount" class="text-xs font-semibold text-gray-400 bg-gray-100 px-3 py-1.5 rounded-full"></span>
                            </div>

                            <div class="flex-1 overflow-y-auto p-3 sm:p-6">
                                <!-- Mobile card view for menus -->
                                <div id="truckMenusCardBody" class="md:hidden space-y-3"></div>
                                <!-- Desktop table view for menus -->
                                <div class="hidden md:block overflow-clip border border-gray-100 rounded-2xl">
                                    <table class="w-full table-fixed">
                                        <thead class="sticky top-0 z-10">
                                            <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                                <th class="py-4 text-left px-6">Menu Name</th>
                                                <th class="py-4 text-left px-6 w-36">Category</th>
                                                <th class="py-4 text-left px-6 w-32">Price</th>
                                                <th class="py-4 text-left px-6 w-36">Qty</th>
                                                <th class="py-4 text-left px-6 w-36">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="truckMenusTableBody" class="divide-y divide-gray-50"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end">
                                <button
                                    type="button"
                                    onclick="closeTruckMenusModal()"
                                    class="px-6 py-2.5 border-2 border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition-all"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>

    @push('css')
        <style>
        </style>
    @endpush

    @push('scripts')
        <script>
            const DEFAULT_MENU_CATEGORIES = ['Foods', 'Drinks', 'Desserts'];
            const globalMenusDataElement = document.getElementById('global-menus-json-data');
            const globalMenusData = globalMenusDataElement ? JSON.parse(globalMenusDataElement.textContent) : {};

            let activeTruckMenusId = null;
            let activeTruckCategory = '';

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function normalizeCategory(category) {
                const raw = String(category ?? '').trim();
                if (!raw) return 'Uncategorized';

                const lower = raw.toLowerCase();
                if (lower === 'food' || lower === 'foods') return 'Foods';
                if (lower === 'drink' || lower === 'drinks') return 'Drinks';
                if (lower === 'dessert' || lower === 'desserts') return 'Desserts';
                if (lower === 'uncategorized') return 'Uncategorized';

                return raw;
            }

            function getActiveTruckData() {
                if (activeTruckMenusId === null) return null;
                return globalMenusData[String(activeTruckMenusId)] || null;
            }

            function getUniqueCaseInsensitive(values) {
                const seen = new Set();
                const result = [];

                values.forEach((value) => {
                    const normalized = String(value ?? '').trim();
                    if (!normalized) return;

                    const key = normalized.toLowerCase();
                    if (seen.has(key)) return;

                    seen.add(key);
                    result.push(normalized);
                });

                return result;
            }

            function openTruckMenusModal(truckId) {
                const truckData = globalMenusData[String(truckId)];
                if (!truckData) return;

                activeTruckMenusId = String(truckId);
                activeTruckCategory = '';

                const title = document.getElementById('truckMenusModalTitle');
                const subtitle = document.getElementById('truckMenusModalSubtitle');
                title.textContent = `${truckData.foodtruck_name || 'Food Truck'} Menus`;
                subtitle.textContent = `All created menu items for ${truckData.foodtruck_name || 'this truck'}.`;

                renderTruckCategoryDropdown();
                renderTruckMenusTable();

                const modal = document.getElementById('truckMenusModal');
                modal.style.display = 'flex';
                document.body.classList.add('overflow-hidden');
            }

            function closeTruckMenusModal() {
                const modal = document.getElementById('truckMenusModal');
                modal.style.display = 'none';
                closeTruckCategoryDropdown();
                activeTruckMenusId = null;
                activeTruckCategory = '';
                document.body.classList.remove('overflow-hidden');
            }

            function handleTruckMenusBackdropClick(event) {
                if (event.target.id === 'truckMenusModal') {
                    closeTruckMenusModal();
                }
            }

            function toggleTruckCategoryDropdown() {
                const dropdown = document.getElementById('truckCategoryDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                renderTruckFilterButtonState();
            }

            function closeTruckCategoryDropdown() {
                const dropdown = document.getElementById('truckCategoryDropdown');
                dropdown.style.display = 'none';
                renderTruckFilterButtonState();
            }

            function setTruckCategory(category) {
                activeTruckCategory = category;
                closeTruckCategoryDropdown();
                renderTruckCategoryDropdown();
                renderTruckMenusTable();
            }

            function renderTruckFilterButtonState() {
                const filterButton = document.getElementById('truckCategoryFilterBtn');
                const filterLabel = document.getElementById('truckCategoryFilterLabel');
                const chevron = document.getElementById('truckCategoryFilterChevron');
                const dropdown = document.getElementById('truckCategoryDropdown');

                filterLabel.textContent = activeTruckCategory || 'Filter';

                if (activeTruckCategory) {
                    filterButton.className = 'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all bg-purple-50 text-purple-600 border border-purple-200';
                } else {
                    filterButton.className = 'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all bg-gray-100 text-gray-500 border border-transparent hover:bg-gray-200';
                }

                if (dropdown.style.display === 'none') {
                    chevron.classList.remove('rotate-180');
                } else {
                    chevron.classList.add('rotate-180');
                }
            }

            function renderTruckCategoryDropdown() {
                const dropdown = document.getElementById('truckCategoryDropdown');
                const truckData = getActiveTruckData();
                if (!dropdown || !truckData) return;

                const menuCategories = (truckData.menus || []).map(menu => normalizeCategory(menu.category));
                const declaredCustom = Array.isArray(truckData.custom_categories)
                    ? truckData.custom_categories.map(category => normalizeCategory(category))
                    : [];

                const customCategories = getUniqueCaseInsensitive([...declaredCustom, ...menuCategories]).filter(category => {
                    return !DEFAULT_MENU_CATEGORIES.includes(category) && category !== 'Uncategorized';
                });

                const optionConfigs = [
                    { value: '', label: 'All', dotClass: 'bg-gray-300', selectedClass: 'bg-gray-50 font-black text-gray-700', defaultClass: 'text-gray-500 hover:bg-gray-50' },
                    { divider: true },
                    { value: 'Foods', label: 'Foods', dotClass: 'bg-purple-500', selectedClass: 'bg-purple-50 font-black text-purple-600', defaultClass: 'text-gray-500 hover:bg-purple-50 hover:text-purple-600' },
                    { value: 'Drinks', label: 'Drinks', dotClass: 'bg-blue-500', selectedClass: 'bg-blue-50 font-black text-blue-600', defaultClass: 'text-gray-500 hover:bg-blue-50 hover:text-blue-600' },
                    { value: 'Desserts', label: 'Desserts', dotClass: 'bg-pink-500', selectedClass: 'bg-pink-50 font-black text-pink-600', defaultClass: 'text-gray-500 hover:bg-pink-50 hover:text-pink-600' },
                ];

                if (customCategories.length > 0) {
                    optionConfigs.push({ divider: true });
                    customCategories.forEach((category) => {
                        optionConfigs.push({
                            value: category,
                            label: category,
                            dotClass: 'bg-gray-500',
                            selectedClass: 'bg-gray-100 font-black text-gray-700',
                            defaultClass: 'text-gray-500 hover:bg-gray-50 hover:text-gray-700',
                        });
                    });
                }

                optionConfigs.push({ divider: true });
                optionConfigs.push({
                    value: 'Uncategorized',
                    label: 'Uncategorized',
                    dotClass: 'bg-gray-400',
                    selectedClass: 'bg-gray-100 font-black text-gray-700',
                    defaultClass: 'text-gray-500 hover:bg-gray-50 hover:text-gray-600',
                });

                let html = '';
                optionConfigs.forEach((option) => {
                    if (option.divider) {
                        html += '<div class="border-t border-gray-50 mx-3 my-0.5"></div>';
                        return;
                    }

                    const isSelected = activeTruckCategory === option.value;
                    const colorClass = isSelected ? option.selectedClass : option.defaultClass;

                    html += `
                        <button type="button" data-category="${escapeHtml(option.value)}"
                                class="truck-category-option w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors ${colorClass}">
                            <span class="w-2 h-2 rounded-full ${option.dotClass} flex-shrink-0"></span>
                            ${escapeHtml(option.label)}
                        </button>
                    `;
                });

                dropdown.innerHTML = html;

                dropdown.querySelectorAll('.truck-category-option').forEach((button) => {
                    button.addEventListener('click', () => {
                        const category = button.getAttribute('data-category') || '';
                        setTruckCategory(category);
                    });
                });

                renderTruckFilterButtonState();
            }

            function renderTruckMenusTable() {
                const tbody = document.getElementById('truckMenusTableBody');
                const cardBody = document.getElementById('truckMenusCardBody');
                const countLabel = document.getElementById('truckMenusCount');
                const truckData = getActiveTruckData();

                if (!tbody || !countLabel || !truckData) return;

                const menus = (truckData.menus || []).map((item) => {
                    return {
                        ...item,
                        normalizedCategory: normalizeCategory(item.category),
                    };
                });

                const filteredMenus = activeTruckCategory
                    ? menus.filter((item) => item.normalizedCategory === activeTruckCategory)
                    : menus;

                countLabel.textContent = `${filteredMenus.length} ${filteredMenus.length === 1 ? 'menu' : 'menus'}`;

                const emptyHtml = `
                    <div class="flex flex-col items-center py-16">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <i class="fas fa-utensils text-xl text-gray-300"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-500">No Menus Found</p>
                        <p class="text-xs text-gray-400 mt-1">No menu items match this category.</p>
                    </div>
                `;

                if (filteredMenus.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-16 text-center text-gray-400 bg-white">${emptyHtml}</td></tr>`;
                    if (cardBody) cardBody.innerHTML = emptyHtml;
                    return;
                }

                // Shared helper for item data
                function getItemDisplay(item) {
                    const isAvailable = String(item.status || '').toLowerCase() === 'available';
                    const quantity = (item.quantity === null || item.quantity === '' || item.quantity === undefined)
                        ? '-'
                        : (Number(item.quantity) > 0 ? `${item.quantity} left` : 'Out of stock');
                    const price = (item.base_price === null || item.base_price === '' || item.base_price === undefined)
                        ? '0.00'
                        : Number(item.base_price).toFixed(2);
                    return { isAvailable, quantity, price };
                }

                // Desktop table
                tbody.innerHTML = filteredMenus.map((item) => {
                    const { isAvailable, quantity, price } = getItemDisplay(item);
                    return `
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900 line-clamp-1">${escapeHtml(item.name)}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-blue-500 uppercase tracking-wider">${escapeHtml(item.normalizedCategory)}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-400 font-medium mr-1">RM</span>
                                <span class="text-sm font-black text-gray-900">${escapeHtml(price)}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-700">${escapeHtml(quantity)}</td>
                            <td class="px-6 py-4">
                                ${isAvailable
                                    ? '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>Available</span>'
                                    : '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-red-100 text-red-600"><span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>Unavailable</span>'
                                }
                            </td>
                        </tr>
                    `;
                }).join('');

                // Mobile cards
                if (cardBody) {
                    cardBody.innerHTML = filteredMenus.map((item) => {
                        const { isAvailable, quantity, price } = getItemDisplay(item);
                        return `
                            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                                <div class="flex items-start justify-between mb-2">
                                    <p class="text-sm font-bold text-gray-800 line-clamp-1 flex-1 mr-2">${escapeHtml(item.name)}</p>
                                    ${isAvailable
                                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-700 flex-shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>Available</span>'
                                        : '<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-100 text-red-600 flex-shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>Unavailable</span>'
                                    }
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-blue-500 uppercase">${escapeHtml(item.normalizedCategory)}</span>
                                        <span class="text-xs text-gray-500 font-bold">${escapeHtml(quantity)}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 mr-0.5">RM</span>
                                        <span class="text-sm font-black text-gray-900">${escapeHtml(price)}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.addEventListener('click', function (event) {
                    const filterWrap = document.getElementById('truckCategoryFilterWrap');
                    if (filterWrap && !filterWrap.contains(event.target)) {
                        closeTruckCategoryDropdown();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        const modal = document.getElementById('truckMenusModal');
                        if (modal && modal.style.display === 'flex') {
                            closeTruckMenusModal();
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
