<x-ftadmin-layout>
<x-slot name="header_title">Dashboard</x-slot>

@php
    $user = Auth::user();
    $role = $user->role;
    $adminFoodTruckId = $user->foodtruck_id;
    $workers = $ftworkers ?? [];
    $menus = $menuItems ?? [];
@endphp

<div x-data="{
        showStaffModal: false,
        showMenuModal: false,
        showCreateForm: false,
        showMenuCreateForm: false,
        searchQuery: '',
        menuSearchQuery: '',
        workers: {{ json_encode($workers) }},
        menuItems: {{ json_encode($menus) }},
        resetForm() {
            if(this.$refs.staffForm) this.$refs.staffForm.reset();
            if(this.$refs.staffDirectoryScroll) this.$refs.staffDirectoryScroll.scrollTop = 0;
            if(this.$refs.registerFormScroll) this.$refs.registerFormScroll.scrollTop = 0;
            this.searchQuery = '';
        },
        resetMenuForm() {
            this.formData = { name: '', category: '', base_price: '', quantity: '', description: '' };
            localStorage.removeItem('ftos_addMenuForm');
            this.croppedDataUrl = null;
            this.imageDataUrl = null;
            if (this.$refs.menuImageInput) this.$refs.menuImageInput.value = '';
            if (this.$refs.menuDirectoryScroll) this.$refs.menuDirectoryScroll.scrollTop = 0;
            if (this.$refs.menuCreateFormScroll) this.$refs.menuCreateFormScroll.scrollTop = 0;
            this.menuSearchQuery = '';
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
        menuMatches(item) {
            if (!this.menuSearchQuery) return true;
            const query = this.menuSearchQuery.toLowerCase();
            return (
                item.name.toLowerCase().includes(query) ||
                item.category.toLowerCase().includes(query)
            );
        },
        get filteredCount() {
            return this.workers.filter(w => this.matches(w)).length;
        },
        get menuFilteredCount() {
            return this.menuItems.filter(i => this.menuMatches(i)).length;
        },
        showMenuEditModal: false,
        selectedMenu: null,
        editName: '',
        editCategory: '',
        editBasePrice: '',
        editQuantity: 0,
        editDescription: '',
        openMenuEdit(item) {
            this.selectedMenu = item;
            this.editName = item.name;
            this.editCategory = item.category;
            this.editBasePrice = item.base_price;
            this.editQuantity = item.quantity;
            this.editDescription = item.description || '';
            // if menu has stored image, preload it for preview and adjuster
            if (item.image) {
                const src = '/storage/' + item.image;
                this.croppedDataUrl = src;
                this.imageDataUrl = src;
            } else {
                this.croppedDataUrl = null;
                this.imageDataUrl = null;
            }
            this.showMenuEditModal = true;
        },
        closeMenuEdit() {
            this.showMenuEditModal = false;
            this.selectedMenu = null;
            this.croppedDataUrl = null;
            if (this.$refs.editMenuImageInput) this.$refs.editMenuImageInput.value = '';
        },

        /* ── Form persistence ── */
        formData: { name: '', category: '', base_price: '', quantity: '', description: '' },

        /* ── Image adjuster ── */
        showImageAdjuster: false,
        imageAdjusterSource: 'add',
        imageDataUrl: null,
        croppedDataUrl: null,
        imageScale: 1,
        imageMinScale: 1,
        imageNaturalW: 0,
        imageNaturalH: 0,
        imageX: 0,
        imageY: 0,
        /* viewport / ratio */
        imageVPW: 320,
        imageVPH: 320,
        /* fixed adjuster container size (px) - keeps modal size stable */
        adjusterContainerSize: 320,
        imageRatio: '', // '' = placeholder, 'square' or '16:9'
        showRatioOptions: false,
        /* empty-preview sizing for add form */
        emptyImageSize: 320,
        updateEmptyImageSize() {
            try {
                const ref = this.$refs.menuRightCol;
                if (ref) {
                    // measure and clamp between min/max
                    const h = ref.clientHeight || 320;
                    this.emptyImageSize = Math.max(140, Math.min(h, 420));
                }
            } catch(e) {}
        },
        lastCrop: null,
        showPreviewActions: false,
        previewActionSource: 'add',
        replacePreviewImage() {
            const ref = this.previewActionSource === 'edit' ? this.$refs.editMenuImageInput : this.$refs.menuImageInput;
            if (ref) ref.click();
            this.showPreviewActions = false;
        },
        isDragging: false,
        _dragStartX: 0, _dragStartY: 0, _dragStartImgX: 0, _dragStartImgY: 0,

        handleImageSelect(event, source) {
            const file = event.target.files[0];
            if (!file) return;
            this.imageAdjusterSource = source || 'add';
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imageDataUrl = e.target.result;
                this.imageX = 0;
                this.imageY = 0;
                const img = new Image();
                img.onload = () => {
                    this.imageNaturalW = img.naturalWidth;
                    this.imageNaturalH = img.naturalHeight;
                    const minScale = Math.max(this.imageVPW / img.naturalWidth, this.imageVPH / img.naturalHeight);
                    this.imageMinScale = minScale;
                    this.imageScale = minScale;
                };
                img.src = e.target.result;
                this.showImageAdjuster = true;
            };
            reader.readAsDataURL(file);
        },
        clampPosition() {
            const vpW = this.imageVPW;
            const vpH = this.imageVPH;
            const hw = (this.imageNaturalW * this.imageScale) / 2;
            const hh = (this.imageNaturalH * this.imageScale) / 2;
            const maxX = Math.max(hw - vpW / 2, 0);
            const maxY = Math.max(hh - vpH / 2, 0);
            this.imageX = Math.min(Math.max(this.imageX, -maxX), maxX);
            this.imageY = Math.min(Math.max(this.imageY, -maxY), maxY);
        },
        startDrag(event) {
            this.isDragging = true;
            const pt = event.touches ? event.touches[0] : event;
            this._dragStartX = pt.clientX; this._dragStartY = pt.clientY;
            this._dragStartImgX = this.imageX; this._dragStartImgY = this.imageY;
        },
        onDrag(event) {
            if (!this.isDragging) return;
            const pt = event.touches ? event.touches[0] : event;
            const scale = this.getViewportScale() || 1;
            this.imageX = this._dragStartImgX + (pt.clientX - this._dragStartX) / scale;
            this.imageY = this._dragStartImgY + (pt.clientY - this._dragStartY) / scale;
            this.clampPosition();
        },
        stopDrag() { this.isDragging = false; },
        zoomIn()  { this.imageScale = Math.min(this.imageScale + 0.1, 4); this.clampPosition(); },
        zoomOut() { this.imageScale = Math.max(this.imageScale - 0.1, this.imageMinScale); this.clampPosition(); },
        resetZoom() {
            // Reset: switch viewport to Square and restore the initial inserted zoom (fit-to-viewport)
            this.imageRatio = 'square';
            this.imageVPW = 320;
            this.imageVPH = 320;
            if (this.imageNaturalW && this.imageNaturalH) {
                // compute minScale for the square viewport (same logic as initial load)
                const minScale = Math.max(this.imageVPW / this.imageNaturalW, this.imageVPH / this.imageNaturalH);
                this.imageMinScale = minScale;
                // reset scale to the minScale so image is fully visible (fit/contain behavior)
                this.imageScale = minScale;
            } else {
                this.imageScale = this.imageMinScale;
            }
            this.imageX = 0;
            this.imageY = 0;
            this.clampPosition();
        },
        confirmCrop() {
            const canvas = this.$refs.cropCanvas;
            const ctx = canvas.getContext('2d');
            const img = new Image();
            img.onload = () => {
                const w = this.imageVPW;
                const h = this.imageVPH;
                canvas.width = w; canvas.height = h;
                ctx.clearRect(0, 0, w, h);
                ctx.save();
                ctx.translate(w / 2 + this.imageX, h / 2 + this.imageY);
                ctx.scale(this.imageScale, this.imageScale);
                ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
                ctx.restore();
                this.croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
                // save last crop parameters so we can reopen adjuster with same framing
                this.lastCrop = {
                    imageScale: this.imageScale,
                    imageX: this.imageX,
                    imageY: this.imageY,
                    imageVPW: this.imageVPW,
                    imageVPH: this.imageVPH
                };
                this.showImageAdjuster = false;
            };
            img.src = this.imageDataUrl;
        },
        cancelImageAdjust() {
            this.imageDataUrl = null;
            this.croppedDataUrl = null;
            this.showImageAdjuster = false;
            if (this.$refs.menuImageInput) this.$refs.menuImageInput.value = '';
            if (this.$refs.editMenuImageInput) this.$refs.editMenuImageInput.value = '';
        },
        selectNewImage() {
            const ref = this.imageAdjusterSource === 'edit'
                ? this.$refs.editMenuImageInput
                : this.$refs.menuImageInput;
            if (ref) ref.click();
        }
        ,
        openImageAdjusterFromData(source) {
            this.showPreviewActions = false;
            this.imageAdjusterSource = source || 'add';
            // prefer the original selected image if available, otherwise fall back to cropped preview
            const src = this.imageDataUrl || this.croppedDataUrl;
            if (!src) return;
            this.imageDataUrl = src;
            this.imageX = 0; this.imageY = 0;
            const img = new Image();
            img.onload = () => {
                this.imageNaturalW = img.naturalWidth;
                this.imageNaturalH = img.naturalHeight;
                // if we have a lastCrop, restore its viewport and transform
                if (this.lastCrop) {
                    this.imageVPW = this.lastCrop.imageVPW || this.imageVPW;
                    this.imageVPH = this.lastCrop.imageVPH || this.imageVPH;
                    this.imageScale = this.lastCrop.imageScale || this.imageScale;
                    this.imageX = this.lastCrop.imageX || this.imageX;
                    this.imageY = this.lastCrop.imageY || this.imageY;
                    this.imageMinScale = Math.max(this.imageVPW / img.naturalWidth, this.imageVPH / img.naturalHeight);
                    this.clampPosition();
                } else {
                    const minScale = Math.max(this.imageVPW / img.naturalWidth, this.imageVPH / img.naturalHeight);
                    this.imageMinScale = minScale;
                    this.imageScale = minScale;
                    this.clampPosition();
                }
            };
            img.src = src;
            this.showImageAdjuster = true;
        }
        ,
        setRatio(r) {
            if (!r) return;
            this.imageRatio = r;
            if (r === '16:9') {
                this.imageVPW = 426;
                this.imageVPH = 240;
            } else if (r === 'square') {
                this.imageVPW = 320;
                this.imageVPH = 320;
            }
            if (this.imageDataUrl) {
                const img = new Image();
                img.onload = () => {
                    const minScale = Math.max(this.imageVPW / img.naturalWidth, this.imageVPH / img.naturalHeight);
                    this.imageMinScale = minScale;
                    this.imageScale = Math.max(this.imageScale, minScale);
                    this.clampPosition();
                };
                img.src = this.imageDataUrl;
            }
            this.showRatioOptions = false;
        }
,
        /* compute scale so the internal crop viewport fits inside fixed container */
        getViewportScale() {
            const cw = this.adjusterContainerSize;
            const ch = this.adjusterContainerSize;
            return Math.min(cw / this.imageVPW, ch / this.imageVPH);
        },

        cropViewportStyle() {
            const s = this.getViewportScale();
            const w = Math.round(this.imageVPW * s);
            const h = Math.round(this.imageVPH * s);
            return `width: ${w}px; height: ${h}px;`;
        },

     }"
     x-init="
         const saved = localStorage.getItem('ftos_addMenuForm');
         if (saved) {
             try {
                 const d = JSON.parse(saved);
                 if (d.name)        formData.name        = d.name;
                 if (d.category)    formData.category    = d.category;
                 if (d.base_price)  formData.base_price  = d.base_price;
                 if (d.quantity)    formData.quantity    = d.quantity;
                 if (d.description) formData.description = d.description;
             } catch(e) {}
         }
         const save = () => localStorage.setItem('ftos_addMenuForm', JSON.stringify(formData));
         $watch('formData.name',        save);
         $watch('formData.category',    save);
         $watch('formData.base_price',  save);
         $watch('formData.quantity',    save);
         $watch('formData.description', save);
         // ensure empty preview sizing is calculated and kept on resize
         updateEmptyImageSize();
         window.addEventListener('resize', updateEmptyImageSize);
     "
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

                    <!-- Menu Items Tab -->
                    <button @click="showMenuModal = true; showMenuCreateForm = false; resetMenuForm()" class="text-left bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-purple-300 hover:shadow-md transition-all group outline-none">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                                <i class="fas fa-utensils text-xl"></i>
                            </div>
                            <i class="fas fa-expand-alt text-gray-300 group-hover:text-purple-500 transition-colors"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Menu Items</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">{{ count($menus) }}</p>
                        <span class="text-[10px] font-bold text-purple-500 mt-2 block opacity-0 group-hover:opacity-100 transition-opacity uppercase tracking-widest">Manage Menu</span>
                    </button>

                    <!-- Active Staff Tab (Modified hover border from blue to orange) -->
                    <button @click="showStaffModal = true; showCreateForm = false; resetForm()" class="text-left bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-orange-300 hover:shadow-md transition-all group outline-none">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <i class="fas fa-expand-alt text-gray-300 group-hover:text-orange-500"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Active Staff</h3>
                        <p class="text-3xl font-black text-gray-900 mt-1">{{ count($workers) }}</p>
                        <span class="text-[10px] font-bold text-orange-500 mt-2 block opacity-0 group-hover:opacity-100 transition-opacity uppercase tracking-widest">Manage Staff</span>
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
                    <div class="flex-1 overflow-y-auto px-8 pb-8" x-ref="staffDirectoryScroll">
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
                     class="flex-1 overflow-y-auto px-8 py-10"
                     x-ref="registerFormScroll">
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

    <!-- MENU MODAL -->
    <div x-show="showMenuModal"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @keydown.escape.window="showMenuEditModal ? closeMenuEdit() : (showMenuModal = false, resetMenuForm())">

        <div @click.away="!showImageAdjuster && (showMenuModal = false, resetMenuForm())"
             class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[85vh] max-h-[750px] border border-white/20">

            <!-- Modal Header (Fixed) -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <div class="bg-purple-600 text-white p-3 rounded-2xl shadow-lg shadow-purple-100">
                        <i class="fas" :class="showMenuCreateForm ? 'fa-plus' : 'fa-utensils'"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight" x-text="showMenuCreateForm ? 'Add New Menu Item' : 'Menu Directory'"></h2>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5" x-text="showMenuCreateForm ? 'Fill in the details below' : 'Manage your menu items'"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-show="showMenuCreateForm" x-cloak @click="resetMenuForm()"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-orange-50 text-gray-400 hover:text-orange-500 text-xs font-black uppercase tracking-wider transition-all">
                        <i class="fas fa-redo-alt text-xs"></i> Refresh
                    </button>
                    <button @click="showMenuModal = false; resetMenuForm()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-hidden flex flex-col">

                <!-- View: Menu Directory -->
                <div x-show="!showMenuCreateForm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="flex-1 flex flex-col overflow-hidden">

                    <!-- Search + Add Button -->
                    <div class="px-8 py-6 flex-shrink-0 flex items-center justify-between">
                        <div class="relative w-72">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" x-model="menuSearchQuery" placeholder="Search name or category..." class="w-full pl-11 pr-4 py-2.5 bg-gray-100 border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-purple-500 transition-all text-sm font-medium outline-none">
                        </div>
                        <button @click="showMenuCreateForm = true; resetMenuForm()" class="inline-flex items-center px-5 py-2.5 bg-slate-900 hover:bg-purple-600 text-white text-sm font-bold rounded-xl shadow-md transition-all active:scale-95 group">
                            <i class="fas fa-plus mr-2.5 text-[10px] group-hover:rotate-90 transition-transform"></i>
                            Add New Menu
                        </button>
                    </div>

                    <!-- Scrollable Table -->
                    <div class="flex-1 overflow-y-auto px-8 pb-8" x-ref="menuDirectoryScroll">
                        <div class="overflow-hidden border border-gray-100 rounded-2xl">
                            <table class="w-full">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                        <th class="py-4 text-left px-6">Menu Name</th>
                                        <th class="py-4 text-left px-6">Category</th>
                                        <th class="py-4 text-left px-6">Price</th>
                                        <th class="py-4 text-right px-6">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <template x-for="item in menuItems" :key="item.id">
                                        <tr x-show="menuMatches(item)" @click="openMenuEdit(item)" class="hover:bg-purple-50/30 transition-colors group cursor-pointer">
                                            <td class="py-5 px-6">
                                                <div class="flex items-center">
                                                    <template x-if="item.image">
                                                        <img :src="'/storage/' + item.image" class="w-10 h-10 rounded-xl object-cover mr-4 shadow-sm group-hover:scale-110 transition-transform flex-shrink-0">
                                                    </template>
                                                    <template x-if="!item.image">
                                                        <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-black text-sm mr-4 shadow-sm group-hover:scale-110 transition-transform flex-shrink-0" x-text="item.name.charAt(0)"></div>
                                                    </template>
                                                    <span class="text-sm font-bold text-gray-800" x-text="item.name"></span>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <span class="text-sm font-medium text-gray-600" x-text="item.category"></span>
                                            </td>
                                            <td class="py-5 px-6">
                                                <span class="text-sm font-bold text-gray-800" x-text="'RM ' + parseFloat(item.base_price).toFixed(2)"></span>
                                            </td>
                                            <td class="py-5 px-6 text-right">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase"
                                                      :class="item.quantity > 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-500 border border-red-100'"
                                                      x-text="item.quantity > 0 ? item.quantity + ' left' : 'Out of Stock'">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="menuItems.length === 0">
                                        <td colspan="4" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-utensils text-2xl text-purple-300"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">No Menu Items Yet</h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">Add your first menu item to get started</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr x-show="menuSearchQuery !== '' && menuFilteredCount === 0 && menuItems.length > 0">
                                        <td colspan="4" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-search text-2xl text-red-300"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">No Results Found</h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">No menu items match your search query</p>
                                                <button @click="menuSearchQuery = ''" class="mt-4 text-[11px] font-black text-purple-600 hover:text-purple-800 uppercase tracking-widest">Clear Search</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- View: Add New Menu Form -->
                <div x-show="showMenuCreateForm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="flex-1 overflow-y-auto px-8 py-10"
                     x-ref="menuCreateFormScroll">
                    <div class="max-w-2xl mx-auto">
                        <form action="{{ route('ftadmin.menu.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            @csrf
                            <input type="hidden" name="foodtruck_id" value="{{ $adminFoodTruckId }}">
                            <input type="hidden" name="image_data" :value="croppedDataUrl">
                            <input type="file" x-ref="menuImageInput" accept="image/jpg,image/jpeg,image/png" class="hidden"
                                   @change="handleImageSelect($event, 'add')">

                            {{-- Row 1: Image (left) | Menu Name + Base Price stacked (right) --}}
                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Image</label>
                                <div @click="!croppedDataUrl ? $refs.menuImageInput.click() : (previewActionSource='add', showPreviewActions = !showPreviewActions)"
                                         x-ref="menuImageContainer"
                                         :style="croppedDataUrl ? '' : ('width: ' + emptyImageSize + 'px; height: ' + emptyImageSize + 'px;')"
                                         class="flex items-center justify-center min-h-[140px] max-h-[420px] border-2 border-dashed rounded-2xl cursor-pointer transition-all overflow-hidden relative"
                                         :class="croppedDataUrl ? 'border-purple-400' : 'border-gray-200 hover:border-purple-400 bg-gray-50 hover:bg-purple-50/30 group'">
                                        <template x-if="croppedDataUrl">
                                            <div class="w-full h-full relative">
                                                <img :src="croppedDataUrl" class="max-w-full max-h-[420px] object-contain" style="pointer-events:none; display:block; margin:0 auto;">
                                                <div x-show="showPreviewActions && previewActionSource === 'add'" x-cloak @click.away="showPreviewActions = false"
                                                     class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/30">
                                                        <button @click.stop="openImageAdjusterFromData('add')"
                                                            class="px-4 py-2 bg-white text-sm font-black rounded-2xl">Click to adjust</button>
                                                    <button @click.stop="(previewActionSource='add', replacePreviewImage())"
                                                            class="px-4 py-2 bg-white text-sm font-black rounded-2xl">Click to replace</button>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!croppedDataUrl">
                                            <div class="flex flex-col items-center py-6">
                                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 group-hover:text-purple-400 transition-colors mb-2"></i>
                                                <span class="text-xs font-bold text-gray-400 group-hover:text-purple-500 transition-colors">Click to upload</span>
                                                <span class="text-[10px] text-gray-300 mt-1">JPG, JPEG, PNG</span>
                                            </div>
                                        </template>
                                    </div>
                            </div>

                            <div x-ref="menuRightCol" class="flex flex-col gap-6">
                                {{-- Menu Name --}}
                                <div class="space-y-2">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Menu Name <span class="text-red-500">*</span></label>
                                    <div class="relative group">
                                        <i class="fas fa-utensils absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                        <input type="text" name="name" required placeholder="Ex: Nasi Lemak Special"
                                               x-model="formData.name"
                                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                    </div>
                                </div>
                                {{-- Base Price --}}
                                <div class="space-y-2">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Base Price (RM) <span class="text-red-500">*</span></label>
                                    <div class="relative group">
                                        <i class="fas fa-dollar-sign absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                        <input type="text" name="base_price" required placeholder="0.00" inputmode="decimal"
                                               x-model="formData.base_price"
                                               @input="formData.base_price = $event.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); $event.target.value = formData.base_price"
                                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                    </div>
                                </div>
                                <div class="flex items-end gap-4 mt-2 w-full">
                                    <div class="space-y-2 flex-1">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Category <span class="text-red-500">*</span></label>
                                        <div class="relative group">
                                            <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                                            <select name="category" required x-model="formData.category"
                                                    class="w-full pl-11 pr-8 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold text-gray-700 appearance-none cursor-pointer">
                                                <option value="" disabled>Select</option>
                                                <option value="Foods">Foods</option>
                                                <option value="Drinks">Drinks</option>
                                                <option value="Desserts">Desserts</option>
                                            </select>
                                            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-xs"></i>
                                        </div>
                                    </div>

                                    <div class="space-y-2 flex-1">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Quantity <span class="text-red-500">*</span></label>
                                        <div class="relative group">
                                            <i class="fas fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                            <input type="text" name="quantity" required placeholder="0" inputmode="numeric"
                                                   x-model="formData.quantity"
                                                   @input="formData.quantity = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = formData.quantity"
                                                   class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            

                            {{-- Row 3: Description (full width) --}}
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Description</label>
                                <textarea name="description" rows="3" placeholder="Describe your menu item..."
                                          x-model="formData.description"
                                          class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300 resize-none"></textarea>
                            </div>

                            {{-- Row 4: Buttons --}}
                            <div class="md:col-span-2 pt-4 flex items-center space-x-4">
                                <button type="button" @click="showMenuCreateForm = false; resetMenuForm()"
                                        class="flex-1 px-8 py-4 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all active:scale-[0.98]">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Back
                                </button>
                                <button type="submit"
                                        class="flex-[2.5] px-8 py-4 bg-slate-900 text-white rounded-2xl text-sm font-black hover:bg-purple-600 shadow-xl shadow-slate-200 hover:shadow-purple-200 transition-all active:scale-[0.98]">
                                    Add Menu Item
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Fixed) -->
            <div class="px-8 py-6 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Authorized Access Only</p>
                <button @click="showMenuModal = false; resetMenuForm()" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition-colors">
                    Close Management Tools
                </button>
            </div>
        </div>
    </div>

    <!-- MENU EDIT MODAL -->
    <div x-show="showMenuEditModal"
         class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        <div @click.away="!showImageAdjuster && closeMenuEdit()"
             class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[85vh] max-h-[750px] border border-white/20">

            <!-- Modal Header -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <div class="bg-purple-600 text-white p-3 rounded-2xl shadow-lg shadow-purple-100">
                        <i class="fas fa-pen"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight">Edit Menu Item</h2>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5">Update the details below</p>
                    </div>
                </div>
                <button @click="closeMenuEdit()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto px-8 py-10">
                <div class="max-w-2xl mx-auto" x-show="selectedMenu">
                    <form :action="'/ftadmin/menu/' + (selectedMenu ? selectedMenu.id : '')" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Row 1: Image (left) | Menu Name + Base Price stacked (right) --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Image</label>
                            <input type="hidden" name="image_data" :value="croppedDataUrl">
                            <input type="file" x-ref="editMenuImageInput" accept="image/jpg,image/jpeg,image/png" class="hidden"
                                   @change="handleImageSelect($event, 'edit')">
                            <div @click="!croppedDataUrl ? $refs.editMenuImageInput.click() : (previewActionSource='edit', showPreviewActions = !showPreviewActions)"
                                  class="flex items-center justify-center min-h-[140px] max-h-[420px] border-2 border-dashed rounded-2xl cursor-pointer transition-all overflow-hidden relative"
                                  :class="croppedDataUrl ? 'border-purple-400' : 'border-gray-200 hover:border-purple-400 bg-gray-50 hover:bg-purple-50/30 group'">
                                <template x-if="croppedDataUrl">
                                    <div class="w-full h-full relative">
                                        <img :src="croppedDataUrl" class="max-w-full max-h-[420px] object-contain" style="pointer-events:none; display:block; margin:0 auto;">
                                        <div x-show="showPreviewActions && previewActionSource === 'edit'" x-cloak @click.away="showPreviewActions = false"
                                             class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/30">
                                                <button @click.stop="openImageAdjusterFromData('edit')"
                                                    class="px-4 py-2 bg-white text-sm font-black rounded-2xl">Click to adjust</button>
                                            <button @click.stop="(previewActionSource='edit', replacePreviewImage())"
                                                    class="px-4 py-2 bg-white text-sm font-black rounded-2xl">Click to replace</button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!croppedDataUrl">
                                    <div class="flex flex-col items-center py-6">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 group-hover:text-purple-400 transition-colors mb-2"></i>
                                        <span class="text-xs font-bold text-gray-400 group-hover:text-purple-500 transition-colors">Click to replace</span>
                                        <span class="text-[10px] text-gray-300 mt-1">JPG, JPEG, PNG</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-col gap-5">
                            {{-- Menu Name --}}
                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Menu Name <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <i class="fas fa-utensils absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                    <input type="text" name="name" required x-model="editName"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>
                            {{-- Base Price --}}
                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Base Price (RM) <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <i class="fas fa-dollar-sign absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                    <input type="text" name="base_price" required placeholder="0.00" inputmode="decimal"
                                           x-model="editBasePrice"
                                           @input="editBasePrice = $event.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); $event.target.value = editBasePrice"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Category short (left) | Quantity + Out of Stock (right) --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Category <span class="text-red-500">*</span></label>
                            <div class="relative group w-44">
                                <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                                <select name="category" required x-model="editCategory"
                                        class="w-full pl-11 pr-8 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold text-gray-700 appearance-none cursor-pointer">
                                    <option value="" disabled>Select</option>
                                    <option value="Foods">Foods</option>
                                    <option value="Drinks">Drinks</option>
                                    <option value="Desserts">Desserts</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Quantity <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-3">
                                <div class="relative group w-36">
                                    <i class="fas fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                    <input type="text" name="quantity" required placeholder="0" inputmode="numeric"
                                           x-model="editQuantity"
                                           @input="editQuantity = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = editQuantity"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                                <button type="button" @click="editQuantity = 0"
                                        class="px-4 py-3.5 bg-red-50 hover:bg-red-500 text-red-500 hover:text-white border border-red-200 hover:border-red-500 rounded-2xl text-xs font-black uppercase tracking-wider transition-all active:scale-95 whitespace-nowrap">
                                    <i class="fas fa-ban mr-1.5"></i> Out of Stock
                                </button>
                            </div>
                        </div>

                        {{-- Row 3: Description (full width) --}}
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Description</label>
                            <textarea name="description" rows="3" x-model="editDescription"
                                      placeholder="Describe your menu item..."
                                      class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300 resize-none"></textarea>
                        </div>

                        {{-- Row 4: Buttons --}}
                        <div class="md:col-span-2 pt-4 flex items-center space-x-4">
                            <button type="button" @click="closeMenuEdit()"
                                    class="flex-1 px-8 py-4 border-2 border-gray-100 rounded-2xl text-sm font-black text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-all active:scale-[0.98]">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back
                            </button>
                            <button type="submit"
                                    class="flex-[2.5] px-8 py-4 bg-slate-900 text-white rounded-2xl text-sm font-black hover:bg-purple-600 shadow-xl shadow-slate-200 hover:shadow-purple-200 transition-all active:scale-[0.98]">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Authorized Access Only</p>
                <button @click="closeMenuEdit()" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition-colors">
                    Close Management Tools
                </button>
            </div>
        </div>
    </div>

    <!-- IMAGE ADJUSTER OVERLAY -->
    <div x-show="showImageAdjuster"
         class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        <div class="bg-white rounded-3xl shadow-2xl flex flex-col items-center p-8 gap-6 w-full max-w-md">

            <!-- Title -->
            <div class="w-full flex items-center justify-between">
                <h3 class="text-lg font-black text-gray-800">Adjust Image</h3>
                <button type="button" @click="cancelImageAdjust()"
                        class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Image Viewport (fixed modal size) -->
            <div class="relative rounded-2xl overflow-hidden bg-gray-100 border-2 border-gray-200 select-none w-80 h-80 flex items-center justify-center"
                 :class="isDragging ? 'cursor-grabbing' : 'cursor-grab'"
                 @mousedown="startDrag($event)" @mousemove="onDrag($event)" @mouseup="stopDrag()" @mouseleave="stopDrag()"
                 @touchstart.prevent="startDrag($event)" @touchmove.prevent="onDrag($event)" @touchend="stopDrag()">

                <!-- inner crop viewport: centered fixed-size box whose aspect changes only inside the modal -->
                 <div class="absolute rounded-lg overflow-hidden bg-transparent border border-white/30 flex items-center justify-center"
                     :style="cropViewportStyle()">
                    <img :src="imageDataUrl"
                         :style="{
                             position: 'absolute',
                             top: '50%',
                             left: '50%',
                             transform: 'translate(calc(-50% + ' + (imageX * getViewportScale()) + 'px), calc(-50% + ' + (imageY * getViewportScale()) + 'px)) scale(' + (imageScale * getViewportScale()) + ')',
                             transformOrigin: 'center',
                             pointerEvents: 'none',
                             userSelect: 'none',
                             maxWidth: 'none'
                         }"
                         alt="">
                    <div class="absolute inset-0 pointer-events-none border border-white/20"></div>
                </div>

                <!-- subtle outer border for modal area -->
                <div class="absolute inset-0 pointer-events-none rounded-2xl"></div>
            </div>

            <!-- Zoom Controls -->
            <div class="flex items-center gap-3 justify-center w-full">
                <button type="button" @click="resetZoom()"
                        class="px-3 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-purple-100 text-gray-500 hover:text-purple-600 text-xs font-black uppercase tracking-wider transition-all">
                    <i class="fas fa-undo-alt mr-1.5 text-[10px]"></i> Reset Zoom
                </button>
                <div class="relative w-44 ml-2">
                    <i class="fas fa-expand absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                    <select x-model="imageRatio" @change="setRatio(imageRatio)"
                            class="w-full pl-11 pr-8 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold text-gray-700 appearance-none cursor-pointer">
                        <option value="" disabled selected>Ratio</option>
                        <option value="square">Square</option>
                        <option value="16:9">16:9</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-xs"></i>
                </div>
                <button type="button" @click="zoomOut()"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-purple-100 text-gray-500 hover:text-purple-600 transition-all">
                    <i class="fas fa-search-minus text-sm"></i>
                </button>
                <span class="text-xs font-black text-gray-400 w-12 text-center" x-text="Math.round((imageScale * getViewportScale()) * 100) + '%'"></span>
                <button type="button" @click="zoomIn()"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-purple-100 text-gray-500 hover:text-purple-600 transition-all">
                    <i class="fas fa-search-plus text-sm"></i>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="w-full flex items-center gap-3">
                <button type="button" @click="selectNewImage()"
                        class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-black rounded-2xl uppercase tracking-wider transition-all active:scale-95">
                    <i class="fas fa-folder-open mr-1.5"></i> Select New Image
                </button>
                <button type="button" @click="cancelImageAdjust()"
                        class="px-4 py-3 border-2 border-gray-200 text-gray-400 hover:text-gray-600 hover:border-gray-300 text-xs font-black rounded-2xl uppercase tracking-wider transition-all active:scale-95">
                    Cancel
                </button>
                <button type="button" @click="confirmCrop()"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white text-xs font-black rounded-2xl uppercase tracking-wider shadow-lg shadow-purple-200 transition-all active:scale-95">
                    Confirm
                </button>
            </div>

            <!-- Hidden canvas for crop rendering -->
            <canvas x-ref="cropCanvas" class="hidden"></canvas>
        </div>
    </div>

</div>


</x-ftadmin-layout>