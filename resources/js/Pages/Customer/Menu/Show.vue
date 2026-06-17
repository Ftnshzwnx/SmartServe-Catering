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

// Track which package's menu is expanded on mobile
const expandedMenu = ref({});

// Setup quantities for each variation
const quantities = ref(
    props.variations.reduce((acc, v) => {
        acc[v.package_id || v.id] = v.min_order || 20;
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
            toastMessage.value = t('toast_added_to_cart');
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 3000);
        }
    });
}

function toggleMenu(pkgId) {
    expandedMenu.value[pkgId] = !expandedMenu.value[pkgId];
}

const adjustGuests = (pkgId, amount, minVal = 0) => {
    let currentVal = parseInt(quantities.value[pkgId] || 0);
    let newVal = currentVal + amount;
    if (newVal < minVal) {
        newVal = minVal;
    }
    quantities.value[pkgId] = newVal;
};

const validateQuantity = (pkgId, minVal) => {
    let currentVal = parseInt(quantities.value[pkgId] || 0);
    if (isNaN(currentVal) || currentVal < minVal) {
        quantities.value[pkgId] = minVal;
    }
};

const getScaleBadge = (qty, minVal) => {
    const q = parseInt(qty || 0);
    if (q <= minVal + 100) {
        return {
            text: t('scale_small'),
            icon: 'fa-users',
            class: 'bg-[#EBEFEF] text-[#4A6B5D] border border-[#D1DEDB]'
        };
    } else if (q <= minVal + 400) {
        return {
            text: t('scale_medium'),
            icon: 'fa-people-group',
            class: 'bg-[#FFF9EE] text-[#D98A29] border border-[#F5E6CD]'
        };
    } else {
        return {
            text: t('scale_large'),
            icon: 'fa-crown',
            class: 'bg-[#FDF3F3] text-[#C84B4B] border border-[#F3DFDF]'
        };
    }
};

const getDishIcon = (dishName) => {
    const name = dishName.toLowerCase();
    if (name.includes('nasi')) {
        return { icon: 'fa-bowl-rice', bg: 'bg-[#EBF3F0]', text: 'text-[#4A6B5D]' };
    } else if (name.includes('daging') || name.includes('kambing') || name.includes('rendang') || name.includes('kerutuk') || name.includes('gulai')) {
        return { icon: 'fa-drumstick-bite', bg: 'bg-[#FDF3F3]', text: 'text-[#C84B4B]' };
    } else if (name.includes('ayam')) {
        return { icon: 'fa-drumstick-bite', bg: 'bg-[#FFF9EE]', text: 'text-[#D98A29]' };
    } else if (name.includes('ikan') || name.includes('seafood') || name.includes('udang') || name.includes('sotong') || name.includes('masin')) {
        return { icon: 'fa-fish-fins', bg: 'bg-[#EEF6FC]', text: 'text-[#3E82B8]' };
    } else if (name.includes('sayur') || name.includes('ulam') || name.includes('kobis') || name.includes('jelatah') || name.includes('acar')) {
        return { icon: 'fa-leaf', bg: 'bg-[#F0F9EB]', text: 'text-[#5E9E3D]' };
    } else if (name.includes('sambal') || name.includes('belacan') || name.includes('lada')) {
        return { icon: 'fa-pepper-hot', bg: 'bg-[#FFF2F2]', text: 'text-[#E53E3E]' };
    } else if (name.includes('air') || name.includes('sirap') || name.includes('teh') || name.includes('kopi') || name.includes('selasih') || name.includes('jus')) {
        return { icon: 'fa-glass-water', bg: 'bg-[#F2F3F5]', text: 'text-[#5C6460]' };
    } else if (name.includes('buah') || name.includes('pencuci mulut') || name.includes('manisan') || name.includes('agar') || name.includes('puding')) {
        return { icon: 'fa-apple-whole', bg: 'bg-[#FFF0F5]', text: 'text-[#D53F8C]' };
    }
    return { icon: 'fa-circle-check', bg: 'bg-[#EBEFEF]', text: 'text-[#4A6B5D]' };
};
</script>

<template>
    <Head :title="category + ' ' + t('packages')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .package-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .package-card:hover {
            border-color: #4A6B5D;
            box-shadow: 0 20px 30px -10px rgba(74, 107, 93, 0.15);
            transform: translateY(-4px);
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
        .btn-premium-primary {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-premium-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.15),
                transparent
            );
            transition: all 0.6s ease;
        }
        .btn-premium-primary:hover::before {
            left: 100%;
        }
        .btn-premium-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(74, 107, 93, 0.35);
        }
        .btn-premium-secondary {
            transition: all 0.3s ease;
        }
        .btn-premium-secondary:hover {
            border-color: #4A6B5D;
            background-color: rgba(74, 107, 93, 0.05);
            color: #4A6B5D;
            transform: translateY(-1px);
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #4A6B5D;
            border: 2px solid #FAF6F0;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.25);
            background: #3D574B;
        }
    </component>

    <AuthenticatedLayout
        header-title=""
        header-desc=""
    >

        <div class="font-sans-modern">
            <div class="max-w-5xl mx-auto px-4 sm:px-6">
                <!-- Back Link -->
                <div class="mb-4">
                    <Link 
                        :href="route('menu.index')" 
                        class="inline-flex items-center gap-2 text-xs font-semibold text-[#8C8275] hover:text-[#4A6B5D] uppercase tracking-wider transition-colors"
                    >
                        <i class="fas fa-arrow-left text-[9px]"></i>
                        <span>{{ t('back_to_menu') }}</span>
                    </Link>
                </div>
                
                <!-- Luxury Cover Banner -->
                <div class="mb-4 sm:mb-6 overflow-hidden rounded-lg sm:rounded-2xl bg-gradient-to-r from-[#2D3330] via-[#3A4540] to-[#4A6B5D] p-3.5 sm:p-6 md:p-8 text-white border border-[#E6E1DA]/10 shadow-lg relative">
                    <!-- Decor blurs -->
                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-[#C5A880]/10 rounded-full blur-2xl pointer-events-none"></div>
 
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#C5A880]/20 border border-[#C5A880]/30 rounded-full text-[10px] font-bold text-[#E6CBA3] uppercase tracking-widest">
                                <i class="fas fa-utensils"></i> {{ t('catering_packages') }}
                            </div>
                            <h1 class="text-base sm:text-2xl md:text-3xl lg:text-4xl font-normal font-serif-luxury tracking-wide uppercase leading-tight">
                                {{ category }}
                            </h1>
                            <p class="text-[10px] sm:text-xs md:text-sm text-[#E6E1DA]/80 max-w-2xl font-light leading-relaxed">
                                {{ t('category_show_desc') }}
                            </p>
                        </div>
                        <div class="flex gap-4 shrink-0">
                            <div class="bg-white/5 border border-white/10 rounded-2xl px-4 py-2.5 sm:px-5 sm:py-3.5 text-center backdrop-blur-xs min-w-24 sm:min-w-28">
                                <span class="block text-[8px] sm:text-[9px] font-bold text-[#C5A880] uppercase tracking-widest mb-1">{{ t('packages') }}</span>
                                <span class="text-lg sm:text-2xl font-semibold font-serif-luxury text-white">{{ variations.length }}</span>
                            </div>
                        </div>
                    </div>
                </div>
 
                <div v-if="variations.length > 0" class="grid grid-cols-2 lg:grid-cols-1 gap-3 sm:gap-6 lg:gap-10">
                    <!-- Package Card -->
                    <div 
                        v-for="pkg in variations" 
                        :key="pkg.id || pkg.package_id"
                        class="bg-white p-2.5 sm:p-5 md:p-6 rounded-lg sm:rounded-2xl border border-[#E6E1DA] shadow-sm package-card flex flex-col"
                    >
                        <!-- Mobile: stacked layout | lg: side-by-side 12-col grid -->
                        <div class="flex flex-col lg:grid lg:grid-cols-12 gap-3 sm:gap-6 lg:gap-8 items-start flex-grow">
                            
                            <!-- Left Info Section -->
                            <div class="lg:col-span-7 space-y-2.5 sm:space-y-6">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:flex-wrap sm:gap-4">
                                    <h3 class="text-[10px] sm:text-xl md:text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wide leading-tight">
                                        {{ pkg.package_name }}
                                    </h3>
                                    <!-- Price Badge -->
                                    <span class="self-start text-[9px] sm:text-sm font-semibold text-[#4A6B5D] bg-[#EBEFEF] border border-[#D1DEDB] px-2 py-0.5 sm:px-4 sm:py-2 rounded-full shadow-xs">
                                        RM {{ parseFloat(pkg.price).toFixed(2) }} <span class="text-[8px] sm:text-xs text-[#8C8275] font-normal">/ {{ t('pax') }}</span>
                                    </span>
                                </div>
 
                                <!-- Minimum Requirement (mobile compact) -->
                                <p class="text-[8px] sm:hidden text-[#8C8275] uppercase tracking-wider font-light">
                                    {{ t('min_requirement') }}: {{ pkg.min_order || 20 }} {{ t('pax') }}
                                </p>

                                <!-- Package Menu Items list -->
                                <div class="border-t border-[#E6E1DA] pt-2 sm:pt-6">

                                    <!-- Mobile: Toggle button to show/hide menu -->
                                    <button
                                        type="button"
                                        @click="toggleMenu(pkg.id || pkg.package_id)"
                                        class="sm:hidden w-full flex items-center justify-between text-[8px] font-bold text-[#4A6B5D] uppercase tracking-widest mb-1.5 cursor-pointer"
                                    >
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-list-ul text-[9px]"></i> {{ t('included_dishes') }}
                                        </span>
                                        <span class="flex items-center gap-0.5 text-[#8C8275]">
                                            <span>{{ expandedMenu[pkg.id || pkg.package_id] ? 'Tutup' : 'Lihat' }}</span>
                                            <i class="fas transition-transform duration-300 text-[8px]"
                                               :class="expandedMenu[pkg.id || pkg.package_id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </span>
                                    </button>

                                    <!-- Desktop: always show label -->
                                    <p class="hidden sm:flex text-[10px] font-bold text-[#8C8275] uppercase tracking-widest mb-2.5 items-center gap-1">
                                        <i class="fas fa-list-ul text-xs text-[#4A6B5D]"></i> {{ t('included_dishes') }}:
                                    </p>

                                    <!-- Dish grid: hidden on mobile by default, toggle on click; always visible on sm+ -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-1 sm:gap-3.5 overflow-hidden transition-all duration-300"
                                        :class="[
                                            'sm:block sm:grid',
                                            expandedMenu[pkg.id || pkg.package_id] ? 'grid' : 'hidden sm:grid'
                                        ]"
                                    >
                                        <div 
                                            v-for="item in pkg.description.split('\n').map(i => i.trim()).filter(i => i !== '')"
                                            :key="item"
                                            class="flex items-center gap-1.5 sm:gap-3 p-1 sm:p-3 bg-[#FAF8F5]/80 hover:bg-[#FAF8F5] border border-[#E6E1DA] rounded-md sm:rounded-xl transition-all duration-300"
                                        >
                                            <span 
                                                class="w-5 h-5 sm:w-7 sm:h-7 rounded-full flex items-center justify-center shrink-0 shadow-xs"
                                                :class="[getDishIcon(item).bg, getDishIcon(item).text]"
                                            >
                                                <i class="fas text-[8px] sm:text-[10px]" :class="getDishIcon(item).icon"></i>
                                            </span>
                                            <span class="text-[9px] sm:text-xs text-[#2D3330] font-medium tracking-wide leading-tight">{{ item }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
 
                            <!-- Right Calculator Section -->
                            <div class="lg:col-span-5 bg-[#FAF8F5] p-2 sm:p-5 rounded-md sm:rounded-xl border border-[#E6E1DA] flex flex-col justify-between gap-2 sm:gap-5 shadow-xs">
                                <div class="space-y-2 sm:space-y-5">
                                    <!-- Min req (hidden on mobile, shown above) -->
                                    <div class="hidden sm:flex items-center justify-between text-xs tracking-wide">
                                        <span class="font-bold text-[#8C8275] uppercase">{{ t('min_requirement') }}:</span>
                                        <span class="font-bold text-[#2D3330] bg-white border border-[#E6E1DA] px-2.5 py-1 rounded-lg shadow-2xs">
                                            {{ pkg.min_order || 20 }} {{ t('pax') }}
                                        </span>
                                    </div>
 
                                     <!-- Stepper Quantity Input -->
                                     <div class="space-y-1">
                                         <label class="text-[8px] sm:text-[10px] font-bold text-[#8C8275] uppercase tracking-widest block">{{ t('select_quantity') }}</label>
                                         <div class="flex items-center shadow-xs rounded-md sm:rounded-xl overflow-hidden border border-[#E6E1DA] w-full">
                                             <!-- Minus Button -->
                                             <button 
                                                 type="button"
                                                 @click="adjustGuests(pkg.id || pkg.package_id, -50, pkg.min_order || 20)"
                                                 class="w-7 h-7 sm:w-12 sm:h-12 bg-white text-[#5C6460] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors flex items-center justify-center font-semibold cursor-pointer focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed border-r border-[#E6E1DA]"
                                                 :disabled="quantities[pkg.id || pkg.package_id] <= (pkg.min_order || 20)"
                                             >
                                                 <i class="fas fa-minus text-[8px] sm:text-xs"></i>
                                             </button>
                                             
                                             <!-- Value Input -->
                                             <input 
                                                 type="number" 
                                                 v-model.number="quantities[pkg.id || pkg.package_id]" 
                                                 :min="pkg.min_order || 20"
                                                 @change="validateQuantity(pkg.id || pkg.package_id, pkg.min_order || 20)"
                                                 class="flex-1 text-center font-bold text-[10px] sm:text-lg border-0 h-7 sm:h-12 focus:outline-none focus:ring-0 bg-white text-[#2D3330]"
                                             />
                                             
                                             <!-- Plus Button -->
                                             <button 
                                                 type="button"
                                                 @click="adjustGuests(pkg.id || pkg.package_id, 50)"
                                                 class="w-7 h-7 sm:w-12 sm:h-12 bg-white text-[#5C6460] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors flex items-center justify-center font-semibold cursor-pointer focus:outline-none border-l border-[#E6E1DA]"
                                             >
                                                 <i class="fas fa-plus text-[8px] sm:text-xs"></i>
                                             </button>
                                         </div>
                                     </div>
                                     
                                     <!-- Range Slider — hidden on mobile to save space -->
                                     <div class="hidden sm:block space-y-1 px-1">
                                         <input 
                                             type="range" 
                                             v-model.number="quantities[pkg.id || pkg.package_id]" 
                                             :min="pkg.min_order || 20" 
                                             :max="Math.max(1000, (pkg.min_order || 20) * 5)"
                                             step="50"
                                             class="w-full accent-[#4A6B5D] cursor-pointer h-1 bg-[#E6E1DA] rounded-lg appearance-none"
                                         />
                                         <div class="flex justify-between text-[9px] font-bold text-[#8C8275] uppercase tracking-wider">
                                             <span>Min: {{ pkg.min_order || 20 }} {{ t('pax') }}</span>
                                             <span>Max: {{ Math.max(1000, (pkg.min_order || 20) * 5) }} {{ t('pax') }}</span>
                                         </div>
                                     </div>
 
                                     <!-- Live Total (compact on mobile) -->
                                     <div class="bg-[#FAF7F2] p-2 sm:p-4 rounded-md sm:rounded-xl border border-[#E6E1DA] flex items-center justify-between shadow-2xs">
                                         <span class="text-[8px] sm:text-xs font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('live_total') }}:</span>
                                         <span class="text-sm sm:text-2xl font-normal text-[#4A6B5D] font-serif-luxury tracking-wide">
                                             RM {{ calculateTotal(pkg.price, quantities[pkg.id || pkg.package_id]).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                         </span>
                                     </div>
                                 </div>
 
                                 <!-- Action Buttons -->
                                 <div class="flex flex-col gap-1.5 sm:gap-2 mt-2 sm:mt-0">
                                     <Link 
                                         :href="route('cart.customize', { package_id: pkg.id || pkg.package_id })"
                                         class="btn-premium-primary w-full inline-flex items-center justify-center gap-1 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-1.5 sm:py-3.5 px-2 sm:px-6 rounded-md sm:rounded-xl text-[8.5px] sm:text-xs uppercase tracking-widest shadow-sm text-center cursor-pointer"
                                     >
                                         <i class="fas fa-sliders-h text-[8px] sm:text-[10px]"></i> {{ t('customize_order') }}
                                     </Link>
                                     
                                     <button 
                                         @click="handleAddToCart(pkg.id || pkg.package_id)"
                                         class="btn-premium-secondary w-full inline-flex items-center justify-center gap-1 bg-white border border-[#E6E1DA] text-[#5C6460] font-semibold py-1.5 sm:py-3.5 px-2 sm:px-6 rounded-md sm:rounded-xl text-[8.5px] sm:text-xs uppercase tracking-widest cursor-pointer"
                                         :disabled="form.processing"
                                     >
                                         <i class="fas fa-cart-plus text-[8px] sm:text-[10px]"></i> {{ t('direct_add_cart') }}
                                     </button>
                                 </div>
                             </div>

                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 bg-white rounded-3xl border border-[#E6E1DA] text-center max-w-xl mx-auto shadow-sm">
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
