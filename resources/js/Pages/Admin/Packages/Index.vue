<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalization } from '@/Composables/useLocalization';

const { t, currentLanguage } = useLocalization();

const props = defineProps({
    packages: {
        type: Array,
        required: true,
    },
    addons: {
        type: Array,
        required: true,
    },
    dishes: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
});

const { toast } = useToast();
const { confirm } = useConfirm();

const activeTab = ref('packages'); // 'packages' | 'addons' | 'dishes' | 'categories'

// --- Package Modal State ---
const showPackageModal = ref(false);
const isEditingPackage = ref(false);
const editingPackageId = ref(null);
const currentPackageImage = ref(null);

const packageForm = useForm({
    package_name : '',
    price        : '',
    min_order    : 100,
    description  : '',
    dish_limits  : {},
    dishes       : [],
    image        : null,
});

// --- Addon Modal State ---
const showAddonModal = ref(false);
const isEditingAddon = ref(false);
const editingAddonId = ref(null);

const addonForm = useForm({
    addon_name   : '',
    price_per_pax: '',
    active       : true,
});

// --- Dish Modal State ---
const showDishModal = ref(false);
const isEditingDish = ref(false);
const editingDishId = ref(null);

const dishForm = useForm({
    name: '',
    category: '',
    active: true,
});

// --- Category Modal State ---
const showCategoryModal = ref(false);
const isEditingCategory = ref(false);
const editingCategoryId = ref(null);

const categoryForm = useForm({
    name: '',
});

const dishCategories = computed(() => {
    return props.categories.map(c => c.name);
});

// ─── Package Helper Methods ──────────────────────────────────────────────────

const imagePreviewUrl = ref(null);

function handleImageChange(event) {
    const file = event.target.files[0];
    if (file) {
        packageForm.image = file;
        imagePreviewUrl.value = URL.createObjectURL(file);
    }
}

function clearSelectedImage() {
    packageForm.image = null;
    imagePreviewUrl.value = null;
    const fileInput = document.getElementById('package-image-input');
    if (fileInput) fileInput.value = '';
}

function openCreatePackage() {
    isEditingPackage.value = false;
    editingPackageId.value = null;
    currentPackageImage.value = null;
    imagePreviewUrl.value = null;
    packageForm.package_name = '';
    packageForm.price        = '';
    packageForm.min_order    = 100;
    packageForm.description  = '';
    packageForm.image        = null;
    
    const limits = {};
    props.categories.forEach(cat => {
        limits[cat.name] = 1;
    });
    packageForm.dish_limits = limits;
    packageForm.dishes       = [];
    
    packageForm.clearErrors();
    showPackageModal.value = true;
}

function openEditPackage(pkg) {
    isEditingPackage.value = true;
    editingPackageId.value = pkg.id;
    currentPackageImage.value = pkg.image;
    imagePreviewUrl.value = null;
    packageForm.package_name = pkg.package_name;
    packageForm.price        = pkg.price;
    packageForm.min_order    = pkg.min_order;
    packageForm.description  = pkg.description;
    packageForm.image        = null;
    
    const limits = {};
    props.categories.forEach(cat => {
        limits[cat.name] = pkg.dish_limits?.[cat.name] !== undefined ? pkg.dish_limits[cat.name] : 0;
    });
    packageForm.dish_limits  = limits;
    
    packageForm.dishes       = pkg.dishes ? pkg.dishes.map(d => d.id) : [];
    packageForm.clearErrors();
    showPackageModal.value = true;
}

function closePackageModal() {
    showPackageModal.value = false;
    imagePreviewUrl.value = null;
}

function submitPackage() {
    const route_name = isEditingPackage.value
        ? route('admin.packages.update', { id: editingPackageId.value })
        : route('admin.packages.store');

    packageForm.post(route_name, {
        onSuccess: () => {
            toast(isEditingPackage.value ? t('admin_toast_pkg_updated') : t('admin_toast_pkg_created'));
            closePackageModal();
        }
    });
}

async function deletePackage(id) {
    if (await confirm(t('admin_confirm_delete_pkg'), t('admin_confirm_delete_pkg_title'))) {
        router.delete(route('admin.packages.delete', { id }), {
            onSuccess: () => toast(t('admin_toast_pkg_deleted'))
        });
    }
}

async function deleteAllPackages() {
    if (await confirm(t('admin_confirm_clear_all_packages_desc'), t('admin_confirm_clear_all_packages_title'))) {
        router.delete(route('admin.packages.clear-all'));
    }
}

// --- Select All Dishes Helper ---
const activeDishes = computed(() => {
    return props.dishes.filter(d => d.active);
});

const isAllDishesSelected = computed(() => {
    if (activeDishes.value.length === 0) return false;
    return activeDishes.value.every(dish => packageForm.dishes.includes(dish.id));
});

function toggleSelectAllDishes() {
    if (isAllDishesSelected.value) {
        packageForm.dishes = [];
    } else {
        packageForm.dishes = activeDishes.value.map(dish => dish.id);
    }
}

function selectDishesInCategory(categoryName) {
    const categoryDishIds = props.dishes
        .filter(d => d.category === categoryName && d.active)
        .map(d => d.id);
        
    const updatedDishes = [...packageForm.dishes];
    categoryDishIds.forEach(id => {
        if (!updatedDishes.includes(id)) {
            updatedDishes.push(id);
        }
    });
    packageForm.dishes = updatedDishes;
}

function clearDishesInCategory(categoryName) {
    const categoryDishIds = props.dishes
        .filter(d => d.category === categoryName && d.active)
        .map(d => d.id);
        
    packageForm.dishes = packageForm.dishes.filter(id => !categoryDishIds.includes(id));
}

const expandedPackageDishes = ref({});

function togglePackageDishes(pkgId) {
    expandedPackageDishes.value[pkgId] = !expandedPackageDishes.value[pkgId];
}

// ─── Add-on Helper Methods ───────────────────────────────────────────────────

function openCreateAddon() {
    isEditingAddon.value = false;
    editingAddonId.value = null;
    addonForm.reset();
    addonForm.clearErrors();
    showAddonModal.value = true;
}

function openEditAddon(addon) {
    isEditingAddon.value = true;
    editingAddonId.value = addon.id;
    addonForm.addon_name    = addon.addon_name;
    addonForm.price_per_pax = addon.price_per_pax;
    addonForm.active        = !!addon.active;
    addonForm.clearErrors();
    showAddonModal.value = true;
}

function closeAddonModal() {
    showAddonModal.value = false;
}

function submitAddon() {
    const route_name = isEditingAddon.value
        ? route('admin.addons.update', { id: editingAddonId.value })
        : route('admin.addons.store');

    addonForm.post(route_name, {
        onSuccess: () => {
            toast(isEditingAddon.value ? t('admin_toast_addon_updated') : t('admin_toast_addon_created'));
            closeAddonModal();
        }
    });
}

async function deleteAddon(id) {
    if (await confirm(t('admin_confirm_delete_addon'), t('admin_confirm_delete_addon_title'))) {
        router.delete(route('admin.addons.delete', { id }), {
            onSuccess: () => toast(t('admin_toast_addon_deleted'))
        });
    }
}

async function deleteAllAddons() {
    if (await confirm(t('admin_confirm_clear_all_addons_desc'), t('admin_confirm_clear_all_addons_title'))) {
        router.delete(route('admin.addons.clear-all'));
    }
}

function toggleAddonStatus(addon) {
    const toggledActive = !addon.active;
    router.post(route('admin.addons.update', { id: addon.id }), {
        addon_name: addon.addon_name,
        price_per_pax: addon.price_per_pax,
        active: toggledActive ? 1 : 0
    }, {
        onSuccess: () => {
            toast(toggledActive ? t('admin_toast_addon_status_active') : t('admin_toast_addon_status_inactive'));
        }
    });
}

// ─── Dishes Helper Methods ───────────────────────────────────────────────────

function openCreateDish() {
    isEditingDish.value = false;
    editingDishId.value = null;
    dishForm.reset();
    dishForm.category = props.categories[0]?.name || '';
    dishForm.clearErrors();
    showDishModal.value = true;
}

function openEditDish(dish) {
    isEditingDish.value = true;
    editingDishId.value = dish.id;
    dishForm.name = dish.name;
    dishForm.category = dish.category;
    dishForm.active = !!dish.active;
    dishForm.clearErrors();
    showDishModal.value = true;
}

function closeDishModal() {
    showDishModal.value = false;
}

function submitDish() {
    const route_name = isEditingDish.value
        ? route('admin.dishes.update', { id: editingDishId.value })
        : route('admin.dishes.store');

    dishForm.post(route_name, {
        onSuccess: () => {
            toast(isEditingDish.value ? t('admin_toast_dish_updated') : t('admin_toast_dish_created'));
            closeDishModal();
        }
    });
}

async function deleteDish(id) {
    if (await confirm(t('admin_confirm_delete_dish'), t('admin_confirm_delete_dish_title'))) {
        router.delete(route('admin.dishes.delete', { id }), {
            onSuccess: () => toast(t('admin_toast_dish_deleted'))
        });
    }
}

async function deleteAllDishes() {
    if (await confirm(t('admin_confirm_clear_all_dishes_desc'), t('admin_confirm_clear_all_dishes_title'))) {
        router.delete(route('admin.dishes.clear-all'));
    }
}

function toggleDishStatus(dish) {
    const toggledActive = !dish.active;
    router.post(route('admin.dishes.update', { id: dish.id }), {
        name: dish.name,
        category: dish.category,
        active: toggledActive ? 1 : 0
    }, {
        onSuccess: () => {
            toast(toggledActive ? t('admin_toast_dish_status_active') : t('admin_toast_dish_status_inactive'));
        }
    });
}

// ─── Category Helper Methods ─────────────────────────────────────────────────

function openCreateCategory() {
    isEditingCategory.value = false;
    editingCategoryId.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    showCategoryModal.value = true;
}

function openEditCategory(cat) {
    isEditingCategory.value = true;
    editingCategoryId.value = cat.id;
    categoryForm.name = cat.name;
    categoryForm.clearErrors();
    showCategoryModal.value = true;
}

function closeCategoryModal() {
    showCategoryModal.value = false;
}

function submitCategory() {
    const route_name = isEditingCategory.value
        ? route('admin.categories.update', { id: editingCategoryId.value })
        : route('admin.categories.store');

    categoryForm.post(route_name, {
        onSuccess: () => {
            toast(isEditingCategory.value ? t('admin_toast_cat_updated') : t('admin_toast_cat_created'));
            closeCategoryModal();
        }
    });
}

async function deleteCategory(id) {
    if (await confirm(t('admin_confirm_delete_cat'), t('admin_confirm_delete_cat_title'))) {
        router.delete(route('admin.categories.delete', { id }), {
            onSuccess: () => {
                toast(t('admin_toast_cat_deleted'));
            },
            onError: (errors) => {
                if (errors.category) {
                    toast(errors.category, 'error');
                }
            }
        });
    }
}

async function deleteAllCategories() {
    if (await confirm(t('admin_confirm_clear_all_categories_desc'), t('admin_confirm_clear_all_categories_title'))) {
        router.delete(route('admin.categories.clear-all'), {
            onError: (errors) => {
                if (errors.category) {
                    toast(errors.category, 'error');
                }
            }
        });
    }
}

// Active addons list for display purposes in Package tab
const activeAddons = computed(() => {
    return props.addons.filter(a => a.active);
});

// Category categorization helpers (identical to Customer Menu index)
function getCategoryKey(packageName) {
    const lower = packageName.toLowerCase();
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding') || lower.includes('tunang') || lower.includes('engagement')) {
        return 'wedding';
    }
    if (lower.includes('corporate') || lower.includes('korporat') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
        return 'corporate';
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('kenduri') || lower.includes('family')) {
        return 'aqiqah';
    }
    return 'other';
}

function getCategoryIcon(name) {
    const lower = name.toLowerCase();
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding') || lower.includes('tunang') || lower.includes('engagement')) {
        return {
            icon: 'fa-heart',
            colors: 'text-[#8C3A3A] bg-[#FDF2F2] border-[#FADCDD]'
        };
    }
    if (lower.includes('corporate') || lower.includes('korporat') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
        return {
            icon: 'fa-briefcase',
            colors: 'text-[#3D574B] bg-[#EBEFEF] border-[#D1DEDB]'
        };
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('kenduri') || lower.includes('family')) {
        return {
            icon: 'fa-birthday-cake',
            colors: 'text-[#6E5D4F] bg-[#FAF6F0] border-[#EADED9]'
        };
    }
    return {
        icon: 'fa-utensils',
        colors: 'text-[#4A6B5D] bg-[#FAF7F2] border-[#E6E1DA]'
    };
}

function getPackageImage(pkg) {
    if (pkg.image && pkg.image !== 'placeholder.jpg') {
        return pkg.image.startsWith('/') ? pkg.image : '/' + pkg.image;
    }
    
    const weddingImages = [
        'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=80', // Elegant table setting
        'https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=800&q=80', // Luxury buffet setup
        'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=800&q=80', // Banquet food display
        'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?auto=format&fit=crop&w=800&q=80'  // Wedding reception food
    ];

    const aqiqahImages = [
        'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80', // BBQ/Roasted meats feast
        'https://images.unsplash.com/photo-1560684352-8497838a2229?auto=format&fit=crop&w=800&q=80', // Buffet pans
        'https://images.unsplash.com/photo-1532636875304-0c8fe119ff91?auto=format&fit=crop&w=800&q=80', // Catering lamb/beef dishes
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80'  // Gourmet roasted dish
    ];

    const corporateImages = [
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80', // Canape appetizers
        'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=800&q=80', // High tea/buffet platters
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=80', // Premium dining event
        'https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=800&q=80'  // Gourmet catering banquet
    ];

    const generalMalayImages = [
        'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=800&q=80', // Biryani Rice feast
        'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?auto=format&fit=crop&w=800&q=80', // Traditional curry/roti setup
        'https://images.unsplash.com/photo-1541832676-9b763b0239ab?auto=format&fit=crop&w=800&q=80', // Asian gourmet hot pot/feast
        'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&w=800&q=80'  // Asian style skewers/catering
    ];

    const name = pkg.package_name || '';
    const lower = name.toLowerCase();
    
    // Hash function to consistently select the same image for the same package name
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash);

    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding') || lower.includes('tunang') || lower.includes('engagement') || lower.includes('nikah')) {
        return weddingImages[index % weddingImages.length];
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('family') || lower.includes('kenduri')) {
        return aqiqahImages[index % aqiqahImages.length];
    }
    if (lower.includes('korporat') || lower.includes('corporate') || lower.includes('event') || lower.includes('seminar') || lower.includes('mesyuarat')) {
        return corporateImages[index % corporateImages.length];
    }
    return generalMalayImages[index % generalMalayImages.length];
}

const activePackageFilter = ref('all');

// --- Search & Filters & Pagination state for Packages ---
const packageSearchQuery = ref('');
const packageCurrentPage = ref(1);
const packagesPerPage = 6;

const filteredPackages = computed(() => {
    let result = props.packages;
    
    // Category filter
    if (activePackageFilter.value !== 'all') {
        result = result.filter(p => getCategoryKey(p.package_name) === activePackageFilter.value);
    }
    
    // Search query filter
    if (packageSearchQuery.value.trim()) {
        const query = packageSearchQuery.value.toLowerCase().trim();
        result = result.filter(p => p.package_name.toLowerCase().includes(query) || p.description?.toLowerCase().includes(query));
    }
    
    return result;
});

const paginatedPackages = computed(() => {
    const start = (packageCurrentPage.value - 1) * packagesPerPage;
    return filteredPackages.value.slice(start, start + packagesPerPage);
});

const packageTotalPages = computed(() => {
    return Math.ceil(filteredPackages.value.length / packagesPerPage) || 1;
});

// Reset page when filter or search changes
watch([activePackageFilter, packageSearchQuery], () => {
    packageCurrentPage.value = 1;
});

// --- Search & Filters & Pagination state for Add-ons ---
const addonSearchQuery = ref('');
const addonFilterStatus = ref('all'); // 'all' | 'active' | 'inactive'
const addonCurrentPage = ref(1);
const addonsPerPage = 10;

const filteredAddons = computed(() => {
    let result = props.addons;
    
    if (addonFilterStatus.value === 'active') {
        result = result.filter(a => a.active);
    } else if (addonFilterStatus.value === 'inactive') {
        result = result.filter(a => !a.active);
    }
    
    if (addonSearchQuery.value.trim()) {
        const query = addonSearchQuery.value.toLowerCase().trim();
        result = result.filter(a => a.addon_name.toLowerCase().includes(query));
    }
    
    return result;
});

const paginatedAddons = computed(() => {
    const start = (addonCurrentPage.value - 1) * addonsPerPage;
    return filteredAddons.value.slice(start, start + addonsPerPage);
});

const addonTotalPages = computed(() => {
    return Math.ceil(filteredAddons.value.length / addonsPerPage) || 1;
});

watch([addonSearchQuery, addonFilterStatus], () => {
    addonCurrentPage.value = 1;
});

// --- Search & Filters & Pagination state for Dishes ---
const dishSearchQuery = ref('');
const dishFilterStatus = ref('all'); // 'all' | 'active' | 'inactive'
const dishFilterCategory = ref('all');
const dishCurrentPage = ref(1);
const dishesPerPage = 10;

const filteredDishes = computed(() => {
    let result = props.dishes;
    
    if (dishFilterStatus.value === 'active') {
        result = result.filter(d => d.active);
    } else if (dishFilterStatus.value === 'inactive') {
        result = result.filter(d => !d.active);
    }
    
    if (dishFilterCategory.value !== 'all') {
        result = result.filter(d => d.category === dishFilterCategory.value);
    }
    
    if (dishSearchQuery.value.trim()) {
        const query = dishSearchQuery.value.toLowerCase().trim();
        result = result.filter(d => d.name.toLowerCase().includes(query) || d.category.toLowerCase().includes(query));
    }
    
    return result;
});

const paginatedDishes = computed(() => {
    const start = (dishCurrentPage.value - 1) * dishesPerPage;
    return filteredDishes.value.slice(start, start + dishesPerPage);
});

const dishTotalPages = computed(() => {
    return Math.ceil(filteredDishes.value.length / dishesPerPage) || 1;
});

watch([dishSearchQuery, dishFilterStatus, dishFilterCategory], () => {
    dishCurrentPage.value = 1;
});

// --- Search & Pagination state for Categories ---
const categorySearchQuery = ref('');
const categoryCurrentPage = ref(1);
const categoriesPerPage = 10;

const filteredCategories = computed(() => {
    let result = props.categories;
    
    if (categorySearchQuery.value.trim()) {
        const query = categorySearchQuery.value.toLowerCase().trim();
        result = result.filter(c => c.name.toLowerCase().includes(query));
    }
    
    return result;
});

const paginatedCategories = computed(() => {
    const start = (categoryCurrentPage.value - 1) * categoriesPerPage;
    return filteredCategories.value.slice(start, start + categoriesPerPage);
});

const categoryTotalPages = computed(() => {
    return Math.ceil(filteredCategories.value.length / categoriesPerPage) || 1;
});

watch([categorySearchQuery], () => {
    categoryCurrentPage.value = 1;
});
</script>

<template>
    <AdminLayout
        :title="t('admin_packages_title')"
        :header-title="t('admin_packages_title')"
        :header-desc="t('admin_packages_desc')"
    >
        <!-- Tab Navigation Bar (Pill Segmented Control) -->
        <div class="flex bg-[#FAF7F2] border border-[#E6E1DA] rounded-2xl p-1 mb-6 overflow-x-auto flex-nowrap whitespace-nowrap scrollbar-none select-none">
            <button
                @click="activeTab = 'packages'"
                class="flex-1 text-center py-1.5 px-2.5 text-[9px] sm:text-xs uppercase tracking-wider font-extrabold rounded-xl transition-all duration-200 cursor-pointer focus:outline-none shrink-0"
                :class="activeTab === 'packages' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#5C6460]'"
            >
                {{ t('admin_tab_packages') }} ({{ packages.length }})
            </button>
            <button
                @click="activeTab = 'addons'"
                class="flex-1 text-center py-1.5 px-2.5 text-[9px] sm:text-xs uppercase tracking-wider font-extrabold rounded-xl transition-all duration-200 cursor-pointer focus:outline-none shrink-0"
                :class="activeTab === 'addons' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#5C6460]'"
            >
                {{ t('admin_tab_addons') }} ({{ addons.length }})
            </button>
            <button
                @click="activeTab = 'dishes'"
                class="flex-1 text-center py-1.5 px-2.5 text-[9px] sm:text-xs uppercase tracking-wider font-extrabold rounded-xl transition-all duration-200 cursor-pointer focus:outline-none shrink-0"
                :class="activeTab === 'dishes' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#5C6460]'"
            >
                {{ t('admin_tab_dishes') }} ({{ dishes.length }})
            </button>
            <button
                @click="activeTab = 'categories'"
                class="flex-1 text-center py-1.5 px-2.5 text-[9px] sm:text-xs uppercase tracking-wider font-extrabold rounded-xl transition-all duration-200 cursor-pointer focus:outline-none shrink-0"
                :class="activeTab === 'categories' ? 'bg-[#4A6B5D] text-white shadow-xs' : 'text-[#8C8275] hover:text-[#5C6460]'"
            >
                {{ t('admin_tab_categories') }} ({{ categories.length }})
            </button>
        </div>

        <!-- TAB 1: CATERING PACKAGES -->
        <div v-if="activeTab === 'packages'" class="space-y-6">
            <!-- Search & Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white border border-[#E6E1DA] rounded-2xl md:rounded-3xl p-3 md:p-5 shadow-xs">
                <!-- Search Input -->
                <div class="relative flex-grow max-w-md w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8C8275]">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input 
                        v-model="packageSearchQuery" 
                        type="text" 
                        :placeholder="t('admin_search_packages_placeholder')" 
                        class="w-full h-9 pl-9 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                    />
                    <button 
                        v-if="packageSearchQuery"
                        @click="packageSearchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors cursor-pointer"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <!-- Action Buttons -->
                <div class="flex flex-row items-center gap-2 w-full md:w-auto">
                    <button
                        v-if="packages.length > 0"
                        @click="deleteAllPackages"
                        class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold px-2.5 sm:px-4 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 sm:gap-2 shadow-xs transition-all cursor-pointer shrink-0 focus:outline-none animate-fade-in flex-1 sm:flex-none"
                    >
                        <i class="fas fa-trash-alt text-[9px] sm:text-xs"></i> {{ currentLanguage === 'en' ? 'Delete All' : 'Padam Semua' }}
                    </button>
                    <button
                        @click="openCreatePackage"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 sm:px-5 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 sm:gap-2 shadow-md transition-all cursor-pointer shrink-0 focus:outline-none flex-1 sm:flex-none"
                    >
                        <i class="fas fa-plus text-[10px] sm:text-xs"></i> {{ t('admin_create_package_btn') }}
                    </button>
                </div>
            </div>

            <!-- Main Packages Panel (if any packages exist in database) -->
            <div v-if="packages.length > 0" class="space-y-6">
                <!-- Sub-tabs for Package Categories -->
                <div class="flex overflow-x-auto flex-nowrap gap-2 pt-2 border-b border-[#FAF6F0] pb-4 scrollbar-none whitespace-nowrap">
                    <button 
                        @click="activePackageFilter = 'all'" 
                        class="px-3 py-1.5 rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none shrink-0"
                        :class="activePackageFilter === 'all' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        {{ t('admin_all_packages') }} ({{ packages.length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'wedding'" 
                        class="px-3 py-1.5 rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none shrink-0"
                        :class="activePackageFilter === 'wedding' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        {{ t('admin_pkg_cat_wedding') }} ({{ packages.filter(p => getCategoryKey(p.package_name) === 'wedding').length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'corporate'" 
                        class="px-3 py-1.5 rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none shrink-0"
                        :class="activePackageFilter === 'corporate' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        {{ t('admin_pkg_cat_corporate') }} ({{ packages.filter(p => getCategoryKey(p.package_name) === 'corporate').length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'aqiqah'" 
                        class="px-3 py-1.5 rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none shrink-0"
                        :class="activePackageFilter === 'aqiqah' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        {{ t('admin_pkg_cat_aqiqah') }} ({{ packages.filter(p => getCategoryKey(p.package_name) === 'aqiqah').length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'other'" 
                        class="px-3 py-1.5 rounded-xl text-[10px] sm:text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none shrink-0"
                        :class="activePackageFilter === 'other' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        {{ t('admin_pkg_cat_other') }} ({{ packages.filter(p => getCategoryKey(p.package_name) === 'other').length }})
                    </button>
                </div>

                <!-- Packages list grid -->
                <div v-if="filteredPackages.length > 0" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div
                        v-for="(pkg, idx) in paginatedPackages"
                        :key="pkg.id"
                        class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs hover:shadow-md transition-all duration-200 overflow-hidden animate-fade-in"
                    >
                        <div class="grid sm:grid-cols-12 gap-0">
                            <!-- Left: Package Info (7 cols) -->
                            <div class="sm:col-span-7 p-3.5 sm:p-5 space-y-3 sm:space-y-4">
                                <div class="flex flex-wrap justify-between items-start gap-3">
                                    <div class="flex items-center gap-2">
                                        <div class="text-[9px] sm:text-xs font-bold text-[#8C8275] bg-[#FAF8F5] border border-[#E6E1DA] rounded-lg w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center select-none shrink-0">
                                            {{ (packageCurrentPage - 1) * packagesPerPage + idx + 1 }}
                                        </div>
                                        <img :src="getPackageImage(pkg)" class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl object-cover shrink-0 border border-[#E6E1DA] shadow-xs" alt="Package image" />
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-bold text-[#2D3330] tracking-wide uppercase font-serif-luxury">{{ pkg.package_name }}</h3>
                                            <p class="text-[9px] sm:text-[10px] text-[#C5A880] font-bold mt-0.5 uppercase tracking-wider">
                                                RM {{ parseFloat(pkg.price).toFixed(2) }} / pax &nbsp;·&nbsp; Min: {{ pkg.min_order }} pax
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0 ml-auto sm:ml-0">
                                        <button
                                            @click="openEditPackage(pkg)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold px-2 sm:px-2.5 py-1 rounded-lg text-[9px] sm:text-xs transition-colors flex items-center gap-1 cursor-pointer focus:outline-none"
                                        >
                                            <i class="fas fa-edit text-[8px] sm:text-[9px]"></i> {{ t('admin_edit') }}
                                        </button>
                                        <button
                                            @click="deletePackage(pkg.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold px-2 sm:px-2.5 py-1 rounded-lg text-[9px] sm:text-xs transition-colors flex items-center gap-1 cursor-pointer focus:outline-none"
                                        >
                                            <i class="fas fa-trash-alt text-[8px] sm:text-[9px]"></i> {{ t('admin_delete') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="border-t border-[#E6E1DA] pt-3.5 space-y-2">
                                    <div class="flex items-center justify-between flex-wrap gap-1">
                                        <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('admin_menu_choices_limits') }}</span>
                                        <div class="flex flex-wrap gap-1">
                                            <span 
                                                v-for="(limit, cat) in (pkg.dish_limits || {})" 
                                                :key="cat"
                                                class="text-[8px] font-bold bg-[#FAF6F0] text-[#5C6460] border border-[#E6E1DA] px-2 py-0.5 rounded-full"
                                            >
                                                {{ cat }}: {{ limit }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Toggle Collapse Button -->
                                    <div v-if="pkg.dishes && pkg.dishes.length > 0" class="flex items-center">
                                        <button
                                            type="button"
                                            @click="togglePackageDishes(pkg.id)"
                                            class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-bold text-[#4A6B5D] hover:text-[#3D574B] hover:underline cursor-pointer focus:outline-none"
                                        >
                                            <i 
                                                class="fas text-[8px] transition-transform duration-200"
                                                :class="expandedPackageDishes[pkg.id] ? 'fa-chevron-up' : 'fa-chevron-down'"
                                            ></i>
                                            <span>
                                                {{ expandedPackageDishes[pkg.id] 
                                                    ? (currentLanguage === 'en' ? 'Hide Available Dishes' : 'Sembunyikan Pilihan Hidangan')
                                                    : (currentLanguage === 'en' ? `Show Available Dishes (${pkg.dishes.length})` : `Lihat Pilihan Hidangan (${pkg.dishes.length})`) 
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                    
                                    <!-- Collapsible Dishes List -->
                                    <div 
                                        v-if="pkg.dishes && pkg.dishes.length > 0" 
                                        v-show="expandedPackageDishes[pkg.id]" 
                                        class="flex flex-wrap gap-1 text-xs pt-0.5 animate-fade-in"
                                    >
                                        <span
                                            v-for="dish in pkg.dishes"
                                            :key="dish.id"
                                            class="inline-flex items-center gap-1 bg-[#FAF6F0]/40 text-[#5C6460] px-1.5 py-0.5 rounded-lg border border-[#E6E1DA] text-[9px] sm:text-[10px] font-medium"
                                        >
                                            <i class="fas fa-utensils text-[8px] text-[#4A6B5D]"></i>
                                            {{ dish.name }} ({{ dish.category }})
                                        </span>
                                    </div>
                                    <div v-else class="text-[10px] sm:text-xs text-[#8C8275] italic">
                                        {{ t('admin_no_custom_dishes_pkg') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Associated Global Add-ons Info (5 cols) -->
                            <div class="sm:col-span-5 bg-[#FAF7F2] border-t sm:border-t-0 sm:border-l border-[#E6E1DA] p-3.5 sm:p-5 flex flex-col justify-between gap-3">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_active_global_addons') }}</span>
                                        <span class="text-[8px] sm:text-[9px] font-bold text-[#4A6B5D] bg-[#4A6B5D]/10 px-1.5 py-0.5 rounded-full">
                                            {{ activeAddons.length }} {{ t('admin_active_options_count') }}
                                        </span>
                                    </div>

                                    <div v-if="activeAddons.length > 0" class="max-h-[120px] overflow-y-auto space-y-1.5 pr-1">
                                        <div
                                            v-for="addon in activeAddons"
                                            :key="addon.id"
                                            class="flex justify-between items-center text-[10px] sm:text-xs text-[#5C6460] bg-white px-2.5 py-1.5 rounded-xl border border-[#E6E1DA] shadow-xs"
                                        >
                                            <span class="font-semibold truncate pr-2">{{ addon.addon_name }}</span>
                                            <span class="text-[#C5A880] font-bold text-[9px] sm:text-[10px] shrink-0 ml-auto">+RM {{ parseFloat(addon.price_per_pax).toFixed(2) }}/pax</span>
                                        </div>
                                    </div>
                                    <div v-else class="py-3 text-center">
                                        <p class="text-[10px] sm:text-xs text-[#B5AFA8] italic">{{ t('admin_no_active_addons_configured') }}</p>
                                    </div>
                                </div>

                                <button
                                    @click="activeTab = 'addons'"
                                    class="w-full flex items-center justify-center gap-1.5 border border-dashed border-[#4A6B5D]/40 text-[#4A6B5D] hover:bg-[#4A6B5D]/5 font-bold py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-wider transition-colors cursor-pointer focus:outline-none"
                                >
                                    <i class="fas fa-list text-[9px] sm:text-[10px]"></i> {{ t('admin_manage_addons_btn') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Controls for Packages -->
                <div v-if="filteredPackages.length > 0" class="flex justify-between items-center bg-white border border-[#E6E1DA] rounded-3xl p-4 shadow-2xs">
                    <button 
                        @click="packageCurrentPage = Math.max(1, packageCurrentPage - 1)"
                        :disabled="packageCurrentPage === 1"
                        class="px-4 py-2 border border-[#E6E1DA] rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 focus:outline-none"
                        :class="packageCurrentPage === 1 ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <i class="fas fa-chevron-left text-[9px]"></i>
                        <span>{{ t('admin_prev_page') }}</span>
                    </button>
                    
                    <span class="text-xs font-semibold text-[#8C8275]">
                        {{ packageCurrentPage }} / {{ packageTotalPages }}
                    </span>
                    
                    <button 
                        @click="packageCurrentPage = Math.min(packageTotalPages, packageCurrentPage + 1)"
                        :disabled="packageCurrentPage === packageTotalPages"
                        class="px-4 py-2 border border-[#E6E1DA] rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 focus:outline-none"
                        :class="packageCurrentPage === packageTotalPages ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <span>{{ t('admin_next_page') }}</span>
                        <i class="fas fa-chevron-right text-[9px]"></i>
                    </button>
                </div>

                <!-- Empty State for filtered packages -->
                <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-16 text-center space-y-3">
                    <div class="w-12 h-12 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-xl flex items-center justify-center mx-auto text-lg">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-[#2D3330]">{{ t('admin_no_packages_matching_filter') }}</h4>
                        <p class="text-[11px] text-[#8C8275] mt-1">{{ t('admin_try_selecting_other_pkg_cat') }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty Packages State (when no packages exist at all in the system) -->
            <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-4">
                <div class="w-16 h-16 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-2xl flex items-center justify-center mx-auto text-2xl">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h4 class="text-[#2D3330] font-bold">{{ t('admin_no_catering_packages_configured') }}</h4>
                    <p class="text-xs text-[#8C8275] mt-1">{{ t('admin_get_started_pkg_desc') }}</p>
                </div>
                <button @click="openCreatePackage" class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm">
                    <i class="fas fa-plus"></i> {{ t('admin_create_first_package_btn') }}
                </button>
            </div>
        </div>

        <!-- TAB 2: GLOBAL ADD-ONS LIBRARY -->
        <div v-if="activeTab === 'addons'" class="space-y-4">
            <!-- Search & Filters Card -->
            <div class="bg-white border border-[#E6E1DA] rounded-2xl p-3 md:p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 flex-grow max-w-2xl w-full">
                    <!-- Search Input -->
                    <div class="relative flex-grow w-full">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8C8275]">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input 
                            v-model="addonSearchQuery" 
                            type="text" 
                            :placeholder="t('admin_search_addons_placeholder')" 
                            class="w-full h-9 pl-9 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                        />
                        <button 
                            v-if="addonSearchQuery"
                            @click="addonSearchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors cursor-pointer"
                        >
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    <!-- Status Filter Dropdown -->
                    <div class="relative w-full sm:w-44">
                        <select 
                            v-model="addonFilterStatus"
                            class="w-full h-9 pl-3 pr-8 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                        >
                            <option value="all">{{ t('admin_all_statuses') }}</option>
                            <option value="active">{{ t('admin_active') }}</option>
                            <option value="inactive">{{ t('admin_inactive') }}</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[#8C8275]">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="flex flex-row items-center gap-2 w-full md:w-auto">
                    <button
                        v-if="addons.length > 0"
                        @click="deleteAllAddons"
                        class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold px-2.5 sm:px-3.5 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-xs transition-all cursor-pointer shrink-0 focus:outline-none animate-fade-in flex-1 sm:flex-none"
                    >
                        <i class="fas fa-trash-alt text-[9px] sm:text-xs"></i> {{ currentLanguage === 'en' ? 'Delete All' : 'Padam Semua' }}
                    </button>
                    <button
                        @click="openCreateAddon"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 sm:px-4 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-md transition-all cursor-pointer shrink-0 focus:outline-none flex-1 sm:flex-none"
                    >
                        <i class="fas fa-plus text-[9px] sm:text-xs"></i> {{ t('admin_add_new_global_item_btn') }}
                    </button>
                </div>
            </div>

            <!-- Add-ons Data Table -->
            <div v-if="filteredAddons.length > 0" class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest w-12 text-center">{{ t('admin_reviews_no_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_addon_item_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_price_pax_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('admin_status_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">{{ t('admin_actions_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA] text-[11px] sm:text-xs text-[#5C6460]">
                            <tr v-for="(addon, index) in paginatedAddons" :key="addon.id" class="hover:bg-[#FAFAF9] transition-colors">
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center font-semibold text-[#8C8275]">
                                    {{ (addonCurrentPage - 1) * addonsPerPage + index + 1 }}
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-[#C5A880]/10 text-[#C5A880] flex items-center justify-center text-[9px] shrink-0 border border-[#C5A880]/20">
                                            <i class="fas fa-star text-[8px]"></i>
                                        </div>
                                        <span class="font-bold text-[#2D3330] uppercase tracking-wide">{{ addon.addon_name }}</span>
                                    </div>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5">
                                    <span class="text-[#C5A880] font-bold text-[10px] sm:text-[11px]">
                                        +RM {{ parseFloat(addon.price_per_pax).toFixed(2) }}
                                    </span>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center">
                                    <button 
                                        @click="toggleAddonStatus(addon)"
                                        class="inline-flex items-center gap-1 text-[8px] sm:text-[9px] font-bold px-2 py-0.5 rounded-full border cursor-pointer transition-all whitespace-nowrap focus:outline-none"
                                        :class="addon.active 
                                            ? 'bg-emerald-50 text-[#4A6B5D] border-emerald-200 hover:bg-emerald-100' 
                                            : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        <i class="fas text-[6px]" :class="addon.active ? 'fa-check' : 'fa-times'"></i>
                                        {{ addon.active ? t('admin_active') : t('admin_inactive') }}
                                    </button>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button
                                            @click="openEditAddon(addon)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold w-7 h-7 rounded-lg transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
                                            :title="t('admin_edit_global_addon')"
                                        >
                                            <i class="fas fa-edit text-[9px] sm:text-[10px]"></i>
                                        </button>
                                        <button
                                            @click="deleteAddon(addon.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold w-7 h-7 rounded-lg transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
                                            :title="t('admin_delete')"
                                        >
                                            <i class="fas fa-trash-alt text-[9px] sm:text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Addons -->
                <div v-if="filteredAddons.length > 0" class="flex justify-between items-center p-3 border-t border-[#E6E1DA]">
                    <button 
                        @click="addonCurrentPage = Math.max(1, addonCurrentPage - 1)"
                        :disabled="addonCurrentPage === 1"
                        class="px-2.5 py-1.5 border border-[#E6E1DA] rounded-xl text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="addonCurrentPage === 1 ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <i class="fas fa-chevron-left text-[8px]"></i>
                        <span>{{ t('admin_prev_page') }}</span>
                    </button>
                    
                    <span class="text-[10px] sm:text-xs font-semibold text-[#8C8275]">
                        {{ addonCurrentPage }} / {{ addonTotalPages }}
                    </span>
                    
                    <button 
                        @click="addonCurrentPage = Math.min(addonTotalPages, addonCurrentPage + 1)"
                        :disabled="addonCurrentPage === addonTotalPages"
                        class="px-2.5 py-1.5 border border-[#E6E1DA] rounded-xl text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="addonCurrentPage === addonTotalPages ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <span>{{ t('admin_next_page') }}</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                </div>
            </div>

            <!-- Empty Add-ons State / No matches -->
            <div v-else class="bg-white rounded-2xl border border-[#E6E1DA] p-10 text-center space-y-3">
                <div class="w-12 h-12 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-xl flex items-center justify-center mx-auto text-lg">
                    <i class="fas fa-list-ul"></i>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-[#2D3330]">{{ t('admin_no_global_addons_configured') }}</h4>
                    <p class="text-[10px] sm:text-xs text-[#8C8275] mt-1">{{ addonSearchQuery || addonFilterStatus !== 'all' ? t('admin_no_packages_matching_filter') : t('admin_get_started_addon_desc') }}</p>
                </div>
                <button v-if="!addonSearchQuery && addonFilterStatus === 'all'" @click="openCreateAddon" class="inline-flex items-center gap-1.5 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm focus:outline-none">
                    <i class="fas fa-plus text-[9px] sm:text-[10px]"></i> {{ t('admin_create_first_addon_btn') }}
                </button>
            </div>
        </div>

        <!-- TAB 3: DISHES LIBRARY -->
        <div v-if="activeTab === 'dishes'" class="space-y-4">
            <!-- Search & Filters Card -->
            <div class="bg-white border border-[#E6E1DA] rounded-2xl p-3 md:p-4 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 flex-grow max-w-3xl w-full">
                    <!-- Search Input -->
                    <div class="relative flex-grow w-full">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8C8275]">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input 
                            v-model="dishSearchQuery" 
                            type="text" 
                            :placeholder="t('admin_search_dishes_placeholder')" 
                            class="w-full h-9 pl-9 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                        />
                        <button 
                            v-if="dishSearchQuery"
                            @click="dishSearchQuery = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors cursor-pointer"
                        >
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    <!-- Status Filter Dropdown -->
                    <div class="relative w-full sm:w-36">
                        <select 
                            v-model="dishFilterStatus"
                            class="w-full h-9 pl-3 pr-8 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                        >
                            <option value="all">{{ t('admin_all_statuses') }}</option>
                            <option value="active">{{ t('admin_active') }}</option>
                            <option value="inactive">{{ t('admin_inactive') }}</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[#8C8275]">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                    <!-- Category Filter Dropdown -->
                    <div class="relative w-full sm:w-44">
                        <select 
                            v-model="dishFilterCategory"
                            class="w-full h-9 pl-3 pr-8 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white text-[#5C6460] transition-all appearance-none cursor-pointer"
                        >
                            <option value="all">{{ t('admin_all_categories') }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.name">
                                {{ cat.name }}
                            </option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[#8C8275]">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="flex flex-row items-center gap-2 w-full lg:w-auto">
                    <button
                        v-if="dishes.length > 0"
                        @click="deleteAllDishes"
                        class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold px-2.5 sm:px-3.5 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-xs transition-all cursor-pointer shrink-0 focus:outline-none animate-fade-in flex-1 sm:flex-none"
                    >
                        <i class="fas fa-trash-alt text-[9px] sm:text-xs"></i> {{ currentLanguage === 'en' ? 'Delete All' : 'Padam Semua' }}
                    </button>
                    <button
                        @click="openCreateDish"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 sm:px-4 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-md transition-all cursor-pointer shrink-0 focus:outline-none flex-1 sm:flex-none"
                    >
                        <i class="fas fa-plus text-[9px] sm:text-xs"></i> {{ t('admin_add_new_dish_btn') }}
                    </button>
                </div>
            </div>

            <!-- Dishes Data Table -->
            <div v-if="filteredDishes.length > 0" class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest w-12 text-center">{{ t('admin_reviews_no_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_dish_name_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('admin_category_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('admin_status_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">{{ t('admin_actions_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA] text-[11px] sm:text-xs text-[#5C6460]">
                            <tr v-for="(dish, index) in paginatedDishes" :key="dish.id" class="hover:bg-[#FAFAF9] transition-colors">
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center font-semibold text-[#8C8275]">
                                    {{ (dishCurrentPage - 1) * dishesPerPage + index + 1 }}
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-[#4A6B5D]/10 text-[#4A6B5D] flex items-center justify-center text-[9px] shrink-0 border border-[#4A6B5D]/20">
                                            <i class="fas fa-utensils text-[8px]"></i>
                                        </div>
                                        <span class="font-bold text-[#2D3330] uppercase tracking-wide">{{ dish.name }}</span>
                                    </div>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center">
                                    <span class="font-semibold px-2 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200 text-[9px] sm:text-[10px] whitespace-nowrap">
                                        {{ dish.category }}
                                    </span>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center">
                                    <button 
                                        @click="toggleDishStatus(dish)"
                                        class="inline-flex items-center gap-1 text-[8px] sm:text-[9px] font-bold px-2 py-0.5 rounded-full border cursor-pointer transition-all whitespace-nowrap focus:outline-none"
                                        :class="dish.active 
                                            ? 'bg-emerald-50 text-[#4A6B5D] border-emerald-200 hover:bg-emerald-100' 
                                            : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        <i class="fas text-[6px]" :class="dish.active ? 'fa-check' : 'fa-times'"></i>
                                        {{ dish.active ? t('admin_active') : t('admin_inactive') }}
                                    </button>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button
                                            @click="openEditDish(dish)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold w-7 h-7 rounded-lg transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
                                            :title="t('admin_edit_dish_details')"
                                        >
                                            <i class="fas fa-edit text-[9px] sm:text-[10px]"></i>
                                        </button>
                                        <button
                                            @click="deleteDish(dish.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold w-7 h-7 rounded-lg transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
                                            :title="t('admin_delete')"
                                        >
                                            <i class="fas fa-trash-alt text-[9px] sm:text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Dishes -->
                <div v-if="filteredDishes.length > 0" class="flex justify-between items-center p-3 border-t border-[#E6E1DA]">
                    <button 
                        @click="dishCurrentPage = Math.max(1, dishCurrentPage - 1)"
                        :disabled="dishCurrentPage === 1"
                        class="px-2.5 py-1.5 border border-[#E6E1DA] rounded-xl text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="dishCurrentPage === 1 ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <i class="fas fa-chevron-left text-[8px]"></i>
                        <span>{{ t('admin_prev_page') }}</span>
                    </button>
                    
                    <span class="text-[10px] sm:text-xs font-semibold text-[#8C8275]">
                        {{ dishCurrentPage }} / {{ dishTotalPages }}
                    </span>
                    
                    <button 
                        @click="dishCurrentPage = Math.min(dishTotalPages, dishCurrentPage + 1)"
                        :disabled="dishCurrentPage === dishTotalPages"
                        class="px-2.5 py-1.5 border border-[#E6E1DA] rounded-xl text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="dishCurrentPage === dishTotalPages ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <span>{{ t('admin_next_page') }}</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                </div>
            </div>

            <!-- Empty Dishes State / No matches -->
            <div v-else class="bg-white rounded-2xl border border-[#E6E1DA] p-10 text-center space-y-3">
                <div class="w-12 h-12 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-xl flex items-center justify-center mx-auto text-lg">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-[#2D3330]">{{ t('admin_no_dishes_configured') }}</h4>
                    <p class="text-[10px] sm:text-xs text-[#8C8275] mt-1">{{ dishSearchQuery || dishFilterStatus !== 'all' || dishFilterCategory !== 'all' ? t('admin_no_packages_matching_filter') : t('admin_get_started_dish_desc') }}</p>
                </div>
                <button v-if="!dishSearchQuery && dishFilterStatus === 'all' && dishFilterCategory === 'all'" @click="openCreateDish" class="inline-flex items-center gap-1.5 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-4 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm focus:outline-none">
                    <i class="fas fa-plus text-[9px] sm:text-[10px]"></i> {{ t('admin_create_first_dish_btn') }}
                </button>
            </div>
        </div>

        <!-- TAB 4: DISH CATEGORIES -->
        <div v-if="activeTab === 'categories'" class="space-y-4">
            <!-- Search & Action Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white border border-[#E6E1DA] rounded-2xl p-3 md:p-4 shadow-xs">
                <!-- Search Input -->
                <div class="relative flex-grow max-w-md w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#8C8275]">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input 
                        v-model="categorySearchQuery" 
                        type="text" 
                        :placeholder="t('admin_search_categories_placeholder')" 
                        class="w-full h-9 pl-9 pr-9 bg-[#FAF8F5] border border-[#E6E1DA] rounded-xl text-xs font-semibold text-[#2D3330] placeholder-[#8C8275]/60 focus:outline-none focus:ring-2 focus:ring-[#4A6B5D]/10 focus:border-[#4A6B5D] focus:bg-white transition-all"
                    />
                    <button 
                        v-if="categorySearchQuery"
                        @click="categorySearchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8C8275] hover:text-rose-600 transition-colors cursor-pointer"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <!-- Action Buttons -->
                <div class="flex flex-row items-center gap-2 w-full md:w-auto">
                    <button
                        v-if="categories.length > 0"
                        @click="deleteAllCategories"
                        class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold px-2.5 sm:px-3.5 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-xs transition-all cursor-pointer shrink-0 focus:outline-none animate-fade-in flex-1 sm:flex-none"
                    >
                        <i class="fas fa-trash-alt text-[9px] sm:text-xs"></i> {{ currentLanguage === 'en' ? 'Delete All' : 'Padam Semua' }}
                    </button>
                    <button
                        @click="openCreateCategory"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-3 sm:px-4 py-1.5 rounded-xl text-[9px] sm:text-xs uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-md transition-all cursor-pointer shrink-0 focus:outline-none flex-1 sm:flex-none"
                    >
                        <i class="fas fa-plus text-[9px] sm:text-xs"></i> {{ t('admin_add_new_category_btn') }}
                    </button>
                </div>
            </div>

            <!-- Categories Data Table -->
            <div v-if="filteredCategories.length > 0" class="bg-white rounded-2xl border border-[#E6E1DA] shadow-xs overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest w-12 text-center">{{ t('admin_reviews_no_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">{{ t('admin_category_name_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">{{ t('admin_dishes_count_col') }}</th>
                                <th class="px-2.5 sm:px-4 py-2 text-[8px] sm:text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">{{ t('admin_actions_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA] text-[11px] sm:text-xs text-[#5C6460]">
                            <tr v-for="(cat, index) in paginatedCategories" :key="cat.id" class="hover:bg-[#FAFAF9] transition-colors">
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center font-semibold text-[#8C8275]">
                                    {{ (categoryCurrentPage - 1) * categoriesPerPage + index + 1 }}
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-[#4A6B5D]/10 text-[#4A6B5D] flex items-center justify-center text-[9px] shrink-0 border border-[#4A6B5D]/20">
                                            <i class="fas fa-folder text-[8px]"></i>
                                        </div>
                                        <span class="font-bold text-[#2D3330] uppercase tracking-wide">{{ cat.name }}</span>
                                    </div>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-center">
                                    <span class="font-semibold px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-800 border-emerald-200 text-[9px] sm:text-[10px] whitespace-nowrap">
                                        {{ dishes.filter(d => d.category === cat.name).length }} {{ currentLanguage === 'en' ? 'Dishes' : 'Hidangan' }}
                                    </span>
                                </td>
                                <td class="px-2.5 sm:px-4 py-1.5 sm:py-2.5 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button
                                            @click="openEditCategory(cat)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold w-7 h-7 rounded-lg transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
                                            :title="t('admin_edit_category')"
                                        >
                                            <i class="fas fa-edit text-[9px] sm:text-[10px]"></i>
                                        </button>
                                        <button
                                            @click="deleteCategory(cat.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold w-7 h-7 rounded-lg transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
                                            :title="t('admin_delete')"
                                        >
                                            <i class="fas fa-trash-alt text-[9px] sm:text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Categories -->
                <div v-if="filteredCategories.length > 0" class="flex justify-between items-center p-3 border-t border-[#E6E1DA]">
                    <button 
                        @click="categoryCurrentPage = Math.max(1, categoryCurrentPage - 1)"
                        :disabled="categoryCurrentPage === 1"
                        class="px-2.5 py-1.5 border border-[#E6E1DA] rounded-xl text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="categoryCurrentPage === 1 ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <i class="fas fa-chevron-left text-[8px]"></i>
                        <span>{{ t('admin_prev_page') }}</span>
                    </button>
                    
                    <span class="text-[10px] sm:text-xs font-semibold text-[#8C8275]">
                        {{ categoryCurrentPage }} / {{ categoryTotalPages }}
                    </span>
                    
                    <button 
                        @click="categoryCurrentPage = Math.min(categoryTotalPages, categoryCurrentPage + 1)"
                        :disabled="categoryCurrentPage === categoryTotalPages"
                        class="px-2.5 py-1.5 border border-[#E6E1DA] rounded-xl text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 focus:outline-none"
                        :class="categoryCurrentPage === categoryTotalPages ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-[#5C6460] bg-white hover:bg-[#FAF7F2] cursor-pointer'"
                    >
                        <span>{{ t('admin_next_page') }}</span>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                    </button>
                </div>
            </div>

            <!-- Empty Categories State / No matches -->
            <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-4">
                <div class="w-16 h-16 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-2xl flex items-center justify-center mx-auto text-2xl">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h4 class="text-[#2D3330] font-bold">{{ t('admin_no_categories_configured') }}</h4>
                    <p class="text-xs text-[#8C8275] mt-1">{{ categorySearchQuery ? t('admin_no_packages_matching_filter') : t('admin_get_started_cat_desc') }}</p>
                </div>
                <button v-if="!categorySearchQuery" @click="openCreateCategory" class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm">
                    <i class="fas fa-plus"></i> {{ t('admin_create_first_category_btn') }}
                </button>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!--  MODAL: CREATE / EDIT PACKAGE                                      -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showPackageModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#1B2A22]/60 backdrop-blur-sm" @click.self="closePackageModal">
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                >
                    <div v-if="showPackageModal" class="bg-white rounded-3xl w-full max-w-xl max-h-[90vh] overflow-y-auto border border-[#E6E1DA] shadow-2xl flex flex-col">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingPackage ? t('admin_edit_package_details') : t('admin_create_new_package') }}
                                </h3>
                                <p class="text-[9px] text-[#8C8275] font-semibold mt-0.5">
                                    {{ t('admin_package_modal_desc') }}
                                </p>
                            </div>
                            <button @click="closePackageModal"
                                class="w-7 h-7 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitPackage" class="flex flex-col flex-grow">
                            <div class="p-5 space-y-3">
                                <!-- Package Name -->
                                <div class="space-y-1">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_package_category_name') }} <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        v-model="packageForm.package_name"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                        :placeholder="t('admin_pkg_name_placeholder')"
                                        required
                                    />
                                    <p v-if="packageForm.errors.package_name" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.package_name }}</p>
                                </div>

                                <!-- Price + Min Order -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_base_price_pax_rm') }} <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[9px] font-bold text-[#8C8275]">RM</span>
                                            <input
                                                type="number" step="0.01"
                                                v-model="packageForm.price"
                                                class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] pl-8 pr-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                                placeholder="0.00" required
                                            />
                                        </div>
                                        <p v-if="packageForm.errors.price" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.price }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_min_order_pax') }} <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-bold text-[#8C8275]">pax</span>
                                            <input
                                                type="number"
                                                v-model="packageForm.min_order"
                                                class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 pr-9 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                                placeholder="100" required
                                            />
                                        </div>
                                        <p v-if="packageForm.errors.min_order" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.min_order }}</p>
                                    </div>
                                </div>

                                <!-- Description / Dishes -->
                                <div class="space-y-1">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">
                                        {{ t('admin_included_dishes_list') }} <span class="text-rose-500">*</span>
                                        <span class="text-[#8C8275] font-normal normal-case ml-1">({{ t('admin_one_dish_per_line') }})</span>
                                    </label>
                                    <textarea
                                        v-model="packageForm.description"
                                        rows="3"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all resize-none"
                                        :placeholder="t('admin_included_dishes_placeholder')"
                                        required
                                    ></textarea>
                                    <p v-if="packageForm.errors.description" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.description }}</p>
                                </div>

                                <!-- Package Image Upload -->
                                <div class="space-y-1">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">
                                        {{ currentLanguage === 'en' ? 'Package Image' : 'Gambar Pakej' }}
                                        <span class="text-[#8C8275] font-normal normal-case ml-1">({{ currentLanguage === 'en' ? 'Optional, Max 2MB' : 'Pilihan, Maksimum 2MB' }})</span>
                                    </label>
                                    
                                    <div class="flex items-center gap-3">
                                        <!-- Thumbnail Preview of current or selected image -->
                                        <div class="w-12 h-12 rounded-xl border border-[#E6E1DA] overflow-hidden bg-[#FAF8F5] shrink-0 flex items-center justify-center">
                                            <img v-if="imagePreviewUrl" :src="imagePreviewUrl" class="w-full h-full object-cover" />
                                            <img v-else :src="getPackageImage({ image: currentPackageImage, package_name: packageForm.package_name })" class="w-full h-full object-cover" />
                                        </div>
                                        
                                        <!-- File picker -->
                                        <div class="flex-grow">
                                            <div class="relative flex items-center">
                                                <input
                                                    id="package-image-input"
                                                    type="file"
                                                    accept="image/*"
                                                    @change="handleImageChange"
                                                    class="hidden"
                                                />
                                                <label
                                                    for="package-image-input"
                                                    class="cursor-pointer bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3 py-1.5 rounded-xl text-[10px] sm:text-xs transition-colors flex items-center gap-1.5"
                                                >
                                                    <i class="fas fa-upload text-[9px] sm:text-[10px]"></i>
                                                    {{ currentLanguage === 'en' ? 'Choose Image' : 'Pilih Gambar' }}
                                                </label>
                                                <button
                                                    v-if="imagePreviewUrl"
                                                    type="button"
                                                    @click="clearSelectedImage"
                                                    class="ml-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold px-2.5 py-1.5 rounded-xl text-[10px] sm:text-xs transition-colors flex items-center gap-1 cursor-pointer"
                                                >
                                                    <i class="fas fa-times text-[9px] sm:text-[10px]"></i>
                                                    {{ currentLanguage === 'en' ? 'Clear' : 'Batal' }}
                                                </button>
                                            </div>
                                            <p class="text-[9px] text-[#8C8275] mt-0.5">
                                                {{ currentLanguage === 'en' ? 'Supports PNG, JPG, JPEG up to 2MB.' : 'Sokong PNG, JPG, JPEG sehingga 2MB.' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p v-if="packageForm.errors.image" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.image }}</p>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-[#E6E1DA] pt-3">
                                    <span class="text-[10px] sm:text-xs font-bold text-[#2D3330] uppercase tracking-wider block mb-1">{{ t('admin_interactive_menu_options') }}</span>
                                    <p class="text-[9px] text-[#8C8275] font-semibold mb-2.5">{{ t('admin_interactive_menu_options_desc') }}</p>
                                </div>

                                <!-- Dish Limits Grid -->
                                <div class="space-y-1.5">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_dish_selection_limits') }}</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div v-for="cat in dishCategories" :key="cat" class="space-y-0.5">
                                            <span class="text-[9px] font-bold text-[#8C8275] uppercase block truncate">{{ cat }}</span>
                                            <input
                                                type="number"
                                                v-model="packageForm.dish_limits[cat]"
                                                min="0"
                                                class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                                placeholder="0"
                                            />
                                        </div>
                                    </div>
                                    <p v-if="packageForm.errors.dish_limits" class="text-xs text-rose-500 font-semibold mt-1">{{ packageForm.errors.dish_limits }}</p>
                                </div>

                                <!-- Dishes Checklist -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_select_available_dishes') }}</label>
                                        <button 
                                            type="button" 
                                            @click="toggleSelectAllDishes" 
                                            class="text-[9px] sm:text-[10px] font-bold text-[#4A6B5D] hover:text-[#3D574B] hover:underline cursor-pointer focus:outline-none"
                                        >
                                            {{ isAllDishesSelected ? (currentLanguage === 'en' ? 'Deselect All' : 'Nyahpilih Semua') : (currentLanguage === 'en' ? 'Select All' : 'Pilih Semua') }}
                                        </button>
                                    </div>
                                    <div class="space-y-3 max-h-48 overflow-y-auto border border-[#E6E1DA] rounded-xl p-3 bg-[#FAF7F2]/40">
                                        <div v-for="cat in dishCategories" :key="cat" class="space-y-1.5">
                                            <div class="flex items-center justify-between border-b border-[#E6E1DA] pb-0.5 mb-1">
                                                <span class="text-[9px] font-extrabold text-[#4A6B5D] uppercase tracking-wider">{{ cat }} (Limit: {{ packageForm.dish_limits[cat] || 0 }})</span>
                                                <div class="flex items-center gap-1.5">
                                                    <button 
                                                        type="button" 
                                                        @click="selectDishesInCategory(cat)" 
                                                        class="text-[8px] sm:text-[9px] font-bold text-[#4A6B5D] hover:text-[#3D574B] hover:underline cursor-pointer focus:outline-none"
                                                    >
                                                        {{ currentLanguage === 'en' ? 'Select All' : 'Pilih Semua' }}
                                                    </button>
                                                    <span class="text-[8px] text-[#D1C8BD] font-normal">|</span>
                                                    <button 
                                                        type="button" 
                                                        @click="clearDishesInCategory(cat)" 
                                                        class="text-[8px] sm:text-[9px] font-bold text-rose-500 hover:text-rose-700 hover:underline cursor-pointer focus:outline-none"
                                                    >
                                                        {{ currentLanguage === 'en' ? 'Clear' : 'Kosongkan' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                                <label 
                                                    v-for="dish in dishes.filter(d => d.category === cat && d.active)" 
                                                    :key="dish.id" 
                                                    class="flex items-center gap-1.5 text-xs text-[#5C6460] cursor-pointer"
                                                >
                                                    <input 
                                                        type="checkbox" 
                                                        :value="dish.id" 
                                                        v-model="packageForm.dishes" 
                                                        class="rounded border-[#E6E1DA] text-[#4A6B5D] focus:ring-[#4A6B5D] w-3.5 h-3.5"
                                                    />
                                                    <span class="text-[11px] sm:text-xs">{{ dish.name }}</span>
                                                </label>
                                            </div>
                                            <span v-if="dishes.filter(d => d.category === cat && d.active).length === 0" class="text-[9px] sm:text-[10px] text-[#8C8275] italic block">
                                                {{ t('admin_no_active_dishes_in_cat') }}
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="packageForm.errors.dishes" class="text-xs text-rose-500 font-semibold mt-1">{{ packageForm.errors.dishes }}</p>
                                </div>
                            </div>

                            <!-- Modal Footer Actions -->
                            <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-5 py-3.5 flex items-center justify-end gap-2.5 rounded-b-3xl">
                                <button type="button" @click="closePackageModal"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3.5 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer focus:outline-none">
                                    {{ t('cancel') }}
                                </button>
                                <button type="submit"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-4 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-1.5 focus:outline-none"
                                    :disabled="packageForm.processing">
                                    <i class="fas fa-save text-[9px] sm:text-[10px]"></i>
                                    {{ isEditingPackage ? t('save_changes') : t('admin_create_package_btn') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!--  MODAL: CREATE / EDIT GLOBAL ADDON                                 -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showAddonModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#1B2A22]/60 backdrop-blur-sm" @click.self="closeAddonModal">
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                >
                    <div v-if="showAddonModal" class="bg-white rounded-3xl w-full max-w-md border border-[#E6E1DA] shadow-2xl flex flex-col">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingAddon ? t('admin_edit_global_addon') : t('admin_add_new_global_addon') }}
                                </h3>
                                <p class="text-[9px] text-[#8C8275] font-semibold mt-0.5">
                                    {{ t('admin_addon_modal_desc') }}
                                </p>
                            </div>
                            <button @click="closeAddonModal"
                                class="w-7 h-7 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitAddon" class="flex flex-col">
                            <div class="p-5 space-y-3">
                                <!-- Addon Name -->
                                <div class="space-y-1">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_addon_item_name') }} <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        v-model="addonForm.addon_name"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                        :placeholder="t('admin_addon_name_placeholder')"
                                        required
                                    />
                                    <p v-if="addonForm.errors.addon_name" class="text-xs text-rose-500 font-semibold">{{ addonForm.errors.addon_name }}</p>
                                </div>

                                <!-- Price -->
                                <div class="space-y-1">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_extra_price_pax_rm') }} <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[9px] font-bold text-[#8C8275]">RM</span>
                                        <input
                                            type="number" step="0.01"
                                            v-model="addonForm.price_per_pax"
                                            class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] pl-8 pr-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                            placeholder="0.00" required
                                        />
                                    </div>
                                    <p v-if="addonForm.errors.price_per_pax" class="text-xs text-rose-500 font-semibold">{{ addonForm.errors.price_per_pax }}</p>
                                </div>

                                <!-- Toggle Status -->
                                <div v-if="isEditingAddon" class="flex items-center justify-between bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-3 py-2">
                                    <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#5C6460]">{{ t('admin_active_status') }}</div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="addonForm.active" class="sr-only peer" />
                                        <div class="w-8 h-4 bg-[#E6E1DA] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#4A6B5D]"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Modal Footer Actions -->
                            <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-5 py-3.5 flex items-center justify-end gap-2.5 rounded-b-3xl">
                                <button type="button" @click="closeAddonModal"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3.5 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer focus:outline-none">
                                    {{ t('cancel') }}
                                </button>
                                <button type="submit"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-4 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-1.5 focus:outline-none"
                                    :disabled="addonForm.processing">
                                    <i class="fas fa-save text-[9px] sm:text-[10px]"></i>
                                    {{ isEditingAddon ? t('save_changes') : t('admin_add_item') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!--  MODAL: CREATE / EDIT GLOBAL DISH                                  -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showDishModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#1B2A22]/60 backdrop-blur-sm" @click.self="closeDishModal">
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                >
                    <div v-if="showDishModal" class="bg-white rounded-3xl w-full max-w-md border border-[#E6E1DA] shadow-2xl flex flex-col">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingDish ? t('admin_edit_dish_details') : t('admin_add_new_dish') }}
                                </h3>
                                <p class="text-[9px] text-[#8C8275] font-semibold mt-0.5">
                                    {{ t('admin_dish_modal_desc') }}
                                </p>
                            </div>
                            <button @click="closeDishModal"
                                class="w-7 h-7 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitDish" class="flex flex-col">
                             <div class="p-5 space-y-3">
                                 <!-- Dish Name -->
                                 <div class="space-y-1">
                                     <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_dish_name_label') }} <span class="text-rose-500">*</span></label>
                                     <input
                                         type="text"
                                         v-model="dishForm.name"
                                         class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                         :placeholder="t('admin_dish_name_placeholder')"
                                         required
                                     />
                                     <p v-if="dishForm.errors.name" class="text-xs text-rose-500 font-semibold">{{ dishForm.errors.name }}</p>
                                 </div>

                                 <!-- Category Dropdown -->
                                 <div class="space-y-1">
                                     <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_category_col') }} <span class="text-rose-500">*</span></label>
                                     <select
                                         v-model="dishForm.category"
                                         class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all cursor-pointer"
                                         required
                                     >
                                         <option v-for="cat in dishCategories" :key="cat" :value="cat">{{ cat }}</option>
                                     </select>
                                     <p v-if="dishForm.errors.category" class="text-xs text-rose-500 font-semibold">{{ dishForm.errors.category }}</p>
                                 </div>

                                 <!-- Toggle Status -->
                                 <div v-if="isEditingDish" class="flex items-center justify-between bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-3 py-2">
                                     <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#5C6460]">{{ t('admin_active_status') }}</div>
                                     <label class="relative inline-flex items-center cursor-pointer">
                                         <input type="checkbox" v-model="dishForm.active" class="sr-only peer" />
                                         <div class="w-8 h-4 bg-[#E6E1DA] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#4A6B5D]"></div>
                                     </label>
                                 </div>
                             </div>

                             <!-- Modal Footer Actions -->
                             <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-5 py-3.5 flex items-center justify-end gap-2.5 rounded-b-3xl">
                                 <button type="button" @click="closeDishModal"
                                     class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3.5 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer focus:outline-none">
                                     {{ t('cancel') }}
                                 </button>
                                 <button type="submit"
                                     class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-4 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-1.5 focus:outline-none"
                                     :disabled="dishForm.processing">
                                     <i class="fas fa-save text-[9px] sm:text-[10px]"></i>
                                     {{ isEditingDish ? t('save_changes') : t('admin_add_dish') }}
                                 </button>
                             </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!--  MODAL: CREATE / EDIT CATEGORY                                     -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#1B2A22]/60 backdrop-blur-sm" @click.self="closeCategoryModal">
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                >
                    <div v-if="showCategoryModal" class="bg-white rounded-3xl w-full max-w-md border border-[#E6E1DA] shadow-2xl flex flex-col">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingCategory ? t('admin_edit_category') : t('admin_add_new_category') }}
                                </h3>
                                <p class="text-[9px] text-[#8C8275] font-semibold mt-0.5">
                                    {{ t('admin_category_modal_desc') }}
                                </p>
                            </div>
                            <button @click="closeCategoryModal"
                                class="w-7 h-7 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitCategory" class="flex flex-col">
                            <div class="p-5 space-y-3">
                                <!-- Category Name -->
                                <div class="space-y-1">
                                    <label class="text-[10px] sm:text-xs font-bold text-[#5C6460] block uppercase tracking-wider">{{ t('admin_category_name_label') }} <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        v-model="categoryForm.name"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-1.5 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                        :placeholder="t('admin_category_placeholder')"
                                        required
                                    />
                                    <p v-if="categoryForm.errors.name" class="text-xs text-rose-500 font-semibold">{{ categoryForm.errors.name }}</p>
                                </div>
                            </div>

                            <!-- Modal Footer Actions -->
                            <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-5 py-3.5 flex items-center justify-end gap-2.5 rounded-b-3xl">
                                <button type="button" @click="closeCategoryModal"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-3.5 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest transition-colors cursor-pointer focus:outline-none">
                                    {{ t('cancel') }}
                                </button>
                                <button type="submit"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-4 py-1.5 rounded-xl text-[10px] sm:text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-1.5 focus:outline-none"
                                    :disabled="categoryForm.processing">
                                    <i class="fas fa-save text-[9px] sm:text-[10px]"></i>
                                    {{ isEditingCategory ? t('save_changes') : t('admin_add_category') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
