<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    package: {
        type: Object,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
    cartItem: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        required: true,
    },
});

const { t, currentLanguage } = useLocalization();

// Setup reactive quantity/pax
const quantity = ref(
    (new URLSearchParams(window.location.search).get('pax') 
        ? parseInt(new URLSearchParams(window.location.search).get('pax')) 
        : (props.package.min_order || 20))
);

// Selected Addon IDs
const selectedAddonIds = ref([]);
if (new URLSearchParams(window.location.search).get('addons')) {
    selectedAddonIds.value = new URLSearchParams(window.location.search).get('addons').split(',').map(Number);
}

// Selected Dish IDs grouped by category
const selectedDishIds = ref({});
props.categories.forEach(cat => {
    selectedDishIds.value[cat] = [];
});

watch(() => props.cartItem, (newVal) => {
    if (newVal) {
        if (newVal.quantity) {
            quantity.value = newVal.quantity;
        }

        // Parse selected addons
        let addonsArray = [];
        if (newVal.selected_addons) {
            if (Array.isArray(newVal.selected_addons)) {
                addonsArray = newVal.selected_addons;
            } else if (typeof newVal.selected_addons === 'string') {
                try {
                    addonsArray = JSON.parse(newVal.selected_addons);
                } catch (e) {
                    addonsArray = [];
                }
            }
        }
        
        selectedAddonIds.value = [];
        if (props.package.addons) {
            props.package.addons.forEach(addon => {
                const formattedName = `${addon.addon_name} (+RM${parseFloat(addon.price_per_pax).toFixed(2)})`;
                if (addonsArray.includes(formattedName)) {
                    selectedAddonIds.value.push(addon.id);
                }
            });
        }

        // Parse selected dishes
        let dishesArray = [];
        if (newVal.selected_dishes) {
            if (Array.isArray(newVal.selected_dishes)) {
                dishesArray = newVal.selected_dishes;
            } else if (typeof newVal.selected_dishes === 'string') {
                try {
                    dishesArray = JSON.parse(newVal.selected_dishes);
                } catch (e) {
                    dishesArray = [];
                }
            }
        }

        // Reset
        Object.keys(selectedDishIds.value).forEach(category => {
            selectedDishIds.value[category] = [];
        });

        if (props.package.dishes) {
            props.package.dishes.forEach(dish => {
                const hasDish = dishesArray.some(
                    name => name.trim().toLowerCase() === dish.name.trim().toLowerCase()
                );
                if (hasDish) {
                    const category = dish.category;
                    if (!selectedDishIds.value[category]) {
                        selectedDishIds.value[category] = [];
                    }
                    if (!selectedDishIds.value[category].includes(dish.id)) {
                        selectedDishIds.value[category].push(dish.id);
                    }
                }
            });
        }
    }
}, { immediate: true });

// Group package dishes by category
const dishesByCategory = computed(() => {
    const grouped = {};
    if (props.package.dishes) {
        props.package.dishes.forEach(dish => {
            if (dish.active) {
                if (!grouped[dish.category]) {
                    grouped[dish.category] = [];
                }
                grouped[dish.category].push(dish);
            }
        });
    }
    return grouped;
});

function changeQty(delta) {
    let val = quantity.value + delta;
    if (val < (props.package.min_order || 20)) {
        val = props.package.min_order || 20;
    }
    quantity.value = val;
}

// Toggle addon selection
function toggleAddon(addonId) {
    const idx = selectedAddonIds.value.indexOf(addonId);
    if (idx > -1) {
        selectedAddonIds.value.splice(idx, 1);
    } else {
        selectedAddonIds.value.push(addonId);
    }
}

// Check if addon is selected
function isAddonSelected(addonId) {
    return selectedAddonIds.value.includes(addonId);
}

// Toggle dish selection enforcing category limits
function toggleDishSelection(dish) {
    const category = dish.category;
    const limit = parseInt(props.package.dish_limits?.[category] || 0);
    if (limit === 0) return;

    if (!selectedDishIds.value[category]) {
        selectedDishIds.value[category] = [];
    }

    const idx = selectedDishIds.value[category].indexOf(dish.id);
    if (idx > -1) {
        selectedDishIds.value[category].splice(idx, 1);
    } else {
        if (limit === 1) {
            selectedDishIds.value[category] = [dish.id];
        } else {
            if (selectedDishIds.value[category].length < limit) {
                selectedDishIds.value[category].push(dish.id);
            } else {
                selectedDishIds.value[category].shift();
                selectedDishIds.value[category].push(dish.id);
            }
        }
    }
    validationError.value = '';
}

function isDishSelected(dishId, category) {
    return selectedDishIds.value[category]?.includes(dishId) || false;
}

// Calculations
const basePrice = computed(() => parseFloat(props.package.price));
const minPax = computed(() => parseInt(props.package.min_order || 20));

const addonsCostPerPax = computed(() => {
    return props.package.addons
        .filter(addon => isAddonSelected(addon.id))
        .reduce((sum, addon) => sum + parseFloat(addon.price_per_pax), 0.00);
});

const totalPricePerPax = computed(() => {
    return basePrice.value + addonsCostPerPax.value;
});

const totalAmount = computed(() => {
    return totalPricePerPax.value * quantity.value;
});

const depositAmount = computed(() => {
    return totalAmount.value * 0.3;
});

const balanceAmount = computed(() => {
    return totalAmount.value * 0.7;
});

// Form Submission
const form = useForm({
    package_id: props.package.id,
    quantity: 100,
    addons: [],
    dishes: [],
    cart_id: props.cartItem?.id || null,
});

const showSuccessToast = ref(false);
const toastMessage = ref('');
const validationError = ref('');

function handleAddToCart() {
    // Validate selection limits
    const dishIds = [];
    const limits = props.package.dish_limits || {};
    for (const [category, limit] of Object.entries(limits)) {
        const limitNum = parseInt(limit);
        if (limitNum > 0) {
            const count = selectedDishIds.value[category]?.length || 0;
            if (count !== limitNum) {
                validationError.value = `Sila pilih tepat ${limitNum} hidangan untuk kategori "${category}".`;
                return;
            }
            dishIds.push(...selectedDishIds.value[category]);
        }
    }

    validationError.value = '';
    form.quantity = quantity.value;
    form.addons = selectedAddonIds.value;
    form.dishes = dishIds;
    form.cart_id = props.cartItem?.id || null;
    
    form.post(route('cart.addCustom'), {
        onSuccess: () => {
            toastMessage.value = props.cartItem 
                ? (t('toast_cart_updated') || 'Cart item updated successfully!')
                : (t('toast_added_to_cart') || 'Customized package added to cart successfully!');
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 3000);
        }
    });
}

function handleDownloadQuotation() {
    // Validate selection limits
    const dishIds = [];
    const limits = props.package.dish_limits || {};
    for (const [category, limit] of Object.entries(limits)) {
        const limitNum = parseInt(limit);
        if (limitNum > 0) {
            const count = selectedDishIds.value[category]?.length || 0;
            if (count !== limitNum) {
                validationError.value = `Sila pilih tepat ${limitNum} hidangan untuk kategori "${category}" sebelum memuat turun sebut harga.`;
                return;
            }
            dishIds.push(...selectedDishIds.value[category]);
        }
    }

    validationError.value = '';

    const addonsParam = selectedAddonIds.value.join(',');
    const dishesParam = dishIds.join(',');
    
    const url = route('menu.quotation', {
        package_id: props.package.id,
        quantity: quantity.value,
        addons: addonsParam,
        dishes: dishesParam,
    });
    
    window.open(url, '_blank');
}
</script>

<template>
    <Head :title="t('customize_order') + ' ' + package.package_name" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .section-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
        }
        .addon-card {
            border: 1px solid #E6E1DA;
            border-radius: 16px;
            padding: 16px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 14px;
            background: white;
        }
        .addon-card:hover {
            border-color: #4A6B5D;
        }
        .addon-card.selected {
            border-color: #4A6B5D;
            background-color: #FAF6F0;
        }
        .addon-check {
            width: 20px;
            height: 20px;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            background: white;
        }
        .addon-card.selected .addon-check {
            background: #4A6B5D;
            border-color: #4A6B5D;
            color: white;
        }
        .pax-btn {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            border: 1px solid #E6E1DA;
            background: #ffffff;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pax-btn:hover {
            background: #4A6B5D;
            color: white;
            border-color: #4A6B5D;
        }
        .price-summary {
            background: #FAF6F0;
            border: 1px solid #E6E1DA;
            border-radius: 16px;
            padding: 25px;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 8px 0;
            border-bottom: 1px solid #EBEFEF;
            color: #5C6460;
        }
        .price-row:last-child {
            border: none;
        }
        .price-row.total {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2D3330;
            border-top: 1px solid #E6E1DA;
            margin-top: 5px;
            padding-top: 12px;
        }
        .price-row.deposit {
            color: #8C3A3A;
            font-weight: 600;
        }
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #2D3330;
            color: #FAF7F2;
            border-left: 4px solid #4A6B5D;
            padding: 16px 24px;
            z-index: 100;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
        }
    </component>

    <AuthenticatedLayout
        :header-title="package.package_name"
        header-desc="Customise your catering package with add-ons, adjust pax count, and add to cart."
    >

        <div class="font-sans-modern">
            <div class="max-w-6xl mx-auto px-6">
                <!-- Back Link -->
                <div class="mb-4">
                    <Link 
                        :href="route('menu.show', { category: package.package_name })" 
                        class="inline-flex items-center gap-2 text-xs font-semibold text-[#8C8275] hover:text-[#4A6B5D] uppercase tracking-wider transition-colors"
                    >
                        <i class="fas fa-arrow-left text-[9px]"></i>
                        <span>{{ currentLanguage === 'en' ? 'Back to Packages' : 'Kembali ke Pakej' }}</span>
                    </Link>
                </div>
                
                <!-- Header Card -->
                <div class="bg-[#2D3330] text-[#FAF7F2] p-8 rounded-2xl mb-8 border border-[#E6E1DA]">
                    <span class="text-[#4A6B5D] text-[10px] font-bold uppercase tracking-widest block mb-1">{{ t('catering_packages') }}</span>
                    <h3 class="text-3xl font-normal font-serif-luxury uppercase tracking-wide">{{ package.package_name }}</h3>
                    <p class="text-[#E6E1DA] text-xs font-light mt-2 tracking-wide">{{ t('base_pkg_price') }}: RM {{ basePrice.toFixed(2) }} / {{ t('pax') }} &nbsp;·&nbsp; {{ t('min_requirement') }}: {{ minPax }} {{ t('pax') }}</p>
                </div>

                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left Columns (7 cols) -->
                    <div class="lg:col-span-7 space-y-8">
                        
                        <!-- Pax Selector -->
                        <div class="section-card">
                            <h4 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider mb-4 flex items-center gap-2">
                                <i class="fas fa-users text-[#4A6B5D] text-sm"></i> {{ t('select_guest_count') }}
                            </h4>
                            <div class="flex items-center gap-2">
                                <button type="button" class="pax-btn font-light" @click="changeQty(-10)">-10</button>
                                <button type="button" class="pax-btn font-light" @click="changeQty(-1)">-</button>
                                <input 
                                    type="number" 
                                    v-model="quantity" 
                                    :min="package.min_order || 20"
                                    class="w-32 text-center font-bold text-lg border border-[#E6E1DA] rounded-lg p-3 focus:outline-none focus:border-[#4A6B5D] focus:ring-0 transition-colors bg-white text-[#2D3330]"
                                />
                                <button type="button" class="pax-btn font-light" @click="changeQty(1)">+</button>
                                <button type="button" class="pax-btn font-light" @click="changeQty(10)">+10</button>
                            </div>
                            <small class="text-[#8C8275] text-[10px] uppercase tracking-wider mt-4 block">
                                <i class="fas fa-info-circle mr-1"></i> {{ t('min_booking_requirement_is') }} <strong>{{ package.min_order || 20 }} {{ t('pax') }}</strong>.
                            </small>
                        </div>
                        <!-- Interactive Dish Selector -->
                        <div class="space-y-6">
                            <div 
                                v-for="(catDishes, category) in dishesByCategory" 
                                :key="category"
                                class="section-card space-y-4"
                            >
                                <div class="flex justify-between items-center border-b border-[#E6E1DA] pb-3">
                                    <h4 class="text-base font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-utensils text-[#4A6B5D] text-xs"></i> Pilihan {{ category }}
                                    </h4>
                                    <span class="text-[9px] font-bold px-2.5 py-0.5 rounded-full border uppercase tracking-wider"
                                        :class="(selectedDishIds[category]?.length === parseInt(package.dish_limits[category])) 
                                            ? 'bg-emerald-50 text-[#4A6B5D] border-emerald-200' 
                                            : 'bg-amber-50 text-amber-800 border border-amber-200'"
                                    >
                                        Pilih {{ selectedDishIds[category]?.length || 0 }} dari {{ package.dish_limits[category] }}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div 
                                        v-for="dish in catDishes" 
                                        :key="dish.id"
                                        class="addon-card"
                                        :class="{ 'selected': isDishSelected(dish.id, category) }"
                                        @click="toggleDishSelection(dish)"
                                    >
                                        <div class="addon-check">
                                             <i v-if="isDishSelected(dish.id, category)" class="fas fa-check text-[10px]"></i>
                                        </div>
                                        <div class="flex-grow">
                                            <span class="font-bold text-xs text-[#2D3330] uppercase block">{{ dish.name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add-ons Selection -->
                        <div v-if="package.addons.length > 0" class="section-card">
                            <h4 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider mb-1 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-[#4A6B5D] text-sm"></i> {{ t('customize_extra_items') }}
                            </h4>
                            <p class="text-[#8C8275] text-[10px] uppercase tracking-wider mb-6">{{ t('addons_desc') }}</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div 
                                    v-for="addon in package.addons" 
                                    :key="addon.id"
                                    class="addon-card"
                                    :class="{ 'selected': isAddonSelected(addon.id) }"
                                    @click="toggleAddon(addon.id)"
                                >
                                    <div class="addon-check">
                                        <i v-if="isAddonSelected(addon.id)" class="fas fa-check text-[10px]"></i>
                                    </div>
                                    <div class="flex-grow">
                                        <span class="font-bold text-xs text-[#2D3330] uppercase block">{{ addon.addon_name }}</span>
                                        <span class="text-xs text-[#4A6B5D] font-semibold">+RM {{ parseFloat(addon.price_per_pax).toFixed(2) }} / {{ t('pax') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Price Summary (5 cols) -->
                    <div class="lg:col-span-5 sticky top-24">
                        <div class="section-card space-y-6">
                            <h4 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider mb-2">{{ t('price_breakdown') }}</h4>
                            
                            <div class="price-summary">
                                <div class="price-row">
                                    <span>{{ t('base_pkg_price') }}</span>
                                    <span>RM {{ basePrice.toFixed(2) }} / {{ t('pax') }}</span>
                                </div>
                                <div class="price-row">
                                    <span>{{ t('guest_count') }}</span>
                                    <span>{{ quantity }} {{ t('pax') }}</span>
                                </div>
                                <div v-if="addonsCostPerPax > 0" class="price-row">
                                    <span>{{ t('addon_extra') }}</span>
                                    <span class="text-[#4A6B5D] font-semibold">+RM {{ addonsCostPerPax.toFixed(2) }} / {{ t('pax') }}</span>
                                </div>
                                <div class="price-row">
                                    <span>{{ t('combined_cost_pax') }}</span>
                                    <span>RM {{ totalPricePerPax.toFixed(2) }} / {{ t('pax') }}</span>
                                </div>
                                <div class="price-row total flex justify-between items-center">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-[#8C8275]">{{ t('grand_total') }}:</span>
                                    <span class="text-2xl font-normal text-[#2D3330] font-serif-luxury tracking-wide">RM {{ totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>
                                <div class="price-row deposit flex justify-between items-center">
                                    <span>{{ t('deposit_required') }}</span>
                                    <span>RM {{ depositAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>
                                <div class="price-row text-[#8C8275] flex justify-between items-center">
                                    <span>{{ t('balance_due') }}</span>
                                    <span>RM {{ balanceAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>
                            </div>

                            <!-- List Selected Add-ons Summary -->
                            <div v-if="selectedAddonIds.length > 0" class="space-y-2">
                                <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('selected_extra_items') }}:</span>
                                <div class="p-4 bg-[#FAF6F0] rounded-lg border border-[#E6E1DA] space-y-1.5">
                                    <div 
                                        v-for="addon in package.addons.filter(a => isAddonSelected(a.id))" 
                                        :key="addon.id"
                                        class="flex justify-between items-center text-xs text-[#5C6460]"
                                    >
                                        <span><i class="fas fa-plus text-[#4A6B5D] mr-1 text-[8px]"></i> {{ addon.addon_name }}</span>
                                        <span class="text-[#8C8275] font-semibold">+RM {{ parseFloat(addon.price_per_pax).toFixed(2) }} / {{ t('pax') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- List Selected Dishes Summary -->
                            <div class="space-y-2">
                                <span class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">Menu Lauk Pilihan:</span>
                                <div class="p-4 bg-[#FAF6F0] rounded-lg border border-[#E6E1DA] space-y-2.5">
                                    <div v-for="cat in Object.keys(package.dish_limits || {})" :key="cat" class="text-xs">
                                        <div class="flex justify-between items-center">
                                            <span class="font-extrabold text-[10px] text-[#4A6B5D] uppercase tracking-wider">{{ cat }}</span>
                                            <span class="text-[8px] font-bold" :class="(selectedDishIds[cat]?.length === parseInt(package.dish_limits[cat])) ? 'text-emerald-600' : 'text-amber-600'">
                                                {{ selectedDishIds[cat]?.length || 0 }} / {{ package.dish_limits[cat] }}
                                            </span>
                                        </div>
                                        <div v-if="selectedDishIds[cat]?.length > 0" class="text-[#2D3330] font-semibold pl-2 mt-0.5">
                                            {{ package.dishes.filter(d => selectedDishIds[cat].includes(d.id)).map(d => d.name).join(', ') }}
                                        </div>
                                        <div v-else class="text-[#8C8275] italic pl-2 mt-0.5 text-[10px]">
                                            Belum dipilih
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Validation Error Box -->
                            <div v-if="validationError" class="p-3.5 bg-rose-50 border border-rose-200 text-rose-600 font-bold rounded-xl text-[11px] leading-relaxed flex items-center gap-2">
                                <i class="fas fa-exclamation-circle text-xs shrink-0"></i>
                                <span>{{ validationError }}</span>
                            </div>

                            <!-- Submit Action -->
                            <button 
                                @click="handleAddToCart"
                                class="w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3.5 px-6 rounded-lg text-xs uppercase tracking-widest transition-colors shadow-sm cursor-pointer"
                                :disabled="form.processing"
                            >
                                <i class="fas fa-cart-plus text-[10px]"></i> {{ t('add_to_cart') }}
                            </button>
                            <button 
                                @click="handleDownloadQuotation"
                                class="w-full inline-flex items-center justify-center gap-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 font-semibold py-3.5 px-6 rounded-lg text-xs uppercase tracking-widest transition-colors shadow-sm cursor-pointer"
                                :disabled="form.processing"
                            >
                                <i class="fas fa-file-pdf text-[10px] text-amber-700"></i> Download PDF Quote
                            </button>
                            <Link 
                                :href="route('menu.show', { category: package.package_name })"
                                class="w-full inline-flex items-center justify-center bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold py-2.5 px-6 rounded-lg text-xs uppercase tracking-widest transition-colors text-center"
                            >
                                <i class="fas fa-arrow-left mr-2"></i> {{ t('cancel') }}
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Success Toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="showSuccessToast" class="toast-notification font-sans-modern text-xs uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-check-circle text-[#EBEFEF]"></i>
                <span>{{ toastMessage }}</span>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
