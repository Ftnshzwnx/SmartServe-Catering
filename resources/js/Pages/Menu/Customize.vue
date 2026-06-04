<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
});

const { t } = useLocalization();

// Setup reactive quantity/pax
const quantity = ref(props.package.min_order);

// Selected Addon IDs
const selectedAddonIds = ref([]);

function changeQty(delta) {
    let val = quantity.value + delta;
    if (val < props.package.min_order) {
        val = props.package.min_order;
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

// Calculations
const basePrice = computed(() => parseFloat(props.package.price));
const minPax = computed(() => parseInt(props.package.min_order));

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
});

const showSuccessToast = ref(false);
const toastMessage = ref('');

function handleAddToCart() {
    form.quantity = quantity.value;
    form.addons = selectedAddonIds.value;
    
    form.post(route('cart.addCustom'), {
        onSuccess: () => {
            toastMessage.value = t('toast_added_to_cart') || 'Customized package added to cart successfully!';
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 3000);
        }
    });
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between font-sans-modern">
                <!-- Breadcrumbs -->
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#8C8275] font-bold uppercase tracking-widest mb-1">
                        <Link :href="route('menu.index')" class="hover:text-[#4A6B5D] transition-colors">{{ t('menu') }}</Link>
                        <span class="mx-2 text-[#E6E1DA]">/</span>
                        <Link :href="route('menu.show', { category: package.package_name })" class="hover:text-[#4A6B5D] transition-colors">{{ package.package_name }}</Link>
                        <span class="mx-2 text-[#E6E1DA]">/</span>
                        <span class="text-[#4A6B5D]">{{ t('cancel') }}</span>
                    </span>
                    <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                        {{ t('customize_order') }}
                    </h2>
                </div>

                <!-- Cart Link in Header -->
                <Link 
                    :href="route('cart.index')" 
                    class="relative flex items-center justify-center w-10 h-10 rounded-lg border border-[#E6E1DA] bg-white hover:border-[#4A6B5D] hover:text-[#4A6B5D] transition-colors"
                >
                    <i class="fas fa-shopping-basket"></i>
                    <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 bg-[#8C3A3A] text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center border border-white">
                        {{ cartCount }}
                    </span>
                </Link>
            </div>
        </template>

        <div class="py-12 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-6xl mx-auto px-6">
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
                                    :min="package.min_order"
                                    class="w-32 text-center font-bold text-lg border border-[#E6E1DA] rounded-lg p-3 focus:outline-none focus:border-[#4A6B5D] focus:ring-0 transition-colors bg-white text-[#2D3330]"
                                />
                                <button type="button" class="pax-btn font-light" @click="changeQty(1)">+</button>
                                <button type="button" class="pax-btn font-light" @click="changeQty(10)">+10</button>
                            </div>
                            <small class="text-[#8C8275] text-[10px] uppercase tracking-wider mt-4 block">
                                <i class="fas fa-info-circle mr-1"></i> {{ t('min_booking_requirement_is') }} <strong>{{ package.min_order }} {{ t('pax') }}</strong>.
                            </small>
                        </div>

                        <!-- Included Items -->
                        <div class="section-card">
                            <h4 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider mb-4 flex items-center gap-2">
                                <i class="fas fa-check-circle text-[#4A6B5D] text-sm"></i> {{ t('included_dishes') }}
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-[#5C6460]">
                                <div 
                                    v-for="item in package.description.split('\n').map(i => i.trim()).filter(i => i !== '')"
                                    :key="item"
                                    class="flex items-start gap-2.5 py-1"
                                >
                                    <i class="fas fa-check text-[#4A6B5D] text-xs mt-1"></i>
                                    <span class="font-light">{{ item }}</span>
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

                            <!-- Submit Action -->
                            <button 
                                @click="handleAddToCart"
                                class="w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3.5 px-6 rounded-lg text-xs uppercase tracking-widest transition-colors shadow-sm"
                                :disabled="form.processing"
                            >
                                <i class="fas fa-cart-plus text-[10px]"></i> {{ t('add_to_cart') }}
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
