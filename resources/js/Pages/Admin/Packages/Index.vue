<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

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

const packageForm = useForm({
    package_name : '',
    price        : '',
    min_order    : 100,
    description  : '',
    dish_limits  : {},
    dishes       : [],
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

function openCreatePackage() {
    isEditingPackage.value = false;
    editingPackageId.value = null;
    packageForm.package_name = '';
    packageForm.price        = '';
    packageForm.min_order    = 100;
    packageForm.description  = '';
    
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
    packageForm.package_name = pkg.package_name;
    packageForm.price        = pkg.price;
    packageForm.min_order    = pkg.min_order;
    packageForm.description  = pkg.description;
    
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
}

function submitPackage() {
    const route_name = isEditingPackage.value
        ? route('admin.packages.update', { id: editingPackageId.value })
        : route('admin.packages.store');

    packageForm.post(route_name, {
        onSuccess: () => {
            toast(isEditingPackage.value ? 'Package updated successfully.' : 'Package created successfully.');
            closePackageModal();
        }
    });
}

async function deletePackage(id) {
    if (await confirm('Are you sure you want to delete this catering package?', 'Delete Package')) {
        router.delete(route('admin.packages.delete', { id }), {
            onSuccess: () => toast('Package deleted successfully.')
        });
    }
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
            toast(isEditingAddon.value ? 'Global add-on updated successfully.' : 'Global add-on created successfully.');
            closeAddonModal();
        }
    });
}

async function deleteAddon(id) {
    if (await confirm('Are you sure you want to delete this global add-on? It will be removed from all packages.', 'Delete Add-on')) {
        router.delete(route('admin.addons.delete', { id }), {
            onSuccess: () => toast('Add-on deleted successfully.')
        });
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
            toast(`Add-on is now ${toggledActive ? 'Active' : 'Inactive'}.`);
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
            toast(isEditingDish.value ? 'Dish updated successfully.' : 'Dish created successfully.');
            closeDishModal();
        }
    });
}

async function deleteDish(id) {
    if (await confirm('Are you sure you want to delete this dish from the library? It will be removed from all packages.', 'Delete Dish')) {
        router.delete(route('admin.dishes.delete', { id }), {
            onSuccess: () => toast('Dish deleted successfully.')
        });
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
            toast(`Dish is now ${toggledActive ? 'Active' : 'Inactive'}.`);
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
            toast(isEditingCategory.value ? 'Category updated successfully.' : 'Category created successfully.');
            closeCategoryModal();
        }
    });
}

async function deleteCategory(id) {
    if (await confirm('Adakah anda pasti mahu memadam kategori ini?', 'Padam Kategori')) {
        router.delete(route('admin.categories.delete', { id }), {
            onSuccess: () => {
                toast('Kategori berjaya dipadam.');
            },
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
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding')) {
        return 'wedding';
    }
    if (lower.includes('corporate') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
        return 'corporate';
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir') || lower.includes('kenduri') || lower.includes('family')) {
        return 'aqiqah';
    }
    return 'other';
}

function getCategoryIcon(name) {
    const lower = name.toLowerCase();
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding')) {
        return {
            icon: 'fa-heart',
            colors: 'text-[#8C3A3A] bg-[#FDF2F2] border-[#FADCDD]'
        };
    }
    if (lower.includes('corporate') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
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

const activePackageFilter = ref('all');

const filteredPackages = computed(() => {
    if (activePackageFilter.value === 'all') return props.packages;
    return props.packages.filter(p => getCategoryKey(p.package_name) === activePackageFilter.value);
});
</script>

<template>
    <AdminLayout
        title="Catering Packages & Add-ons"
        header-title="Catering Packages & Add-ons"
        header-desc="Manage standard event menu packages and configure global custom add-on selections."
    >
        <!-- Tab Navigation Bar -->
        <div class="flex border-b border-[#E6E1DA] mb-6">
            <button
                @click="activeTab = 'packages'"
                class="px-6 py-3 text-xs uppercase tracking-wider font-bold border-b-2 transition-all cursor-pointer focus:outline-none"
                :class="activeTab === 'packages' ? 'border-[#4A6B5D] text-[#4A6B5D]' : 'border-transparent text-[#8C8275] hover:text-[#5C6460]'"
            >
                Catering Packages ({{ packages.length }})
            </button>
            <button
                @click="activeTab = 'addons'"
                class="px-6 py-3 text-xs uppercase tracking-wider font-bold border-b-2 transition-all cursor-pointer focus:outline-none"
                :class="activeTab === 'addons' ? 'border-[#4A6B5D] text-[#4A6B5D]' : 'border-transparent text-[#8C8275] hover:text-[#5C6460]'"
            >
                Add-on Options ({{ addons.length }})
            </button>
            <button
                @click="activeTab = 'dishes'"
                class="px-6 py-3 text-xs uppercase tracking-wider font-bold border-b-2 transition-all cursor-pointer focus:outline-none"
                :class="activeTab === 'dishes' ? 'border-[#4A6B5D] text-[#4A6B5D]' : 'border-transparent text-[#8C8275] hover:text-[#5C6460]'"
            >
                Dish Selections ({{ dishes.length }})
            </button>
            <button
                @click="activeTab = 'categories'"
                class="px-6 py-3 text-xs uppercase tracking-wider font-bold border-b-2 transition-all cursor-pointer focus:outline-none"
                :class="activeTab === 'categories' ? 'border-[#4A6B5D] text-[#4A6B5D]' : 'border-transparent text-[#8C8275] hover:text-[#5C6460]'"
            >
                Dish Categories ({{ categories.length }})
            </button>
        </div>

        <!-- TAB 1: CATERING PACKAGES -->
        <div v-if="activeTab === 'packages'" class="space-y-6">
            <!-- Action Bar -->
            <div class="flex justify-between items-center gap-3">
                <span class="text-xs text-[#8C8275] font-semibold">
                    Packages automatically offer all active global add-ons during customization.
                </span>
                <button
                    @click="openCreatePackage"
                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-3 rounded-xl text-xs uppercase tracking-widest flex items-center gap-2 shadow-md transition-all cursor-pointer shrink-0"
                >
                    <i class="fas fa-plus"></i> Create New Package
                </button>
            </div>

            <!-- Main Packages Panel (if any packages exist in database) -->
            <div v-if="packages.length > 0" class="space-y-6">
                <!-- Sub-tabs for Package Categories -->
                <div class="flex flex-wrap gap-2 pt-2 border-b border-[#FAF6F0] pb-4">
                    <button 
                        @click="activePackageFilter = 'all'" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none"
                        :class="activePackageFilter === 'all' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        All Packages ({{ packages.length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'wedding'" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none"
                        :class="activePackageFilter === 'wedding' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        Wedding ({{ packages.filter(p => getCategoryKey(p.package_name) === 'wedding').length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'corporate'" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none"
                        :class="activePackageFilter === 'corporate' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        Corporate ({{ packages.filter(p => getCategoryKey(p.package_name) === 'corporate').length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'aqiqah'" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none"
                        :class="activePackageFilter === 'aqiqah' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        Aqiqah & Family ({{ packages.filter(p => getCategoryKey(p.package_name) === 'aqiqah').length }})
                    </button>
                    <button 
                        @click="activePackageFilter = 'other'" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all duration-200 border cursor-pointer select-none focus:outline-none"
                        :class="activePackageFilter === 'other' ? 'bg-[#4A6B5D] text-white border-[#4A6B5D] shadow-xs' : 'bg-white text-[#8C8275] border-[#E6E1DA] hover:bg-[#FAF7F2]'"
                    >
                        Other ({{ packages.filter(p => getCategoryKey(p.package_name) === 'other').length }})
                    </button>
                </div>

                <!-- Packages list grid -->
                <div v-if="filteredPackages.length > 0" class="space-y-5">
                    <div
                        v-for="pkg in filteredPackages"
                        :key="pkg.id"
                        class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs hover:shadow-md transition-all duration-200 overflow-hidden animate-fade-in"
                    >
                        <div class="grid lg:grid-cols-12 gap-0">
                            <!-- Left: Package Info (7 cols) -->
                            <div class="lg:col-span-7 p-6 md:p-8 space-y-5">
                                <div class="flex flex-wrap justify-between items-start gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xs border shadow-sm shrink-0" :class="getCategoryIcon(pkg.package_name).colors">
                                            <i class="fas text-[12px]" :class="getCategoryIcon(pkg.package_name).icon"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-[#2D3330] tracking-wide uppercase font-serif-luxury">{{ pkg.package_name }}</h3>
                                            <p class="text-xs text-[#C5A880] font-bold mt-1.5 uppercase tracking-wider">
                                                RM {{ parseFloat(pkg.price).toFixed(2) }} / pax &nbsp;·&nbsp; Min order: {{ pkg.min_order }} pax
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button
                                            @click="openEditPackage(pkg)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold px-3.5 py-2 rounded-xl text-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <i class="fas fa-edit text-[10px]"></i> Edit
                                        </button>
                                        <button
                                            @click="deletePackage(pkg.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold px-3.5 py-2 rounded-xl text-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <i class="fas fa-trash-alt text-[10px]"></i> Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="border-t border-[#E6E1DA] pt-5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block">Menu Choices & Limits</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span 
                                                v-for="(limit, cat) in (pkg.dish_limits || {})" 
                                                :key="cat"
                                                class="text-[8px] font-bold bg-[#FAF6F0] text-[#5C6460] border border-[#E6E1DA] px-2.5 py-0.5 rounded-full"
                                            >
                                                {{ cat }}: {{ limit }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="pkg.dishes && pkg.dishes.length > 0" class="flex flex-wrap gap-1.5 text-xs">
                                        <span
                                            v-for="dish in pkg.dishes"
                                            :key="dish.id"
                                            class="inline-flex items-center gap-1 bg-[#FAF6F0]/40 text-[#5C6460] px-2 py-1 rounded-lg border border-[#E6E1DA] text-[10px] font-medium"
                                        >
                                            <i class="fas fa-utensils text-[9px] text-[#4A6B5D]"></i>
                                            {{ dish.name }} ({{ dish.category }})
                                        </span>
                                    </div>
                                    <div v-else class="text-xs text-[#8C8275] italic">
                                        No custom dishes associated with this package.
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Associated Global Add-ons Info (5 cols) -->
                            <div class="lg:col-span-5 bg-[#FAF7F2] border-l border-[#E6E1DA] p-6 flex flex-col justify-between gap-4">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Active Global Add-ons</span>
                                        <span class="text-[9px] font-bold text-[#4A6B5D] bg-[#4A6B5D]/10 px-2 py-0.5 rounded-full">
                                            {{ activeAddons.length }} Active Options
                                        </span>
                                    </div>

                                    <div v-if="activeAddons.length > 0" class="max-h-[160px] overflow-y-auto space-y-2 pr-1">
                                        <div
                                            v-for="addon in activeAddons"
                                            :key="addon.id"
                                            class="flex justify-between items-center text-xs text-[#5C6460] bg-white px-3.5 py-2 rounded-xl border border-[#E6E1DA] shadow-xs"
                                        >
                                            <span class="font-semibold truncate pr-2">{{ addon.addon_name }}</span>
                                            <span class="text-[#C5A880] font-bold text-[10px] shrink-0 ml-auto">+RM {{ parseFloat(addon.price_per_pax).toFixed(2) }}/pax</span>
                                        </div>
                                    </div>
                                    <div v-else class="py-4 text-center">
                                        <p class="text-xs text-[#B5AFA8] italic">No active global add-ons configured.</p>
                                    </div>
                                </div>

                                <button
                                    @click="activeTab = 'addons'"
                                    class="w-full flex items-center justify-center gap-2 border border-dashed border-[#4A6B5D]/40 text-[#4A6B5D] hover:bg-[#4A6B5D]/5 font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors cursor-pointer"
                                >
                                    <i class="fas fa-list text-[10px]"></i> Manage Add-ons
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State for filtered packages -->
                <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-16 text-center space-y-3">
                    <div class="w-12 h-12 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-xl flex items-center justify-center mx-auto text-lg">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-[#2D3330]">No packages matching this filter.</h4>
                        <p class="text-[11px] text-[#8C8275] mt-1">Try selecting another category or check the main packages tab.</p>
                    </div>
                </div>
            </div>

            <!-- Empty Packages State (when no packages exist at all in the system) -->
            <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-4">
                <div class="w-16 h-16 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-2xl flex items-center justify-center mx-auto text-2xl">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h4 class="text-[#2D3330] font-bold">No catering packages configured.</h4>
                    <p class="text-xs text-[#8C8275] mt-1">Get started by creating your first event catering menu package.</p>
                </div>
                <button @click="openCreatePackage" class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm">
                    <i class="fas fa-plus"></i> Create First Package
                </button>
            </div>
        </div>

        <!-- TAB 2: GLOBAL ADD-ONS LIBRARY -->
        <div v-if="activeTab === 'addons'" class="space-y-6">
            <!-- Action Bar -->
            <div class="flex justify-between items-center gap-3">
                <span class="text-xs text-[#8C8275] font-semibold">
                    Define and update add-on options. Any changes automatically apply to all packages.
                </span>
                <button
                    @click="openCreateAddon"
                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-3 rounded-xl text-xs uppercase tracking-widest flex items-center gap-2 shadow-md transition-all cursor-pointer shrink-0"
                >
                    <i class="fas fa-plus"></i> Add New Global Item
                </button>
            </div>

            <!-- Add-ons Data Table -->
            <div v-if="addons.length > 0" class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Add-on Item</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Price / Pax</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA] text-xs text-[#5C6460]">
                            <tr v-for="addon in addons" :key="addon.id" class="hover:bg-[#FAFAF9] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-lg bg-[#C5A880]/10 text-[#C5A880] flex items-center justify-center text-[10px] shrink-0 border border-[#C5A880]/20">
                                            <i class="fas fa-star text-[9px]"></i>
                                        </div>
                                        <span class="font-bold text-[#2D3330] uppercase tracking-wide">{{ addon.addon_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[#C5A880] font-extrabold font-serif-luxury text-sm">
                                        +RM {{ parseFloat(addon.price_per_pax).toFixed(2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        @click="toggleAddonStatus(addon)"
                                        class="inline-flex items-center gap-1.5 text-[9px] font-bold px-2.5 py-1 rounded-full border cursor-pointer transition-all"
                                        :class="addon.active 
                                            ? 'bg-emerald-50 text-[#4A6B5D] border-emerald-200 hover:bg-emerald-100' 
                                            : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        <i class="fas text-[7px]" :class="addon.active ? 'fa-check' : 'fa-times'"></i>
                                        {{ addon.active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            @click="openEditAddon(addon)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold w-8 h-8 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
                                            title="Edit Add-on"
                                        >
                                            <i class="fas fa-edit text-[10px]"></i>
                                        </button>
                                        <button
                                            @click="deleteAddon(addon.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold w-8 h-8 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
                                            title="Delete Add-on"
                                        >
                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty Add-ons State -->
            <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-4">
                <div class="w-16 h-16 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-2xl flex items-center justify-center mx-auto text-2xl">
                    <i class="fas fa-list-ul"></i>
                </div>
                <div>
                    <h4 class="text-[#2D3330] font-bold">No global add-ons configured.</h4>
                    <p class="text-xs text-[#8C8275] mt-1">Get started by creating your first global add-on choice.</p>
                </div>
                <button @click="openCreateAddon" class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm">
                    <i class="fas fa-plus"></i> Create First Add-on
                </button>
            </div>
        </div>

        <!-- TAB 3: DISHES LIBRARY -->
        <div v-if="activeTab === 'dishes'" class="space-y-6">
            <!-- Action Bar -->
            <div class="flex justify-between items-center gap-3">
                <span class="text-xs text-[#8C8275] font-semibold">
                    Define and update dishes. Any changes automatically apply to all packages.
                </span>
                <button
                    @click="openCreateDish"
                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-3 rounded-xl text-xs uppercase tracking-widest flex items-center gap-2 shadow-md transition-all cursor-pointer shrink-0"
                >
                    <i class="fas fa-plus"></i> Add New Dish
                </button>
            </div>

            <!-- Dishes Data Table -->
            <div v-if="dishes.length > 0" class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Dish Name</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Category</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA] text-xs text-[#5C6460]">
                            <tr v-for="dish in dishes" :key="dish.id" class="hover:bg-[#FAFAF9] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-lg bg-[#4A6B5D]/10 text-[#4A6B5D] flex items-center justify-center text-[10px] shrink-0 border border-[#4A6B5D]/20">
                                            <i class="fas fa-utensils text-[9px]"></i>
                                        </div>
                                        <span class="font-bold text-[#2D3330] uppercase tracking-wide">{{ dish.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200">
                                        {{ dish.category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        @click="toggleDishStatus(dish)"
                                        class="inline-flex items-center gap-1.5 text-[9px] font-bold px-2.5 py-1 rounded-full border cursor-pointer transition-all"
                                        :class="dish.active 
                                            ? 'bg-emerald-50 text-[#4A6B5D] border-emerald-200 hover:bg-emerald-100' 
                                            : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        <i class="fas text-[7px]" :class="dish.active ? 'fa-check' : 'fa-times'"></i>
                                        {{ dish.active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            @click="openEditDish(dish)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold w-8 h-8 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
                                            title="Edit Dish"
                                        >
                                            <i class="fas fa-edit text-[10px]"></i>
                                        </button>
                                        <button
                                            @click="deleteDish(dish.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold w-8 h-8 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
                                            title="Delete Dish"
                                        >
                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty Dishes State -->
            <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-4">
                <div class="w-16 h-16 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-2xl flex items-center justify-center mx-auto text-2xl">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h4 class="text-[#2D3330] font-bold">No dishes configured.</h4>
                    <p class="text-xs text-[#8C8275] mt-1">Get started by creating your first dish choice.</p>
                </div>
                <button @click="openCreateDish" class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm">
                    <i class="fas fa-plus"></i> Create First Dish
                </button>
            </div>
        </div>

        <!-- TAB 4: DISH CATEGORIES -->
        <div v-if="activeTab === 'categories'" class="space-y-6">
            <!-- Action Bar -->
            <div class="flex justify-between items-center gap-3">
                <span class="text-xs text-[#8C8275] font-semibold">
                    Uruskan kategori makanan untuk digunakan di dalam pakej katering dan senarai hidangan.
                </span>
                <button
                    @click="openCreateCategory"
                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-3 rounded-xl text-xs uppercase tracking-widest flex items-center gap-2 shadow-md transition-all cursor-pointer shrink-0"
                >
                    <i class="fas fa-plus"></i> Add New Category
                </button>
            </div>

            <!-- Categories Data Table -->
            <div v-if="categories.length > 0" class="bg-white rounded-3xl border border-[#E6E1DA] shadow-xs overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#FAF7F2] border-b border-[#E6E1DA]">
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Category Name</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest">Dishes Count</th>
                                <th class="px-6 py-4 text-[9px] font-bold text-[#8C8275] uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E6E1DA] text-xs text-[#5C6460]">
                            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-[#FAFAF9] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-lg bg-[#4A6B5D]/10 text-[#4A6B5D] flex items-center justify-center text-[10px] shrink-0 border border-[#4A6B5D]/20">
                                            <i class="fas fa-folder text-[9px]"></i>
                                        </div>
                                        <span class="font-bold text-[#2D3330] uppercase tracking-wide">{{ cat.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold px-2.5 py-0.5 rounded-full border bg-emerald-50 text-emerald-800 border-emerald-200">
                                        {{ dishes.filter(d => d.category === cat.name).length }} Dishes
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            @click="openEditCategory(cat)"
                                            class="bg-[#FAF7F2] hover:bg-[#E6E1DA] border border-[#E6E1DA] text-[#5C6460] font-bold w-8 h-8 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
                                            title="Edit Category"
                                        >
                                            <i class="fas fa-edit text-[10px]"></i>
                                        </button>
                                        <button
                                            @click="deleteCategory(cat.id)"
                                            class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold w-8 h-8 rounded-lg transition-colors flex items-center justify-center cursor-pointer"
                                            title="Delete Category"
                                        >
                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty Categories State -->
            <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] p-20 text-center space-y-4">
                <div class="w-16 h-16 bg-[#FAF7F2] text-[#8C8275] border border-[#E6E1DA] rounded-2xl flex items-center justify-center mx-auto text-2xl">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h4 class="text-[#2D3330] font-bold">Tiada kategori hidangan dikonfigurasikan.</h4>
                    <p class="text-xs text-[#8C8275] mt-1">Mula dengan mencipta kategori hidangan pertama anda.</p>
                </div>
                <button @click="openCreateCategory" class="inline-flex items-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer shadow-sm">
                    <i class="fas fa-plus"></i> Create First Category
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
                        <div class="flex items-center justify-between px-7 py-5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingPackage ? 'Edit Package Details' : 'Create New Package' }}
                                </h3>
                                <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5">
                                    Define base price, minimum guests, and included dishes list.
                                </p>
                            </div>
                            <button @click="closePackageModal"
                                class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitPackage" class="flex flex-col flex-grow">
                            <div class="p-7 space-y-4">
                                <!-- Package Name -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#5C6460] block">Package Category Name <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        v-model="packageForm.package_name"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                        placeholder="e.g. Wedding Set A / Aqiqah Standard"
                                        required
                                    />
                                    <p v-if="packageForm.errors.package_name" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.package_name }}</p>
                                </div>

                                <!-- Price + Min Order -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-[#5C6460] block">Base Price / Pax (RM) <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-[#8C8275]">RM</span>
                                            <input
                                                type="number" step="0.01"
                                                v-model="packageForm.price"
                                                class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] pl-9 pr-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                                placeholder="0.00" required
                                            />
                                        </div>
                                        <p v-if="packageForm.errors.price" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.price }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-[#5C6460] block">Min Order (Pax) <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-[#8C8275]">pax</span>
                                            <input
                                                type="number"
                                                v-model="packageForm.min_order"
                                                class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 pr-10 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                                placeholder="100" required
                                            />
                                        </div>
                                        <p v-if="packageForm.errors.min_order" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.min_order }}</p>
                                    </div>
                                </div>

                                <!-- Description / Dishes -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#5C6460] block">
                                        Included Dishes List <span class="text-rose-500">*</span>
                                        <span class="text-[#8C8275] font-normal ml-1">(one dish per line)</span>
                                    </label>
                                    <textarea
                                        v-model="packageForm.description"
                                        rows="5"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all resize-none"
                                        placeholder="Nasi Minyak&#10;Ayam Masak Merah&#10;Gulai Daging&#10;Acar Buah&#10;Air Sirap"
                                        required
                                    ></textarea>
                                    <p v-if="packageForm.errors.description" class="text-xs text-rose-500 font-semibold">{{ packageForm.errors.description }}</p>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-[#E6E1DA] pt-4">
                                    <span class="text-xs font-bold text-[#2D3330] uppercase tracking-wider block mb-2">Interactive Menu Options</span>
                                    <p class="text-[10px] text-[#8C8275] font-semibold mb-4">Set the limits and select which dishes from the library are available in this package.</p>
                                </div>

                                <!-- Dish Limits Grid -->
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-[#5C6460] block">Dish Selection Limits (per Category)</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div v-for="cat in dishCategories" :key="cat" class="space-y-1">
                                            <span class="text-[10px] font-bold text-[#8C8275] uppercase block">{{ cat }}</span>
                                            <input
                                                type="number"
                                                v-model="packageForm.dish_limits[cat]"
                                                min="0"
                                                class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-3 py-2 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                                placeholder="0"
                                            />
                                        </div>
                                    </div>
                                    <p v-if="packageForm.errors.dish_limits" class="text-xs text-rose-500 font-semibold mt-1">{{ packageForm.errors.dish_limits }}</p>
                                </div>

                                <!-- Dishes Checklist -->
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-[#5C6460] block">Select Available Dishes for this Package</label>
                                    <div class="space-y-4 max-h-60 overflow-y-auto border border-[#E6E1DA] rounded-xl p-4 bg-[#FAF7F2]/40">
                                        <div v-for="cat in dishCategories" :key="cat" class="space-y-2">
                                            <span class="text-[10px] font-extrabold text-[#4A6B5D] uppercase tracking-wider block border-b border-[#E6E1DA] pb-1">{{ cat }} (Limit: {{ packageForm.dish_limits[cat] || 0 }})</span>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <label 
                                                    v-for="dish in dishes.filter(d => d.category === cat && d.active)" 
                                                    :key="dish.id" 
                                                    class="flex items-center gap-2 text-xs text-[#5C6460] cursor-pointer"
                                                >
                                                    <input 
                                                        type="checkbox" 
                                                        :value="dish.id" 
                                                        v-model="packageForm.dishes" 
                                                        class="rounded border-[#E6E1DA] text-[#4A6B5D] focus:ring-[#4A6B5D]"
                                                    />
                                                    <span>{{ dish.name }}</span>
                                                </label>
                                            </div>
                                            <span v-if="dishes.filter(d => d.category === cat && d.active).length === 0" class="text-[10px] text-[#8C8275] italic block">
                                                No active dishes in this category.
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="packageForm.errors.dishes" class="text-xs text-rose-500 font-semibold mt-1">{{ packageForm.errors.dishes }}</p>
                                </div>
                            </div>

                            <!-- Modal Footer Actions -->
                            <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-7 py-5 flex items-center justify-end gap-3 rounded-b-3xl">
                                <button type="button" @click="closePackageModal"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-2"
                                    :disabled="packageForm.processing">
                                    <i class="fas fa-save text-[10px]"></i>
                                    {{ isEditingPackage ? 'Save Changes' : 'Create Package' }}
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
                        <div class="flex items-center justify-between px-7 py-5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingAddon ? 'Edit Global Add-on' : 'Add New Global Item' }}
                                </h3>
                                <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5">
                                    Provide the add-on name, surcharge price per guest, and status.
                                </p>
                            </div>
                            <button @click="closeAddonModal"
                                class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitAddon" class="flex flex-col">
                            <div class="p-7 space-y-4">
                                <!-- Addon Name -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#5C6460] block">Add-on Item Name <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        v-model="addonForm.addon_name"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                        placeholder="e.g. Teh Tarik Live Station / Kambing Bakar"
                                        required
                                    />
                                    <p v-if="addonForm.errors.addon_name" class="text-xs text-rose-500 font-semibold">{{ addonForm.errors.addon_name }}</p>
                                </div>

                                <!-- Price -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#5C6460] block">Extra Price / Pax (RM) <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-[#8C8275]">RM</span>
                                        <input
                                            type="number" step="0.01"
                                            v-model="addonForm.price_per_pax"
                                            class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] pl-9 pr-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                            placeholder="0.00" required
                                        />
                                    </div>
                                    <p v-if="addonForm.errors.price_per_pax" class="text-xs text-rose-500 font-semibold">{{ addonForm.errors.price_per_pax }}</p>
                                </div>

                                <!-- Toggle Status -->
                                <div v-if="isEditingAddon" class="flex items-center justify-between bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-4 py-3">
                                    <div class="text-xs font-semibold text-[#5C6460]">Active Status</div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="addonForm.active" class="sr-only peer" />
                                        <div class="w-9 h-5 bg-[#E6E1DA] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#4A6B5D]"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Modal Footer Actions -->
                            <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-7 py-5 flex items-center justify-end gap-3 rounded-b-3xl">
                                <button type="button" @click="closeAddonModal"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-2"
                                    :disabled="addonForm.processing">
                                    <i class="fas fa-save text-[10px]"></i>
                                    {{ isEditingAddon ? 'Save Changes' : 'Add Item' }}
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
                        <div class="flex items-center justify-between px-7 py-5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingDish ? 'Edit Dish details' : 'Add New Dish' }}
                                </h3>
                                <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5">
                                    Define the dish name, category, and status.
                                </p>
                            </div>
                            <button @click="closeDishModal"
                                class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitDish" class="flex flex-col">
                             <div class="p-7 space-y-4">
                                 <!-- Dish Name -->
                                 <div class="space-y-1.5">
                                     <label class="text-xs font-bold text-[#5C6460] block">Dish Name <span class="text-rose-500">*</span></label>
                                     <input
                                         type="text"
                                         v-model="dishForm.name"
                                         class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                         placeholder="e.g. Nasi Briyani / Ayam Masak Merah"
                                         required
                                     />
                                     <p v-if="dishForm.errors.name" class="text-xs text-rose-500 font-semibold">{{ dishForm.errors.name }}</p>
                                 </div>

                                 <!-- Category Dropdown -->
                                 <div class="space-y-1.5">
                                     <label class="text-xs font-bold text-[#5C6460] block">Category <span class="text-rose-500">*</span></label>
                                     <select
                                         v-model="dishForm.category"
                                         class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                         required
                                     >
                                         <option v-for="cat in dishCategories" :key="cat" :value="cat">{{ cat }}</option>
                                     </select>
                                     <p v-if="dishForm.errors.category" class="text-xs text-rose-500 font-semibold">{{ dishForm.errors.category }}</p>
                                 </div>

                                 <!-- Toggle Status -->
                                 <div v-if="isEditingDish" class="flex items-center justify-between bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl px-4 py-3">
                                     <div class="text-xs font-semibold text-[#5C6460]">Active Status</div>
                                     <label class="relative inline-flex items-center cursor-pointer">
                                         <input type="checkbox" v-model="dishForm.active" class="sr-only peer" />
                                         <div class="w-9 h-5 bg-[#E6E1DA] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#4A6B5D]"></div>
                                     </label>
                                 </div>
                             </div>

                             <!-- Modal Footer Actions -->
                             <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-7 py-5 flex items-center justify-end gap-3 rounded-b-3xl">
                                 <button type="button" @click="closeDishModal"
                                     class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer">
                                     Cancel
                                 </button>
                                 <button type="submit"
                                     class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-2"
                                     :disabled="dishForm.processing">
                                     <i class="fas fa-save text-[10px]"></i>
                                     {{ isEditingDish ? 'Save Changes' : 'Add Dish' }}
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
                        <div class="flex items-center justify-between px-7 py-5 border-b border-[#E6E1DA] bg-[#FAF7F2]">
                            <div>
                                <h3 class="text-sm font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                    {{ isEditingCategory ? 'Edit Category' : 'Add New Category' }}
                                </h3>
                                <p class="text-[10px] text-[#8C8275] font-semibold mt-0.5">
                                    Provide a unique category name for dishes grouping.
                                </p>
                            </div>
                            <button @click="closeCategoryModal"
                                class="w-8 h-8 rounded-xl border border-[#E6E1DA] flex items-center justify-center text-[#8C8275] hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer shrink-0">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitCategory" class="flex flex-col">
                            <div class="p-7 space-y-4">
                                <!-- Category Name -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-[#5C6460] block">Category Name <span class="text-rose-500">*</span></label>
                                    <input
                                        type="text"
                                        v-model="categoryForm.name"
                                        class="w-full rounded-xl border border-[#E6E1DA] bg-[#FAF7F2] text-[#2D3330] px-4 py-3 text-xs focus:ring-2 focus:ring-[#4A6B5D]/20 focus:border-[#4A6B5D] outline-none transition-all"
                                        placeholder="e.g. Pencuci Mulut / Sambal"
                                        required
                                    />
                                    <p v-if="categoryForm.errors.name" class="text-xs text-rose-500 font-semibold">{{ categoryForm.errors.name }}</p>
                                </div>
                            </div>

                            <!-- Modal Footer Actions -->
                            <div class="border-t border-[#E6E1DA] bg-[#FAF7F2]/50 px-7 py-5 flex items-center justify-end gap-3 rounded-b-3xl">
                                <button type="button" @click="closeCategoryModal"
                                    class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] disabled:opacity-60 text-white font-bold px-5 py-2.5 rounded-xl text-xs uppercase tracking-widest shadow transition-colors cursor-pointer flex items-center gap-2"
                                    :disabled="categoryForm.processing">
                                    <i class="fas fa-save text-[10px]"></i>
                                    {{ isEditingCategory ? 'Save Changes' : 'Add Category' }}
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
