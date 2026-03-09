<x-ftadmin-layout>
<x-slot name="header_title">Dashboard</x-slot>

@php
    $user = Auth::user();
    $role = $user->role;
    $adminFoodTruckId = $user->foodtruck_id;
    $workers = $ftworkers ?? [];
    $activeWorkersCount = collect($workers)->where('status', 'active')->count();
    $menus = $menuItems ?? [];
@endphp

<script>
function ftadminDashboard() {
    const workers   = @json($workers);
    const menuItems = @json($menus);
    return {
        showStaffModal: false,
        showMenuModal: false,
        showOperationalModal: false,
        isOperational: {{ json_encode($isOperational) }},
        operationalSaving: false,
        async toggleOperational() {
            if (this.operationalSaving) return;
            this.operationalSaving = true;
            try {
                const res = await fetch('/ftadmin/toggle-operational', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (data.success) this.isOperational = data.is_operational;
            } catch(e) { console.error(e); }
            this.operationalSaving = false;
        },
        showCreateForm: false,
        showMenuCreateForm: false,
        searchQuery: '',
        menuSearchQuery: '',
        workers,
        menuItems,
        staffFilter: '',
        showStaffFilter: false,
        menuCategoryFilter: '',
        showMenuFilter: false,
        dashboardCategories: [],
        showCreateCategoryModal: false,
        newCategoryName: '',
        newCategoryColor: 'purple',
        createCategoryLoading: false,
        activeCategoryActionMenu: null,
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
        menuSuccessMessage: '',
        showMenuSuccess: false,
        _menuSuccessTimer: null,
        
        /* ── Truck Profile ── */
        showTruckProfileModal: false,
        truckProfile: {},
        truckName: '',
        businessLicense: '',
        truckDescription: '',
        truckProfileSaving: false,
        truckProfileLoading: false,
        
        async loadTruckProfile() {
            this.truckProfileLoading = true;
            try {
                const res = await fetch('/ftadmin/truck-profile', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) {
                    this.truckProfile = data.truck;
                    this.truckName = data.truck.foodtruck_name || '';
                    this.businessLicense = data.truck.business_license_no || '';
                    this.truckDescription = data.truck.foodtruck_desc || '';
                }
            } catch(e) { console.error(e); }
            this.truckProfileLoading = false;
        },
        
        openTruckProfileModal() {
            this.loadTruckProfile();
            this.showTruckProfileModal = true;
        },
        
        closeTruckProfileModal() {
            this.showTruckProfileModal = false;
            this.truckName = '';
            this.businessLicense = '';
            this.truckDescription = '';
        },
        
        async saveTruckProfile() {
            if (!this.truckName.trim()) {
                alert('Please enter the truck name');
                return;
            }
            if (!this.businessLicense.trim()) {
                alert('Please enter the business license number');
                return;
            }
            
            this.truckProfileSaving = true;
            try {
                const res = await fetch('/ftadmin/truck-profile', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        foodtruck_name: this.truckName,
                        business_license_no: this.businessLicense,
                        foodtruck_desc: this.truckDescription
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Truck profile updated successfully!');
                    this.closeTruckProfileModal();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update truck profile'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to update truck profile. Please try again.');
            }
            this.truckProfileSaving = false;
        },
        
        async submitAddMenuForm() {
            // Validate pricing: either base_price OR all choice prices must be filled
            if (!this.hasValidPricing(this.formData.base_price, this.optionGroups)) {
                alert('Please provide pricing:\n- Fill the Base Price in Section 1, OR\n- Fill the Price for all choices in Section 2');
                return;
            }
            // Set category to "Uncategorized" if not selected
            if (!this.formData.category || this.formData.category.trim() === '') {
                this.formData.category = 'Uncategorized';
            }
            const form = this.$refs.addMenuForm;
            const formData = new FormData(form);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.menuItems.unshift(data.item);
                    this.menuSuccessMessage = 'New Menu: ' + data.item.name + ' has been added into the Menu Directory.';
                    this.showMenuSuccess = true;
                    this.showMenuCreateForm = false;
                    this.resetMenuForm();
                    if (this._menuSuccessTimer) clearTimeout(this._menuSuccessTimer);
                    this._menuSuccessTimer = setTimeout(() => { this.showMenuSuccess = false; }, 5000);
                } else {
                    alert('Error: ' + (data.message || 'Failed to add menu item. Please check your input.'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to add menu item. Please try again.');
            }
        },
        submitEditMenuForm() {
            // Validate pricing: either base_price OR all choice prices must be filled
            if (!this.hasValidPricing(this.editPrice, this.editOptionGroups)) {
                alert('Please provide pricing:\n- Fill the Base Price in Section 1, OR\n- Fill the Price for all choices in Section 2');
                return;
            }
            // Set category to "Uncategorized" if not selected
            if (!this.editCategory || this.editCategory.trim() === '') {
                this.editCategory = 'Uncategorized';
            }
            if (this.$refs.editMenuForm) this.$refs.editMenuForm.submit();
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
        hasMissingChoiceQuantities(groups) {
            return (groups || []).some(group =>
                (group.choices || []).some(choice => {
                    if (!choice.name || choice.name.trim() === '') return false;
                    return choice.quantity === '' || choice.quantity === null || choice.quantity === undefined || isNaN(Number(choice.quantity));
                })
            );
        },
        resetForm() {
            if(this.$refs.staffForm) this.$refs.staffForm.reset();
            if(this.$refs.staffDirectoryScroll) this.$refs.staffDirectoryScroll.scrollTop = 0;
            if(this.$refs.registerFormScroll) this.$refs.registerFormScroll.scrollTop = 0;
            this.searchQuery = '';
            this.staffFilter = '';
            this.showStaffFilter = false;
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
            this.menuCategoryFilter = '';
            this.showMenuFilter = false;
            this.showMenuSuccess = false;
            this.menuSuccessMessage = '';
            this.optionGroups = [];
            this._groupIdCounter = 0;
            this._choiceIdCounter = 0;
            this.addOptionGroup();
        },
        
        /* ── Category Management ── */
        async loadCategories() {
            try {
                const res = await fetch('/ftadmin/menu-category/list', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) this.dashboardCategories = data.categories;
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
            if (this.dashboardCategories.some(c => c.name.toLowerCase() === this.newCategoryName.toLowerCase())) {
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
                    this.dashboardCategories.push(data.category);
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
            
            if (this.dashboardCategories.some(c => c.id !== this.editingCategory.id && c.name.toLowerCase() === this.editCategoryName.toLowerCase())) {
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
                    const idx = this.dashboardCategories.findIndex(c => c.id === this.editingCategory.id);
                    if (idx >= 0) this.dashboardCategories[idx] = data.category;
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
                    this.dashboardCategories = this.dashboardCategories.filter(c => c.id !== category.id);
                    if (this.menuCategoryFilter === category.name) this.menuCategoryFilter = '';
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete category'));
                }
            } catch(e) { 
                console.error(e);
                alert('Error: Failed to delete category. Please try again.');
            }
        },
        
        matches(worker) {
            if (this.staffFilter && worker.status !== this.staffFilter) return false;
            if (!this.searchQuery) return true;
            const query = this.searchQuery.toLowerCase();
            return (
                worker.full_name.toLowerCase().includes(query) ||
                worker.email.toLowerCase().includes(query) ||
                (worker.phone_no && worker.phone_no.includes(this.searchQuery))
            );
        },
        menuMatches(item) {
            if (this.menuCategoryFilter && item.category !== this.menuCategoryFilter) return false;
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
        optionGroups: [],
        _groupIdCounter: 0,
        _choiceIdCounter: 0,
        editOptionGroups: [],
        _editGroupIdCounter: 0,
        _editChoiceIdCounter: 0,
        _dragGi: null,
        _dragArrayKey: null,
        openActionMenu: null,
        actionMenuX: 0,
        actionMenuY: 0,
        actionMenuType: '',
        _newChoice(id) {
            return { _id: id, name: '', price: '', quantity: '', status: 'available', openMenu: false };
        },
        addOptionGroup() {
            this._groupIdCounter++;
            this.optionGroups.push({
                _id: this._groupIdCounter,
                name: '',
                selectionType: 'single',
                choices: [this._newChoice(++this._choiceIdCounter)]
            });
            this.$nextTick(() => {
                const container = document.getElementById('new-option-groups-list');
                if (container) {
                    const last = container.lastElementChild;
                    if (last) last.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },
        removeOptionGroup(gi) {
            this.optionGroups.splice(gi, 1);
        },
        addChoice(gi) {
            this._choiceIdCounter++;
            this.optionGroups[gi].choices.push(this._newChoice(this._choiceIdCounter));
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('.add-choice-name-input');
                if (inputs.length) inputs[inputs.length - 1].focus();
            });
        },
        removeChoice(gi, ci) {
            this.optionGroups[gi].choices.splice(ci, 1);
        },
        addEditOptionGroup() {
            this._editGroupIdCounter++;
            this.editOptionGroups.push({
                _id: this._editGroupIdCounter,
                name: '',
                selectionType: 'single',
                choices: [this._newChoice(++this._editChoiceIdCounter)]
            });
        },
        removeEditOptionGroup(gi) {
            this.editOptionGroups.splice(gi, 1);
        },
        addEditChoice(gi) {
            this._editChoiceIdCounter++;
            this.editOptionGroups[gi].choices.push(this._newChoice(this._editChoiceIdCounter));
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('.edit-choice-name-input');
                if (inputs.length) inputs[inputs.length - 1].focus();
            });
        },
        removeEditChoice(gi, ci) {
            this.editOptionGroups[gi].choices.splice(ci, 1);
        },
        onGroupDragStart(event, gi, arrayKey) {
            this._dragGi = gi;
            this._dragArrayKey = arrayKey;
            event.dataTransfer.effectAllowed = 'move';
        },
        onGroupDragEnter(event, gi, arrayKey) {
            if (this._dragArrayKey !== arrayKey || this._dragGi === null || this._dragGi === gi) return;
            const arr = this[arrayKey];
            const dragged = arr.splice(this._dragGi, 1)[0];
            arr.splice(gi, 0, dragged);
            this._dragGi = gi;
        },
        onGroupDrop(event, gi, arrayKey) {
            event.preventDefault();
            this._dragGi = null;
            this._dragArrayKey = null;
        },
        async submitStaffForm() {
            const form = this.$refs.staffForm;
            const formData = new FormData(form);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.workers.unshift(data.user);
                    this.showCreateForm = false;
                    if (this.$refs.staffForm) this.$refs.staffForm.reset();
                    this.searchQuery = '';
                    this.staffFilter = '';
                }
            } catch(e) { console.error(e); }
        },
        async deactivateStaff(id) {
            const res = await fetch('/ftadmin/staff/' + id + '/deactivate', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                const w = this.workers.find(w => w.id === id);
                if (w) w.status = data.status;
            }
            this.openActionMenu = null;
        },
        async toggleMenuStatus(id) {
            const res = await fetch('/ftadmin/menu/' + id + '/toggle-status', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                const item = this.menuItems.find(i => i.id === id);
                if (item) item.status = data.status;
            }
            this.openActionMenu = null;
        },
        async deleteStaff(id) {
            const res = await fetch('/ftadmin/staff/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                this.workers = this.workers.filter(w => w.id !== id);
            }
            this.openActionMenu = null;
        },
        async fireStaff(id) {
            const res = await fetch('/ftadmin/staff/' + id + '/fire', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                const w = this.workers.find(w => w.id === id);
                if (w) w.status = data.status;
            }
            this.openActionMenu = null;
        },
        editName: '',
        editCategory: '',
        editBasePrice: '',
        editQuantity: 0,
        editDescription: '',
        openMenuEdit(item) {
            this.selectedMenu = item;
            this.editName = item.name;
            this.editCategory = item.category;
            this.editBasePrice = item.base_price !== null && item.base_price !== '' ? item.base_price : '';
            this.editQuantity = item.quantity !== null && item.quantity !== '' ? item.quantity : '';
            this.editDescription = item.description || '';
            // Load option groups from saved data
            this._editGroupIdCounter = 0;
            this._editChoiceIdCounter = 0;
            this.editOptionGroups = (item.option_groups || []).map(group => ({
                _id: ++this._editGroupIdCounter,
                id: group.id,
                name: group.name,
                selectionType: group.selection_type,
                choices: (group.choices || []).map(choice => ({
                    _id: ++this._editChoiceIdCounter,
                    id: choice.id,
                    name: choice.name,
                    price: choice.price,
                    quantity: choice.quantity,
                    status: choice.status ?? 'available',
                    openMenu: false
                }))
            }));
            // ensure preview actions state
            this.previewActionSource = 'edit';
            this.showPreviewActions = false;
            // clear any previous preview then open modal
            this.croppedDataUrl = null;
            this.imageDataUrl = null;
            this.showMenuEditModal = true;
            this.$nextTick(() => {
                if (this.$refs.editMenuBodyScroll) this.$refs.editMenuBodyScroll.scrollTop = 0;
            });
            // assign the image shortly after opening to avoid Alpine x-if timing issues
            setTimeout(() => {
                if (item.image) {
                    const croppedSrc = '/storage/' + item.image;
                    // Use original_image for naturalW/H so Reset Zoom reflects the original file dimensions
                    const originalSrc = item.original_image ? '/storage/' + item.original_image : croppedSrc;

                    // Set croppedDataUrl immediately so the preview shows the saved crop
                    this.croppedDataUrl = croppedSrc;

                    // Load the original image to capture its natural dimensions for Reset Zoom
                    const origImg = new Image();
                    origImg.onload = () => {
                        this.imageNaturalW = origImg.naturalWidth;
                        this.imageNaturalH = origImg.naturalHeight;
                        this.imageVPW = 320; this.imageVPH = 320;
                        const minScale = Math.max(this.imageVPW / origImg.naturalWidth, this.imageVPH / origImg.naturalHeight);
                        this.imageMinScale = minScale;
                        this.imageScale = minScale;
                        this.imageDataUrl = originalSrc;
                    };
                    origImg.onerror = () => {
                        // fallback: use cropped image dimensions
                        const fallback = new Image();
                        fallback.onload = () => {
                            this.imageNaturalW = fallback.naturalWidth;
                            this.imageNaturalH = fallback.naturalHeight;
                            this.imageVPW = 320; this.imageVPH = 320;
                            const minScale = Math.max(this.imageVPW / fallback.naturalWidth, this.imageVPH / fallback.naturalHeight);
                            this.imageMinScale = minScale;
                            this.imageScale = minScale;
                            this.imageDataUrl = croppedSrc;
                        };
                        fallback.onerror = () => {
                            this.imageDataUrl = null;
                            this.croppedDataUrl = null;
                        };
                        fallback.src = croppedSrc;
                    };
                    origImg.src = originalSrc;
                }
            }, 50);
        },
        closeMenuEdit() {
            this.showMenuEditModal = false;
            this.selectedMenu = null;
            this.croppedDataUrl = null;
            this.editOptionGroups = [];
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
            this._prevCroppedDataUrl = this.croppedDataUrl;
            this._prevImageDataUrl   = this.imageDataUrl;
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
            this.croppedDataUrl = this._prevCroppedDataUrl || null;
            this.imageDataUrl   = this._prevImageDataUrl   || this.croppedDataUrl;
            this._prevCroppedDataUrl = null;
            this._prevImageDataUrl   = null;
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
            this._prevCroppedDataUrl = this.croppedDataUrl;
            this._prevImageDataUrl   = this.imageDataUrl;
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
        },

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

        init() {
            // Load custom categories and truck profile
            this.loadCategories();
            this.loadTruckProfile();

            const saved = localStorage.getItem('ftos_addMenuForm');
            if (saved) {
                try {
                    const d = JSON.parse(saved);
                    if (d.name)        this.formData.name        = d.name;
                    if (d.category)    this.formData.category    = d.category;
                    if (d.base_price)  this.formData.base_price  = d.base_price;
                    if (d.quantity)    this.formData.quantity    = d.quantity;
                    if (d.description) this.formData.description = d.description;
                } catch(e) {}
            }
            const save = () => localStorage.setItem('ftos_addMenuForm', JSON.stringify(this.formData));
            this.$watch('formData.name',        save);
            this.$watch('formData.category',    save);
            this.$watch('formData.base_price',  save);
            this.$watch('formData.quantity',    save);
            this.$watch('formData.description', save);
            // ensure empty preview sizing is calculated and kept on resize
            this.updateEmptyImageSize();
            window.addEventListener('resize', () => this.updateEmptyImageSize());
        },
    };
}
</script>

<div x-data="ftadminDashboard()" x-init="init()" class="relative min-h-full flex flex-col">

    <!-- Fixed Top Header -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 flex-shrink-0">
        <div class="flex items-center">
            <button id="openSidebar" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none transition mr-3">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center text-gray-400 space-x-2">
                <span class="w-5 flex justify-center"><i class="fas fa-home text-sm"></i></span>
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

        <div class="p-6 lg:p-8 overflow-y-auto h-full">
            <div class="w-full max-w-[1400px] mx-auto space-y-6">

                <!-- Page heading -->
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Overview</h1>
                    <p class="text-gray-500 mt-1 font-medium">Welcome back, {{ $user->full_name }}</p>
                </div>

                <!-- Top Row: 2 Columns (3fr_2fr ratio) -->
                <div class="grid grid-cols-[3fr_2fr] gap-5 h-[calc((100vh-14rem)/2-5px)]">

                    <!-- Row 1 Col 1 — Total Revenue -->
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-4 w-fit bg-blue-50 text-blue-600 rounded-2xl">
                                    <i class="fas fa-dollar-sign text-2xl"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Total Revenue</h3>
                            <p class="text-5xl font-black text-gray-900">RM 0.00</p>
                        </div>
                        <p class="text-xs text-gray-400 font-medium mt-4">Revenue data will appear once orders are completed.</p>
                    </div>

                    <!-- Row 1 Col 2 — Menu Items -->
                    <button @click="loadCategories(); showMenuModal = true; showMenuCreateForm = false; resetMenuForm()"
                            class="text-left bg-white p-8 rounded-3xl border border-gray-100 shadow-sm hover:border-purple-300 hover:shadow-md transition-all group outline-none flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-4 bg-purple-50 text-purple-600 rounded-2xl group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                                    <i class="fas fa-utensils text-2xl"></i>
                                </div>
                                <i class="fas fa-expand-alt text-gray-300 text-sm group-hover:text-purple-500 transition-colors"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Menu Items</h3>
                            <p class="text-5xl font-black text-gray-900">{{ count($menus) }}</p>
                        </div>
                        <span class="text-xs font-bold text-purple-500 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">Manage Menu</span>
                    </button>

                </div>

                <!-- Bottom Row: 3 Columns (equal width) -->
                <div class="grid grid-cols-3 gap-5 h-[calc((100vh-14rem)/2-5px)]">

                    <!-- Row 2 Col 1 — Truck Operational Status -->
                    <button @click="showOperationalModal = true"
                            class="text-left bg-white p-8 rounded-3xl border shadow-sm hover:shadow-md transition-all group outline-none flex flex-col justify-between"
                            :class="isOperational ? 'border-gray-100 hover:border-emerald-300' : 'border-red-100 hover:border-red-300'">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-4 rounded-2xl transition-all duration-300"
                                     :class="isOperational ? 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white' : 'bg-red-50 text-red-500 group-hover:bg-red-500 group-hover:text-white'">
                                    <i class="fas fa-power-off text-2xl"></i>
                                </div>
                                <i class="fas fa-expand-alt text-gray-300 text-sm transition-colors"
                                   :class="isOperational ? 'group-hover:text-emerald-500' : 'group-hover:text-red-400'"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Truck Operational</h3>
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full flex-shrink-0"
                                      :class="isOperational ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></span>
                                <span class="text-4xl font-black"
                                      :class="isOperational ? 'text-emerald-600' : 'text-red-500'"
                                      x-text="isOperational ? 'Online' : 'Offline'"></span>
                            </div>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity"
                              :class="isOperational ? 'text-emerald-500' : 'text-red-400'">Manage Status</span>
                    </button>

                    <!-- Row 2 Col 2 — Truck Profile -->
                    <button @click="openTruckProfileModal()"
                            class="text-left bg-white p-8 rounded-3xl border border-gray-100 shadow-sm hover:border-teal-300 hover:shadow-md transition-all group outline-none flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-4 bg-teal-50 text-teal-600 rounded-2xl group-hover:bg-teal-600 group-hover:text-white transition-all duration-300">
                                    <i class="fas fa-info-circle text-2xl"></i>
                                </div>
                                <i class="fas fa-expand-alt text-gray-300 text-sm group-hover:text-teal-500 transition-colors"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Truck Profile</h3>
                            <p class="text-lg font-black text-gray-900">Your Truck</p>
                        </div>
                        <span class="text-xs font-bold text-teal-500 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">View Details</span>
                    </button>

                    <!-- Row 2 Col 3 — Active Staff -->
                    <button @click="showStaffModal = true; showCreateForm = false; resetForm()"
                            class="text-left bg-white p-8 rounded-3xl border border-gray-100 shadow-sm hover:border-orange-300 hover:shadow-md transition-all group outline-none flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-4 bg-orange-50 text-orange-600 rounded-2xl group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                    <i class="fas fa-users text-2xl"></i>
                                </div>
                                <i class="fas fa-expand-alt text-gray-300 text-sm group-hover:text-orange-500 transition-colors"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Active Staff</h3>
                            <p class="text-5xl font-black text-gray-900">{{ $activeWorkersCount }}</p>
                        </div>
                        <span class="text-xs font-bold text-orange-500 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">Manage Staff</span>
                    </button>

                </div>
            </div>
        </div>
    </div>

    <!-- OPERATIONAL STATUS MODAL -->
    <div x-show="showOperationalModal"
         style="display:none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="showOperationalModal = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-white/20">

            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-gray-900">Truck Operational Status</h2>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">Control who can see and access your food truck.</p>
                </div>
                <button @click="showOperationalModal = false"
                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Current Status -->
            <div class="px-6 py-5">
                <div class="flex items-center gap-4 p-4 rounded-2xl border"
                     :class="isOperational ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         :class="isOperational ? 'bg-emerald-100' : 'bg-red-100'">
                        <i class="fas fa-power-off text-lg" :class="isOperational ? 'text-emerald-600' : 'text-red-500'"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                  :class="isOperational ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></span>
                            <span class="text-sm font-black" :class="isOperational ? 'text-emerald-700' : 'text-red-600'"
                                  x-text="isOperational ? 'Operational Online' : 'Operational Offline'"></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5"
                           x-text="isOperational ? 'Truck is visible to customers. Staff can log in.' : 'Truck is hidden from customers. Staff are blocked.'"></p>
                    </div>
                </div>

                <!-- Info bullets -->
                <div class="mt-4 space-y-2">
                    <div class="flex items-start gap-3 text-xs text-gray-500">
                        <i class="fas fa-eye mt-0.5 w-4 text-center flex-shrink-0" :class="isOperational ? 'text-emerald-400' : 'text-gray-300'"></i>
                        <span>Customer browse page — <span class="font-bold" :class="isOperational ? 'text-emerald-600' : 'text-red-500'" x-text="isOperational ? 'Truck visible' : 'Truck hidden'"></span></span>
                    </div>
                    <div class="flex items-start gap-3 text-xs text-gray-500">
                        <i class="fas fa-users mt-0.5 w-4 text-center flex-shrink-0" :class="isOperational ? 'text-emerald-400' : 'text-gray-300'"></i>
                        <span>Food Truck Workers — <span class="font-bold" :class="isOperational ? 'text-emerald-600' : 'text-red-500'" x-text="isOperational ? 'Can log in normally' : 'See offline message on login'"></span></span>
                    </div>
                    <div class="flex items-start gap-3 text-xs text-gray-500">
                        <i class="fas fa-user-shield mt-0.5 w-4 text-center flex-shrink-0 text-blue-400"></i>
                        <span>Food Truck Admin — <span class="font-bold text-blue-600">Always has full access</span></span>
                    </div>
                </div>
            </div>

            <!-- Toggle Buttons -->
            <div class="px-6 pb-6 flex gap-3">
                <button @click="if(!isOperational) toggleOperational()"
                        :disabled="isOperational || operationalSaving"
                        :class="isOperational ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-100 cursor-default' : 'bg-gray-100 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600'"
                        class="flex-1 flex flex-col items-center justify-center py-4 px-3 rounded-2xl transition-all disabled:opacity-80 font-black text-sm gap-1">
                    <i class="fas fa-check-circle text-lg"></i>
                    <span>Turn ON</span>
                </button>
                <button @click="if(isOperational) toggleOperational()"
                        :disabled="!isOperational || operationalSaving"
                        :class="!isOperational ? 'bg-red-500 text-white shadow-lg shadow-red-100 cursor-default' : 'bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-500'"
                        class="flex-1 flex flex-col items-center justify-center py-4 px-3 rounded-2xl transition-all disabled:opacity-80 font-black text-sm gap-1">
                    <i class="fas fa-times-circle text-lg"></i>
                    <span>Turn OFF</span>
                </button>
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
                        <!-- Search + Filter grouped together -->
                        <div class="flex items-center gap-2">
                        <div class="relative w-72">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" x-model="searchQuery" placeholder="Search name, email, or phone..." class="w-full pl-11 pr-4 py-2.5 bg-gray-100 border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium outline-none">
                        </div>

                        <!-- Filter Button -->
                        <div class="relative flex-shrink-0">
                            <button type="button" @click.stop="showStaffFilter = !showStaffFilter"
                                    :class="staffFilter === 'active'      ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' :
                                            staffFilter === 'deactivated' ? 'bg-orange-50 text-orange-500 border border-orange-200' :
                                            staffFilter === 'fired'       ? 'bg-red-50 text-red-500 border border-red-200' :
                                                                            'bg-gray-100 text-gray-500 border border-transparent hover:bg-gray-200'"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all">
                                <i class="fas fa-filter text-xs"></i>
                                <span x-text="staffFilter ? staffFilter.charAt(0).toUpperCase() + staffFilter.slice(1) : 'Filter'"></span>
                                <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="showStaffFilter ? 'rotate-180' : ''"></i>
                            </button>

                            <div x-show="showStaffFilter"
                                 @click.away="showStaffFilter = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 style="display:none;"
                                 class="absolute right-0 top-full mt-1 bg-white rounded-2xl shadow-xl border border-gray-100 py-1.5 w-44 z-50">

                                <!-- All (clear filter) -->
                                <button type="button" @click.stop="staffFilter = ''; showStaffFilter = false"
                                        :class="!staffFilter ? 'bg-gray-50 font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50'"
                                        class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                    <span class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0"></span>
                                    All
                                </button>
                                <div class="border-t border-gray-50 mx-3 my-0.5"></div>

                                <!-- Active -->
                                <button type="button" @click.stop="staffFilter = 'active'; showStaffFilter = false"
                                        :class="staffFilter === 'active' ? 'bg-emerald-50 font-black text-emerald-600' : 'text-gray-500 hover:bg-emerald-50 hover:text-emerald-600'"
                                        class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                    Active
                                </button>

                                <!-- Deactivated -->
                                <button type="button" @click.stop="staffFilter = 'deactivated'; showStaffFilter = false"
                                        :class="staffFilter === 'deactivated' ? 'bg-orange-50 font-black text-orange-500' : 'text-gray-500 hover:bg-orange-50 hover:text-orange-500'"
                                        class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                    <span class="w-2 h-2 rounded-full bg-orange-500 flex-shrink-0"></span>
                                    Deactivated
                                </button>

                                <!-- Fired -->
                                <button type="button" @click.stop="staffFilter = 'fired'; showStaffFilter = false"
                                        :class="staffFilter === 'fired' ? 'bg-red-50 font-black text-red-500' : 'text-gray-500 hover:bg-red-50 hover:text-red-500'"
                                        class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                    <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                    Fired
                                </button>
                            </div>
                        </div>
                        </div><!-- end search+filter group -->

                        <button @click="showCreateForm = true; resetForm()" class="inline-flex items-center px-5 py-2.5 bg-slate-900 hover:bg-blue-600 text-white text-sm font-bold rounded-xl shadow-md transition-all active:scale-95 group">
                            <i class="fas fa-plus mr-2.5 text-[10px] group-hover:rotate-90 transition-transform"></i>
                            Add New Staff
                        </button>
                    </div>

                    <!-- Scrollable Table Container -->
                    <div class="flex-1 overflow-y-auto px-8 pb-8" x-ref="staffDirectoryScroll">
                        <div class="overflow-clip border border-gray-100 rounded-2xl">
                            <table class="w-full table-fixed">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                        <th class="py-4 text-left px-6">Staff Name</th>
                                        <th class="py-4 text-left px-6">Contact Details</th>
                                        <th class="py-4 text-left px-6 w-36">Status</th>
                                        <th class="py-4 text-center px-6 w-24">Action</th>
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
                                            <td class="py-5 px-6 w-36 whitespace-nowrap">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase"
                                                      :class="worker.status === 'fired'
                                                          ? 'bg-red-50 text-red-500 border border-red-100'
                                                          : worker.status === 'deactivated'
                                                              ? 'bg-orange-50 text-orange-500 border border-orange-100'
                                                              : 'bg-emerald-50 text-emerald-600 border border-emerald-100'"
                                                      x-text="worker.status === 'fired' ? 'Fired' : worker.status === 'deactivated' ? 'Deactivated' : 'Active'">
                                                </span>
                                            </td>
                                            <td class="py-5 px-6 w-24 text-center">
                                                <button type="button"
                                                        @click.stop="
                                                            if (openActionMenu === worker.id && actionMenuType === 'staff') { openActionMenu = null; return; }
                                                            const rect = $el.getBoundingClientRect();
                                                            actionMenuX = rect.right + 4;
                                                            actionMenuY = rect.top;
                                                            actionMenuType = 'staff';
                                                            openActionMenu = worker.id;
                                                        "
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-400 hover:text-blue-600 transition-all mx-auto">
                                                    <i class="fas fa-ellipsis-v text-xs"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <!-- Empty: no staff at all -->
                                    <tr x-show="workers.length === 0">
                                        <td colspan="4" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-user text-2xl text-blue-300"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">No Users Yet</h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">Add your first staff member to get started</p>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty: filter active, no matches -->
                                    <tr x-show="staffFilter !== '' && filteredCount === 0 && workers.length > 0">
                                        <td colspan="4" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                                     :class="staffFilter === 'active'      ? 'bg-emerald-50' :
                                                             staffFilter === 'deactivated' ? 'bg-orange-50'  : 'bg-red-50'">
                                                    <i class="text-2xl"
                                                       :class="staffFilter === 'active'      ? 'fas fa-user-check text-emerald-300' :
                                                               staffFilter === 'deactivated' ? 'fas fa-user-slash text-orange-300'  :
                                                                                               'fas fa-user-times text-red-300'"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">
                                                    No Staff For
                                                    <span :class="staffFilter === 'active'      ? 'text-emerald-600' :
                                                                   staffFilter === 'deactivated' ? 'text-orange-500'  : 'text-red-500'"
                                                          x-text="staffFilter.charAt(0).toUpperCase() + staffFilter.slice(1)"></span>
                                                    Status
                                                </h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">No staff members match this status</p>
                                                <button @click="staffFilter = ''" class="mt-4 text-[11px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest">Clear Filter</button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty: search query active (no status filter), no matches -->
                                    <tr x-show="staffFilter === '' && searchQuery !== '' && filteredCount === 0 && workers.length > 0">
                                        <td colspan="4" class="py-16 text-center">
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
                        <form x-ref="staffForm" action="{{ route('ftadmin.register.staff') }}" method="POST" @submit.prevent="submitStaffForm()" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
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

    <!-- TRUCK PROFILE MODAL -->
    <div x-show="showTruckProfileModal"
         style="display:none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="closeTruckProfileModal()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-white/20 flex flex-col h-[75vh] max-h-[600px]">

            <!-- Header (Fixed) -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <div class="bg-teal-600 text-white p-3 rounded-2xl shadow-lg shadow-teal-100">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight">Truck Profile</h2>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5">Update your food truck details</p>
                    </div>
                </div>
                <button @click="closeTruckProfileModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-8 py-8">
                
                <div x-show="truckProfileLoading" class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full border-4 border-teal-100 border-t-teal-500 animate-spin mx-auto mb-4"></div>
                        <p class="text-sm font-bold text-gray-400">Loading truck profile...</p>
                    </div>
                </div>

                <div x-show="!truckProfileLoading" class="space-y-6 max-w-xl">
                    <!-- Truck Name -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Truck Name <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <i class="fas fa-truck absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-500 transition-colors"></i>
                            <input type="text" x-model="truckName" placeholder="Enter truck name"
                                   class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                        </div>
                    </div>

                    <!-- Business License -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Business License Number <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <i class="fas fa-certificate absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-teal-500 transition-colors"></i>
                            <input type="text" x-model="businessLicense" placeholder="Enter business license number"
                                   class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Truck Description</label>
                        <div class="relative group">
                            <textarea x-model="truckDescription" placeholder="Enter a description about your food truck"
                                      rows="4"
                                      class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300 resize-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer (Fixed) -->
            <div class="px-8 py-6 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Authorized Access Only</p>
                <div class="flex gap-3">
                    <button @click="closeTruckProfileModal()"
                            class="px-6 py-3 border-2 border-gray-200 rounded-2xl text-sm font-black text-gray-600 hover:border-gray-300 hover:bg-white transition-all active:scale-[0.98]">
                        Cancel
                    </button>
                    <button @click="saveTruckProfile()"
                            :disabled="truckProfileSaving || truckProfileLoading"
                            class="px-6 py-3 bg-teal-600 text-white rounded-2xl text-sm font-black hover:bg-teal-700 shadow-lg shadow-teal-200 hover:shadow-teal-300 transition-all active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!truckProfileSaving">Save Changes</span>
                        <span x-show="truckProfileSaving" class="flex items-center gap-2">
                            <i class="fas fa-spinner animate-spin"></i>
                            Saving...
                        </span>
                    </button>
                </div>
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

        <div @click.away="!showMenuEditModal && !showImageAdjuster && !showEditCategoryModal && !showCreateCategoryModal && (showMenuModal = false, resetMenuForm())"
             class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[85vh] max-h-[750px] border border-white/20">

            <!-- Modal Header (Fixed) -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between gap-4 bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4 flex-shrink-0">
                    <div class="bg-purple-600 text-white p-3 rounded-2xl shadow-lg shadow-purple-100">
                        <i class="fas" :class="showMenuCreateForm ? 'fa-plus' : 'fa-utensils'"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight" x-text="showMenuCreateForm ? 'Add New Menu Item' : 'Menu Directory'"></h2>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5" x-text="showMenuCreateForm ? 'Fill in the details below' : 'Manage your menu items'"></p>
                    </div>
                </div>

                <!-- Success banner (appears in header when a menu is added) -->
                <div x-show="showMenuSuccess && !showMenuCreateForm"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     style="display:none;"
                     class="flex-1 flex items-center gap-2.5 px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-2xl min-w-0">
                    <i class="fas fa-check-circle text-emerald-500 flex-shrink-0"></i>
                    <span class="text-sm font-bold text-emerald-700 truncate" x-text="menuSuccessMessage"></span>
                    <button type="button" @click="showMenuSuccess = false"
                            class="ml-auto flex-shrink-0 text-emerald-400 hover:text-emerald-600 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
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
                        <!-- Search + Filter grouped together -->
                        <div class="flex items-center gap-2">
                            <div class="relative w-72">
                                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" x-model="menuSearchQuery" placeholder="Search name or category..." class="w-full pl-11 pr-4 py-2.5 bg-gray-100 border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-purple-500 transition-all text-sm font-medium outline-none">
                            </div>

                            <!-- Menu Filter Button -->
                            <div class="relative flex-shrink-0">
                                <button type="button" @click.stop="showMenuFilter = !showMenuFilter"
                                        :class="menuCategoryFilter ? 'bg-purple-50 text-purple-600 border border-purple-200' : 'bg-gray-100 text-gray-500 border border-transparent hover:bg-gray-200'"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all">
                                    <i class="fas fa-filter text-xs"></i>
                                    <span x-text="menuCategoryFilter || 'Filter'"></span>
                                    <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="showMenuFilter ? 'rotate-180' : ''"></i>
                                </button>

                                <div x-show="showMenuFilter"
                                     @click.away="!activeCategoryActionMenu && (showMenuFilter = false); activeCategoryActionMenu = null"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     style="display:none;"
                                     class="absolute right-0 top-full mt-1 bg-white rounded-2xl shadow-xl border border-gray-100 py-1.5 w-40 z-50">

                                    <!-- All (clear filter) -->
                                    <button type="button" @click.stop="menuCategoryFilter = ''; showMenuFilter = false"
                                            :class="!menuCategoryFilter ? 'bg-gray-50 font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                        <span class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0"></span>
                                        All
                                    </button>
                                    <div class="border-t border-gray-50 mx-3 my-0.5"></div>

                                    <!-- Foods -->
                                    <button type="button" @click.stop="menuCategoryFilter = 'Foods'; showMenuFilter = false"
                                            :class="menuCategoryFilter === 'Foods' ? 'bg-purple-50 font-black text-purple-600' : 'text-gray-500 hover:bg-purple-50 hover:text-purple-600'"
                                            class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                        <span class="w-2 h-2 rounded-full bg-purple-500 flex-shrink-0"></span>
                                        Foods
                                    </button>

                                    <!-- Drinks -->
                                    <button type="button" @click.stop="menuCategoryFilter = 'Drinks'; showMenuFilter = false"
                                            :class="menuCategoryFilter === 'Drinks' ? 'bg-blue-50 font-black text-blue-600' : 'text-gray-500 hover:bg-blue-50 hover:text-blue-600'"
                                            class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        Drinks
                                    </button>

                                    <!-- Desserts -->
                                    <button type="button" @click.stop="menuCategoryFilter = 'Desserts'; showMenuFilter = false"
                                            :class="menuCategoryFilter === 'Desserts' ? 'bg-pink-50 font-black text-pink-600' : 'text-gray-500 hover:bg-pink-50 hover:text-pink-600'"
                                            class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                        <span class="w-2 h-2 rounded-full bg-pink-500 flex-shrink-0"></span>
                                        Desserts
                                    </button>

                                    <!-- Divider (show only if custom categories exist) -->
                                    <div x-show="dashboardCategories.filter(c => c.name !== 'Uncategorized').length > 0" class="border-t border-gray-50 mx-3 my-0.5"></div>

                                    <!-- Custom Categories (with action buttons) - Exclude "Uncategorized" since it's a default category -->
                                    <template x-for="cat in dashboardCategories.filter(c => c.name !== 'Uncategorized')" :key="cat.id">
                                        <div class="relative flex items-center">
                                            <!-- Category filter button -->
                                            <button type="button" @click.stop="menuCategoryFilter = cat.name; showMenuFilter = false"
                                                    :class="menuCategoryFilter === cat.name ? 'font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50'"
                                                    class="flex-1 text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors"
                                                    :style="menuCategoryFilter === cat.name ? `background-color: ${getBgColorClass(cat.color)}; color: ${getTextColorClass(cat.color)};` : ''">
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
                                                     @click.stop="$event"
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

                                    <!-- Uncategorized (default category - NO action button, placed at bottom) -->
                                    <button type="button" @click.stop="menuCategoryFilter = 'Uncategorized'; showMenuFilter = false"
                                            :class="menuCategoryFilter === 'Uncategorized' ? 'bg-gray-100 font-black text-gray-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-600'"
                                            class="w-full text-left px-4 py-2 text-xs font-bold flex items-center gap-2.5 transition-colors">
                                        <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                                        Uncategorized
                                    </button>
                                </div>
                            </div>

                            <!-- +New Category Button (moved here, next to filter) -->
                            <button type="button" @click="openCreateCategoryModal()"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-blue-600 text-white hover:bg-blue-700 transition-all shadow-sm">
                                <i class="fas fa-plus text-xs"></i>
                                <span>New Category</span>
                            </button>
                        </div><!-- end search+filter group -->

                        <button @click="showMenuCreateForm = true; resetMenuForm()" class="inline-flex items-center px-5 py-2.5 bg-slate-900 hover:bg-purple-600 text-white text-sm font-bold rounded-xl shadow-md transition-all active:scale-95 group">
                            <i class="fas fa-plus mr-2.5 text-[10px] group-hover:rotate-90 transition-transform"></i>
                            Add New Menu
                        </button>
                    </div>

                    <!-- Scrollable Table -->
                    <div class="flex-1 overflow-y-auto px-8 pb-8" x-ref="menuDirectoryScroll">
                        <div class="overflow-clip border border-gray-100 rounded-2xl">
                            <table class="w-full table-fixed">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                        <th class="py-4 text-left px-6">Menu Name</th>
                                        <th class="py-4 text-left px-6 w-32">Category</th>
                                        <th class="py-4 text-left px-6 w-32">Price</th>
                                        <th class="py-4 text-left px-6 w-32">Qty</th>
                                        <th class="py-4 text-left px-6 w-36">Status</th>
                                        <th class="py-4 text-center px-6 w-20">Action</th>
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
                                                <span class="text-sm font-bold text-gray-800" x-text="'RM ' + (item.base_price !== null && item.base_price !== '' ? parseFloat(item.base_price).toFixed(2) : '0.00')"></span>
                                            </td>
                                            <td class="py-5 px-6">
                                                <span class="text-sm font-medium text-gray-600"
                                                      x-text="item.quantity === null || item.quantity === '' || item.quantity === undefined ? '-' : (item.quantity > 0 ? item.quantity + ' left' : 'Out of Stock')">
                                                </span>
                                            </td>
                                            <td class="py-5 px-6 w-36 whitespace-nowrap">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase"
                                                      :class="item.status === 'unavailable'
                                                          ? 'bg-orange-50 text-orange-500 border border-orange-100'
                                                          : 'bg-emerald-50 text-emerald-600 border border-emerald-100'"
                                                      x-text="item.status === 'unavailable' ? 'Unavailable' : 'Available'">
                                                </span>
                                            </td>
                                            <td class="py-5 px-6 w-20 text-center" @click.stop>
                                                <button type="button"
                                                        @click.stop="
                                                            if (openActionMenu === item.id && actionMenuType === 'menu') { openActionMenu = null; return; }
                                                            const rect = $el.getBoundingClientRect();
                                                            actionMenuX = rect.right + 4;
                                                            actionMenuY = rect.top;
                                                            actionMenuType = 'menu';
                                                            openActionMenu = item.id;
                                                        "
                                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-purple-100 text-gray-400 hover:text-purple-600 transition-all mx-auto">
                                                    <i class="fas fa-ellipsis-v text-xs"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <!-- Empty: no items at all -->
                                    <tr x-show="menuItems.length === 0">
                                        <td colspan="6" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-utensils text-2xl text-purple-300"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">No Menu Items Yet</h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">Add your first menu item to get started</p>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty: category filter active, no matches -->
                                    <tr x-show="menuCategoryFilter !== '' && menuFilteredCount === 0 && menuItems.length > 0">
                                        <td colspan="6" class="py-16 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                                     :class="menuCategoryFilter === 'Foods'    ? 'bg-purple-50' :
                                                             menuCategoryFilter === 'Drinks'   ? 'bg-blue-50'   : 'bg-pink-50'">
                                                    <i class="text-2xl"
                                                       :class="menuCategoryFilter === 'Foods'    ? 'fas fa-utensils text-purple-300' :
                                                               menuCategoryFilter === 'Drinks'   ? 'fas fa-mug-hot text-blue-300'    :
                                                                                                   'fas fa-birthday-cake text-pink-300'"></i>
                                                </div>
                                                <h3 class="text-base font-black text-gray-800">
                                                    No Menu Items For
                                                    <span :class="menuCategoryFilter === 'Foods'  ? 'text-purple-600' :
                                                                   menuCategoryFilter === 'Drinks' ? 'text-blue-600'   : 'text-pink-600'"
                                                          x-text="menuCategoryFilter"></span>
                                                    Category
                                                </h3>
                                                <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">Add your first menu item to get started</p>
                                                <button @click="menuCategoryFilter = ''" class="mt-4 text-[11px] font-black text-purple-600 hover:text-purple-800 uppercase tracking-widest">Clear Filter</button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty: search query active (no category filter), no matches -->
                                    <tr x-show="menuCategoryFilter === '' && menuSearchQuery !== '' && menuFilteredCount === 0 && menuItems.length > 0">
                                        <td colspan="6" class="py-16 text-center">
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
                        <form x-ref="addMenuForm" action="{{ route('ftadmin.menu.store') }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitAddMenuForm()" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            @csrf
                            <input type="hidden" name="foodtruck_id" value="{{ $adminFoodTruckId }}">
                            <input type="hidden" name="image_data" :value="croppedDataUrl">
                            <input type="hidden" name="original_image_data" :value="imageDataUrl">
                            <input type="hidden" name="option_groups" :value="JSON.stringify(optionGroups)">
                            <input type="file" x-ref="menuImageInput" accept="image/jpg,image/jpeg,image/png" class="hidden"
                                   @change="handleImageSelect($event, 'add')">

                            {{-- Section 1 Title --}}
                            <div class="md:col-span-2 mb-2">
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-700 border-b border-gray-100 pb-3">1. Menu Details</h3>
                            </div>

                            {{-- Row 1: Image (left) | Menu Name + Base Price stacked (right) --}}
                            <div class="space-y-2">
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Image</label>
                                <div @click="!croppedDataUrl ? $refs.menuImageInput.click() : (previewActionSource='add', showPreviewActions = !showPreviewActions)"
                                         @click.away="previewActionSource === 'add' && (showPreviewActions = false)"
                                         x-ref="menuImageContainer"
                                         :style="croppedDataUrl ? '' : ('width: ' + emptyImageSize + 'px; height: ' + emptyImageSize + 'px;')"
                                         class="flex items-center justify-center min-h-[140px] max-h-[420px] border-2 border-dashed rounded-2xl cursor-pointer transition-all overflow-hidden relative"
                                         :class="croppedDataUrl ? 'border-purple-400' : 'border-gray-200 hover:border-purple-400 bg-gray-50 hover:bg-purple-50/30 group'">
                                        <template x-if="croppedDataUrl">
                                            <div class="w-full h-full relative">
                                                <img :src="croppedDataUrl" class="max-w-full max-h-[420px] object-contain" style="pointer-events:none; display:block; margin:0 auto;">
                                                <div x-show="showPreviewActions && previewActionSource === 'add'"
                                                     class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/30">
                                                    <button type="button" @click.stop="openImageAdjusterFromData('add')"
                                                            class="px-5 py-2.5 bg-white hover:bg-purple-50 text-gray-800 text-sm font-black rounded-2xl shadow-lg transition-all active:scale-95">
                                                        <i class="fas fa-sliders-h mr-2 text-purple-500"></i>Click to Adjust
                                                    </button>
                                                    <button type="button" @click.stop="replacePreviewImage()"
                                                            class="px-5 py-2.5 bg-white hover:bg-purple-50 text-gray-800 text-sm font-black rounded-2xl shadow-lg transition-all active:scale-95">
                                                        <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Click to Replace
                                                    </button>
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
                                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Base Price (RM) <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                                    <div class="relative group">
                                        <i class="fas fa-dollar-sign absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                        <input type="text" name="base_price" placeholder="Optional" inputmode="decimal"
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
                                            <select name="category" x-model="formData.category"
                                                    class="w-full pl-11 pr-8 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold text-gray-700 appearance-none cursor-pointer">
                                                <option value="">Select Category</option>
                                                <!-- Default categories first -->
                                                <option value="Foods">Foods</option>
                                                <option value="Drinks">Drinks</option>
                                                <option value="Desserts">Desserts</option>
                                                <!-- Custom categories -->
                                                <template x-for="cat in dashboardCategories.filter(c => !['Foods', 'Drinks', 'Desserts', 'Uncategorized'].includes(c.name))" :key="cat.id">
                                                    <option :value="cat.name" x-text="cat.name"></option>
                                                </template>
                                            </select>
                                            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-xs"></i>
                                        </div>
                                    </div>

                                    <div class="space-y-2 flex-1">
                                        <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Quantity <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                                        <div class="relative group">
                                            <i class="fas fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                            <input type="text" name="quantity" placeholder="Optional" inputmode="numeric"
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

                            {{-- Section 2: Choices / Options --}}
                            <div class="md:col-span-2 pt-2">
                                <div class="flex items-center justify-between mb-5">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-700">2. Choices / Options</h3>
                                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">Optional — add customizable options for this menu item</p>
                                    </div>
                                    <button type="button" @click="addOptionGroup()"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-600 text-xs font-black rounded-xl border border-purple-200 transition-all">
                                        <i class="fas fa-plus text-[10px]"></i>
                                        Add Option Group
                                    </button>
                                </div>

                                <div id="new-option-groups-list" class="space-y-4">
                                    <template x-for="(group, gi) in optionGroups" :key="group._id">
                                        <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/30 transition-opacity"
                                             draggable="true"
                                             @dragstart="onGroupDragStart($event, gi, 'optionGroups')"
                                             @dragover.prevent
                                             @dragenter.prevent="onGroupDragEnter($event, gi, 'optionGroups')"
                                             @drop="onGroupDrop($event, gi, 'optionGroups')"
                                             :class="{'opacity-40': _dragGi === gi && _dragArrayKey === 'optionGroups'}">
                                            <!-- Group header: name + selection type + remove -->
                                            <div class="flex items-end gap-3 mb-4">
                                                <div class="flex items-center self-end mb-1 cursor-grab active:cursor-grabbing flex-shrink-0" title="Drag to reorder">
                                                    <i class="fas fa-grip-lines text-gray-300 hover:text-purple-400 text-base transition-colors"></i>
                                                </div>
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
                                                                class="px-5 py-2.5 text-xs font-black uppercase tracking-wider transition-all">Single</button>
                                                        <div class="w-px bg-gray-200 flex-shrink-0"></div>
                                                        <button type="button" @click="group.selectionType = 'multiple'"
                                                                :class="group.selectionType === 'multiple' ? 'bg-purple-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                                                                class="px-5 py-2.5 text-xs font-black uppercase tracking-wider transition-all">Multiple</button>
                                                    </div>
                                                </div>
                                                <button type="button" @click="removeOptionGroup(gi)"
                                                        class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all flex-shrink-0 mb-0.5">
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

                                            <!-- Choice rows -->
                                            <div class="space-y-2">
                                                <template x-for="(choice, ci) in group.choices" :key="choice._id">
                                                    <div class="grid grid-cols-12 gap-2 items-center">
                                                        <div class="col-span-4">
                                                            <input type="text" x-model="choice.name" placeholder="Choice name"
                                                                   class="add-choice-name-input w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                                                        </div>
                                                        <div class="col-span-2 relative">
                                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400 pointer-events-none">RM</span>
                                                            <input type="text" x-model="choice.price" placeholder="0.00" inputmode="decimal"
                                                                   @input="choice.price = $event.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); $event.target.value = choice.price"
                                                                   class="w-full pl-7 pr-1 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                                                        </div>
                                                        <div class="col-span-2">
                                                                <input type="text" x-model="choice.quantity" placeholder="Qty" inputmode="numeric"
                                                                    @input="choice.quantity = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = choice.quantity; if (choice.quantity === '0') choice.status = 'unavailable'"
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
                                                                <div x-show="choice.openMenu"
                                                                     @click.away="choice.openMenu = false"
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
                                                            <button type="button" @click="removeChoice(gi, ci)"
                                                                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all">
                                                                <i class="fas fa-times text-xs"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Add Choice -->
                                            <button type="button" @click="addChoice(gi)"
                                                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-purple-500 hover:text-purple-700 transition-colors">
                                                <i class="fas fa-plus text-[10px]"></i>
                                                Add Choice
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Empty state -->
                                    <div x-show="optionGroups.length === 0"
                                         class="border-2 border-dashed border-gray-100 rounded-2xl py-6 text-center">
                                        <p class="text-[11px] font-bold text-gray-300 uppercase tracking-wider">No option groups yet — click "Add Option Group" to create one</p>
                                    </div>
                                </div>
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
            <div class="flex-1 overflow-y-auto px-8 py-10" x-ref="editMenuBodyScroll">
                <div class="max-w-2xl mx-auto" x-show="selectedMenu">
                    <form x-ref="editMenuForm" :action="'/ftadmin/menu/' + (selectedMenu ? selectedMenu.id : '')" method="POST" enctype="multipart/form-data" @submit.prevent="submitEditMenuForm()" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Section 1 Title --}}
                        <div class="md:col-span-2 mb-2">
                            <h3 class="text-xs font-black uppercase tracking-widest text-gray-700 border-b border-gray-100 pb-3">1. Menu Details</h3>
                        </div>

                        {{-- Row 1: Image (left) | Menu Name + Base Price stacked (right) --}}
                        <div class="space-y-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Image</label>
                            <input type="hidden" name="image_data" :value="croppedDataUrl">
                            <input type="hidden" name="original_image_data" :value="imageDataUrl">
                            <input type="hidden" name="option_groups" :value="JSON.stringify(editOptionGroups)">
                            <input type="file" x-ref="editMenuImageInput" accept="image/jpg,image/jpeg,image/png" class="hidden"
                                   @change="handleImageSelect($event, 'edit')">
                            <div @click="!croppedDataUrl ? $refs.editMenuImageInput.click() : (previewActionSource='edit', showPreviewActions = !showPreviewActions)"
                                 @click.away="previewActionSource === 'edit' && (showPreviewActions = false)"
                                 class="flex items-center justify-center min-h-[140px] max-h-[420px] border-2 border-dashed rounded-2xl cursor-pointer transition-all overflow-hidden relative"
                                 :class="croppedDataUrl ? 'border-purple-400' : 'border-gray-200 hover:border-purple-400 bg-gray-50 hover:bg-purple-50/30 group'">
                                <template x-if="croppedDataUrl">
                                    <div class="w-full h-full relative">
                                        <img :src="croppedDataUrl" class="max-w-full max-h-[420px] object-contain" style="pointer-events:none; display:block; margin:0 auto;">
                                        <div x-show="showPreviewActions && previewActionSource === 'edit'"
                                             class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/30">
                                            <button type="button" @click.stop="openImageAdjusterFromData('edit')"
                                                    class="px-5 py-2.5 bg-white hover:bg-purple-50 text-gray-800 text-sm font-black rounded-2xl shadow-lg transition-all active:scale-95">
                                                <i class="fas fa-sliders-h mr-2 text-purple-500"></i>Click to Adjust
                                            </button>
                                            <button type="button" @click.stop="replacePreviewImage()"
                                                    class="px-5 py-2.5 bg-white hover:bg-purple-50 text-gray-800 text-sm font-black rounded-2xl shadow-lg transition-all active:scale-95">
                                                <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Click to Replace
                                            </button>
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

                        <div x-ref="menuRightColEdit" class="flex flex-col gap-6">
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
                                <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Base Price (RM) <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                                <div class="relative group">
                                    <i class="fas fa-dollar-sign absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                    <input type="text" name="base_price" placeholder="Optional" inputmode="decimal"
                                           x-model="editBasePrice"
                                           @input="editBasePrice = $event.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); $event.target.value = editBasePrice"
                                           class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                </div>
                            </div>

                            <div class="flex items-end gap-4 mt-2 w-full">
                                <div class="space-y-2 flex-1">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Category <span class="text-red-500">*</span></label>
                                    <div class="relative group">
                                        <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                                        <select name="category" x-model="editCategory"
                                                class="w-full pl-11 pr-8 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold text-gray-700 appearance-none cursor-pointer">
                                            <option value="">Select Category</option>
                                            <!-- Default categories first -->
                                            <option value="Foods">Foods</option>
                                            <option value="Drinks">Drinks</option>
                                            <option value="Desserts">Desserts</option>
                                            <!-- Custom categories -->
                                            <template x-for="cat in dashboardCategories.filter(c => !['Foods', 'Drinks', 'Desserts', 'Uncategorized'].includes(c.name))" :key="cat.id">
                                                <option :value="cat.name" x-text="cat.name"></option>
                                            </template>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none text-xs"></i>
                                    </div>
                                </div>

                                <div class="space-y-2 flex-1">
                                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Quantity <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                                    <div class="relative group">
                                        <i class="fas fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-purple-500 transition-colors"></i>
                                        <input type="text" name="quantity" placeholder="Optional" inputmode="numeric"
                                               x-model="editQuantity"
                                               @input="editQuantity = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = editQuantity"
                                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                                    </div>
                                </div>
                            </div>

                            {{-- Out of Stock button --}}
                            <button type="button" @click="editQuantity = '0'"
                                    :class="editQuantity == 0 && editQuantity !== ''
                                        ? 'bg-red-500 text-white border-red-500 shadow-lg shadow-red-100'
                                        : 'bg-red-50 text-red-500 border-red-200 hover:bg-red-500 hover:text-white hover:border-red-500'"
                                    class="w-full mt-1 py-3 border-2 rounded-2xl text-xs font-black uppercase tracking-wider transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                                <i class="fas fa-ban"></i>
                                <span x-text="editQuantity == 0 && editQuantity !== '' ? 'Out of Stock' : 'Set Out of Stock'"></span>
                            </button>
                        </div>

                        {{-- Row 3: Description (full width) --}}
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Description</label>
                            <textarea name="description" rows="3" x-model="editDescription"
                                      placeholder="Describe your menu item..."
                                      class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300 resize-none"></textarea>
                        </div>

                        {{-- Section 2: Choices / Options --}}
                        <div class="md:col-span-2 pt-2">
                            <div class="flex items-center justify-between mb-5">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-700">2. Choices / Options</h3>
                                    <p class="text-[10px] text-gray-400 font-medium mt-0.5">Optional — add customizable options for this menu item</p>
                                </div>
                                <button type="button" @click="addEditOptionGroup()"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-600 text-xs font-black rounded-xl border border-purple-200 transition-all">
                                    <i class="fas fa-plus text-[10px]"></i>
                                    Add Option Group
                                </button>
                            </div>

                            <div class="space-y-4">
                                <template x-for="(group, gi) in editOptionGroups" :key="group._id">
                                    <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50/30 transition-opacity"
                                         draggable="true"
                                         @dragstart="onGroupDragStart($event, gi, 'editOptionGroups')"
                                         @dragover.prevent
                                         @dragenter.prevent="onGroupDragEnter($event, gi, 'editOptionGroups')"
                                         @drop="onGroupDrop($event, gi, 'editOptionGroups')"
                                         :class="{'opacity-40': _dragGi === gi && _dragArrayKey === 'editOptionGroups'}">
                                        <div class="flex items-end gap-3 mb-4">
                                            <div class="flex items-center self-end mb-1 cursor-grab active:cursor-grabbing flex-shrink-0" title="Drag to reorder">
                                                <i class="fas fa-grip-lines text-gray-300 hover:text-purple-400 text-base transition-colors"></i>
                                            </div>
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
                                                            class="px-5 py-2.5 text-xs font-black uppercase tracking-wider transition-all">Single</button>
                                                    <div class="w-px bg-gray-200 flex-shrink-0"></div>
                                                    <button type="button" @click="group.selectionType = 'multiple'"
                                                            :class="group.selectionType === 'multiple' ? 'bg-purple-600 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                                                            class="px-5 py-2.5 text-xs font-black uppercase tracking-wider transition-all">Multiple</button>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeEditOptionGroup(gi)"
                                                    class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all flex-shrink-0 mb-0.5">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-12 gap-2 px-1 mb-2">
                                            <div class="col-span-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Choice Name</div>
                                            <div class="col-span-2 text-[10px] font-black uppercase tracking-widest text-purple-400">Price</div>
                                            <div class="col-span-2 text-[10px] font-black uppercase tracking-widest text-purple-400">Qty</div>
                                            <div class="col-span-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</div>
                                            <div class="col-span-1"></div>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(choice, ci) in group.choices" :key="choice._id">
                                                <div class="grid grid-cols-12 gap-2 items-center">
                                                    <div class="col-span-4">
                                                        <input type="text" x-model="choice.name" placeholder="Choice name"
                                                               class="edit-choice-name-input w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                                                    </div>
                                                    <div class="col-span-2 relative">
                                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400 pointer-events-none">RM</span>
                                                        <input type="text" x-model="choice.price" placeholder="0.00" inputmode="decimal"
                                                               @input="choice.price = $event.target.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); $event.target.value = choice.price"
                                                               class="w-full pl-7 pr-1 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium placeholder:text-gray-300 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all">
                                                    </div>
                                                    <div class="col-span-2">
                                                        <input type="text" x-model="choice.quantity" placeholder="Qty" inputmode="numeric"
                                                            @input="choice.quantity = $event.target.value.replace(/[^0-9]/g, ''); $event.target.value = choice.quantity; if (choice.quantity === '0') choice.status = 'unavailable'"
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
                                                            <div x-show="choice.openMenu"
                                                                 @click.away="choice.openMenu = false"
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

                                <div x-show="editOptionGroups.length === 0"
                                     class="border-2 border-dashed border-gray-100 rounded-2xl py-6 text-center">
                                    <p class="text-[11px] font-bold text-gray-300 uppercase tracking-wider">No option groups yet — click "Add Option Group" to create one</p>
                                </div>
                            </div>
                        </div>

                        {{-- Row 4: Buttons --}}
                        <div class="md:col-span-2 pt-4 flex items-center space-x-4">
                            <button type="button" @click.stop="closeMenuEdit(); loadCategories(); showMenuModal = true;"
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

    <!-- ACTION MENU DROPDOWN (fixed portal — not clipped by any overflow-hidden parent) -->
    <div x-show="openActionMenu !== null"
         @click.away="openActionMenu = null"
         :style="{ position: 'fixed', left: actionMenuX + 'px', top: actionMenuY + 'px', zIndex: 300 }"
         style="display:none;"
         class="bg-white rounded-xl shadow-xl border border-gray-100 py-1 w-44">

        <!-- ── MENU item actions ── -->
        <template x-if="actionMenuType === 'menu'">
            <div>
                <button type="button"
                        @click.stop="toggleMenuStatus(openActionMenu)"
                        :class="menuItems.find(i => i.id === openActionMenu)?.status === 'unavailable'
                            ? 'text-emerald-600 hover:bg-emerald-50'
                            : 'text-orange-500 hover:bg-orange-50'"
                        class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-2.5 transition-colors">
                    <i class="fas w-3 text-center"
                       :class="menuItems.find(i => i.id === openActionMenu)?.status === 'unavailable' ? 'fa-check' : 'fa-ban'"></i>
                    <span x-text="menuItems.find(i => i.id === openActionMenu)?.status === 'unavailable' ? 'Set Available' : 'Unavailable'"></span>
                </button>
                <div class="border-t border-gray-100 mx-3 my-0.5"></div>
                <button type="button"
                        @click.stop="
                            if (confirm('Delete this menu item? This cannot be undone.')) {
                                const id = openActionMenu;
                                openActionMenu = null;
                                fetch('/ftadmin/menu/' + id, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                                        'Accept': 'application/json'
                                    }
                                }).then(r => r.json()).then(data => {
                                    if (data.success) {
                                        menuItems = menuItems.filter(i => i.id !== id);
                                    }
                                });
                            } else {
                                openActionMenu = null;
                            }
                        "
                        class="w-full text-left px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 flex items-center gap-2.5 transition-colors">
                    <i class="fas fa-trash-alt w-3 text-center"></i>
                    Delete
                </button>
            </div>
        </template>

        <!-- ── STAFF actions ── -->
        <template x-if="actionMenuType === 'staff'">
            <div>
                <!-- Deactivate / Reactivate — hidden when fired -->
                <template x-if="workers.find(w => w.id === openActionMenu)?.status !== 'fired'">
                    <div>
                        <button type="button"
                                @click.stop="deactivateStaff(openActionMenu)"
                                :class="workers.find(w => w.id === openActionMenu)?.status === 'deactivated'
                                    ? 'text-emerald-600 hover:bg-emerald-50'
                                    : 'text-orange-500 hover:bg-orange-50'"
                                class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-2.5 transition-colors">
                            <i class="fas w-3 text-center"
                               :class="workers.find(w => w.id === openActionMenu)?.status === 'deactivated' ? 'fa-user-check' : 'fa-user-slash'"></i>
                            <span x-text="workers.find(w => w.id === openActionMenu)?.status === 'deactivated' ? 'Reactivate' : 'Deactivate'"></span>
                        </button>
                        <div class="border-t border-gray-100 mx-3 my-0.5"></div>
                    </div>
                </template>

                <!-- Fired row: when NOT fired show full-width Fired button -->
                <template x-if="workers.find(w => w.id === openActionMenu)?.status !== 'fired'">
                    <button type="button" @click.stop="fireStaff(openActionMenu)"
                            class="w-full text-left px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 flex items-center gap-2.5 transition-colors">
                        <i class="fas fa-user-times w-3 text-center"></i>
                        Fired
                    </button>
                </template>

                <!-- When fired: Fired + Delete side by side -->
                <template x-if="workers.find(w => w.id === openActionMenu)?.status === 'fired'">
                    <div class="flex items-center">
                        <button type="button" @click.stop="fireStaff(openActionMenu)"
                                class="flex-1 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 flex items-center gap-2 transition-colors">
                            <i class="fas fa-user-times w-3 text-center"></i>
                            Fired
                        </button>
                        <div class="w-px h-5 bg-gray-100 flex-shrink-0"></div>
                        <button type="button"
                                @click.stop="
                                    if (confirm('Permanently delete this staff member? This cannot be undone.')) {
                                        deleteStaff(openActionMenu);
                                    } else {
                                        openActionMenu = null;
                                    }
                                "
                                class="flex-1 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 flex items-center gap-2 transition-colors">
                            <i class="fas fa-trash-alt w-3 text-center"></i>
                            Delete
                        </button>
                    </div>
                </template>
            </div>
        </template>

    </div>

    <!-- CREATE CATEGORY MODAL -->
    <div x-show="showCreateCategoryModal"
         style="display:none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">

        <div @click.away="closeCreateCategoryModal()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden border border-white/20 flex flex-col h-auto">

            <!-- Header (Fixed) -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-600 text-white p-3 rounded-2xl shadow-lg shadow-blue-100">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight">Create New Category</h2>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-0.5">Add a custom menu category</p>
                    </div>
                </div>
                <button @click="closeCreateCategoryModal()"
                        class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-red-50 text-gray-400 hover:text-red-500 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body (Scrollable) -->
            <div class="px-8 py-8 space-y-6 overflow-y-auto">

                <!-- Category Name Input -->
                <div class="space-y-2">
                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Category Name <span class="text-red-500">*</span></label>
                    <div class="relative group">
                        <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" x-model="newCategoryName" 
                               @keydown.enter="createCategory()"
                               placeholder="e.g., Alacarte, Set, Promotions"
                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-gray-300">
                    </div>
                </div>

                <!-- Color Picker -->
                <div class="space-y-2">
                    <label class="text-[11px] font-black uppercase tracking-widest text-gray-400 ml-1">Color</label>
                    <div class="flex flex-wrap gap-4">
                        <template x-for="color in colorOptions" :key="color.value">
                            <button type="button"
                                    @click="newCategoryColor = color.value"
                                    :class="newCategoryColor === color.value ? 'ring-2 ring-offset-2 ring-blue-600 scale-110' : 'hover:scale-105'"
                                    class="flex flex-col items-center gap-2 transition-all">
                                <div :class="color.class + ' w-10 h-10 rounded-full border-2 border-white shadow-md transition-all'"></div>
                                <span class="text-[10px] font-bold text-gray-600 text-center" x-text="color.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer (Fixed) -->
            <div class="px-8 py-6 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between gap-3 flex-shrink-0">
                <button @click="closeCreateCategoryModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-200 rounded-2xl text-sm font-bold text-gray-600 hover:border-gray-300 hover:bg-white transition-all active:scale-[0.98]">
                    Cancel
                </button>
                <button @click="createCategory()"
                        :disabled="createCategoryLoading"
                        class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-2xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 hover:shadow-blue-300 transition-all active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed"
                        :class="createCategoryLoading ? 'opacity-60' : ''">
                    <span x-show="!createCategoryLoading" class="flex items-center justify-center gap-2">
                        <i class="fas fa-plus text-xs"></i>
                        Create Category
                    </span>
                    <span x-show="createCategoryLoading" class="flex items-center justify-center gap-2">
                        <i class="fas fa-spinner animate-spin text-xs"></i>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- EDIT CATEGORY MODAL -->
    <div x-show="showEditCategoryModal"
         @click.away="closeEditCategoryModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[90] flex items-center justify-center p-4">

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