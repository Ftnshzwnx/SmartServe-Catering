<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    category: {
        type: String,
        required: true,
    },
    variations: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
});

const { t } = useLocalization();

// Setup quantities for each variation
const quantities = ref(
    props.variations.reduce((acc, v) => {
        acc[v.package_id || v.id] = v.min_order || 100;
        return acc;
    }, {})
);

function calculateTotal(price, qty) {
    return parseFloat(price) * parseInt(qty || 0);
}

// Breeze Form for direct add to cart
const form = useForm({
    package_id: null,
    quantity: null,
});

const showSuccessToast = ref(false);
const toastMessage = ref('');

function handleAddToCart(packageId) {
    form.package_id = packageId;
    form.quantity = quantities.value[packageId];
    form.post(route('cart.add'), {
        onSuccess: () => {
            toastMessage.value = t('toast_added_to_cart') || 'Package added to cart successfully!';
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 3000);
        }
    });
}
</script>

<template>
    <Head :title="category + ' ' + t('packages')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .package-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .package-card:hover {
            border-color: #4A6B5D;
            box-shadow: 0 12px 20px -8px rgba(74, 107, 93, 0.12);
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
                        <span class="text-[#4A6B5D]">{{ category }}</span>
                    </span>
                    <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                        {{ category }}
                    </h2>
                </div>

                <!-- Cart Link in Header -->
                <Link 
                    :href="route('cart.index')" 
                    class="relative flex items-center justify-center w-10 h-10 rounded-none border border-[#E6E1DA] bg-white hover:border-[#4A6B5D] hover:text-[#4A6B5D] transition-colors"
                >
                    <i class="fas fa-shopping-basket"></i>
                    <span v-if="cartCount > 0" class="absolute -top-1.5 -right-1.5 bg-[#8C3A3A] text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center border border-white">
                        {{ cartCount }}
                    </span>
                </Link>
            </div>
        </template>

        <div class="py-12 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-5xl mx-auto px-6">
                
                <div v-if="variations.length > 0" class="space-y-8">
                    <!-- Package Card -->
                    <div 
                        v-for="pkg in variations" 
                        :key="pkg.id || pkg.package_id"
                        class="bg-white p-8 rounded-none border border-[#E6E1DA] shadow-sm package-card"
                    >
                        <div class="grid lg:grid-cols-12 gap-8 items-start">
                            
                            <!-- Left Info Section (7 cols) -->
                            <div class="lg:col-span-7 space-y-6">
                                <div class="flex items-center justify-between flex-wrap gap-4">
                                    <h3 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide">
                                        {{ pkg.package_name }}
                                    </h3>
                                    <!-- Price Badge -->
                                    <span class="text-sm font-semibold text-[#4A6B5D] bg-[#EBEFEF] border border-[#D1DEDB] px-4 py-2 rounded-none">
                                        RM {{ parseFloat(pkg.price).toFixed(2) }} <span class="text-xs text-[#8C8275] font-normal">/ {{ t('pax') }}</span>
                                    </span>
                                </div>

                                <!-- Package Menu Items list -->
                                <div class="border-t border-[#E6E1DA] pt-6">
                                    <p class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest mb-4">{{ t('included_dishes') }}:</p>
                                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-[#5C6460]">
                                        <li 
                                            v-for="item in pkg.description.split('\n').map(i => i.trim()).filter(i => i !== '')"
                                            :key="item"
                                            class="flex items-start gap-2.5"
                                        >
                                            <i class="fas fa-check text-[#4A6B5D] text-xs mt-1"></i>
                                            <span class="font-light">{{ item }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Right Calculator Section (5 cols) -->
                            <div class="lg:col-span-5 bg-[#FAF6F0] p-6 rounded-none border border-[#E6E1DA] flex flex-col justify-between gap-6 self-stretch">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between text-xs tracking-wide">
                                        <span class="font-semibold text-[#8C8275] uppercase">{{ t('min_requirement') }}:</span>
                                        <span class="font-bold text-[#2D3330]">{{ pkg.min_order }} {{ t('pax') }}</span>
                                    </div>

                                    <!-- Quantity Input -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('select_quantity') }}</label>
                                        <input 
                                            type="number" 
                                            v-model="quantities[pkg.id || pkg.package_id]" 
                                            :min="pkg.min_order"
                                            class="w-full text-center font-bold text-lg border border-[#E6E1DA] rounded-none p-3 focus:outline-none focus:border-[#4A6B5D] focus:ring-0 transition-colors bg-white text-[#2D3330]"
                                        />
                                    </div>
                                    
                                    <!-- Live Total -->
                                    <div class="border-t border-[#E6E1DA] pt-4 flex items-center justify-between">
                                        <span class="text-xs font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('live_total') }}:</span>
                                        <span class="text-xl font-normal text-[#2D3330] font-serif-luxury tracking-wide">
                                            RM {{ calculateTotal(pkg.price, quantities[pkg.id || pkg.package_id]).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col gap-2">
                                    <Link 
                                        :href="route('cart.customize', { package_id: pkg.id || pkg.package_id })"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3.5 px-6 rounded-none text-xs uppercase tracking-widest transition-colors shadow-sm text-center"
                                    >
                                        <i class="fas fa-sliders-h text-[10px]"></i> {{ t('customize_order') }}
                                    </Link>
                                    
                                    <button 
                                        @click="handleAddToCart(pkg.id || pkg.package_id)"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold py-3 px-6 rounded-none text-xs uppercase tracking-widest transition-colors"
                                        :disabled="form.processing"
                                    >
                                        <i class="fas fa-cart-plus text-[10px]"></i> {{ t('direct_add_cart') }}
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 bg-white rounded-none border border-[#E6E1DA] text-center max-w-xl mx-auto shadow-sm">
                    <i class="fas fa-utensils fa-2x text-[#8C8275] mb-4"></i>
                    <h5 class="text-[#2D3330] font-serif-luxury text-xl mb-2">{{ t('no_packages_found_cat') }}</h5>
                    <Link :href="route('menu.index')" class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-[#4A6B5D] hover:text-[#3D574B] uppercase tracking-widest">
                        <i class="fas fa-arrow-left"></i> {{ t('back_to_menu') }}
                    </Link>
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
