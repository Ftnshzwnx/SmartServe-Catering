<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const { toast } = useToast();
const { confirm } = useConfirm();

const props = defineProps({
    cartItems: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
});

const { t } = useLocalization();

// Selected Item IDs for checkout
const selectedCartIds = ref(props.cartItems.map(item => item.id));

// Forms
const deleteForm = useForm({});
const updateForm = useForm({
    quantity: {},
});

// Sync default quantities
props.cartItems.forEach(item => {
    updateForm.quantity[item.id] = item.quantity;
});

// Check if all items are selected
const selectAll = computed({
    get: () => selectedCartIds.value.length === props.cartItems.length,
    set: (value) => {
        if (value) {
            selectedCartIds.value = props.cartItems.map(item => item.id);
        } else {
            selectedCartIds.value = [];
        }
    }
});

// Calculate totals of selected items only
const selectedSubtotal = computed(() => {
    return props.cartItems
        .filter(item => selectedCartIds.value.includes(item.id))
        .reduce((sum, item) => sum + parseFloat(item.price) * parseInt(item.quantity), 0.00);
});

const showSuccessToast = ref(false);
const toastMessage = ref('');

// Delete cart item
async function removeCartItem(id) {
    if (await confirm(
        t('confirm_remove_cart') || 'Are you sure you want to remove this package from your cart?',
        t('confirm_action') || 'Remove Item',
        t('yes_confirm') || 'Yes, Remove',
        t('no_cancel') || 'Keep Item'
    )) {
        deleteForm.delete(route('cart.destroy', { id: id }), {
            onSuccess: () => {
                toast(t('toast_item_removed') || 'Item removed from shopping cart.');
                
                // Remove from selected list if deleted
                const idx = selectedCartIds.value.indexOf(id);
                if (idx > -1) selectedCartIds.value.splice(idx, 1);
            }
        });
    }
}

// Update quantity
function updateQuantity(id) {
    const newQty = updateForm.quantity[id];
    const item = props.cartItems.find(i => i.id === id);
    
    // Check package min limit
    if (newQty < (item.package?.min_order || 20)) {
        toast((t('qty_min_order_error') || 'Minimum order requirement is') + ' ' + (item.package?.min_order || 20) + ' pax.', 'error');
        updateForm.quantity[id] = item.package?.min_order || 20;
        return;
    }

    useForm({ quantity: newQty }).post(route('cart.update', { id: id }), {
        onSuccess: () => {
            toast(t('toast_qty_updated') || 'Quantity updated successfully.');
        }
    });
}

function adjustCartQuantity(id, amount, minVal) {
    let currentVal = parseInt(updateForm.quantity[id] || 0);
    let newVal = currentVal + amount;
    if (newVal < minVal) {
        newVal = minVal;
    }
    updateForm.quantity[id] = newVal;
    updateQuantity(id);
}

// Proceed to checkout
const checkoutForm = useForm({
    selected_items: [],
});

function proceedToCheckout() {
    if (selectedCartIds.value.length === 0) {
        toast(t('select_item_checkout_error') || 'Please select at least one item to checkout.', 'error');
        return;
    }
    checkoutForm.selected_items = selectedCartIds.value;
    checkoutForm.get(route('checkout.index'));
}
</script>

<template>
    <Head :title="t('shopping_cart')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
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
    </component>

    <component :is="'style'">
        /* Custom checkbox style overlay to override default blue */
        input[type="checkbox"]:checked {
            background-color: #4A6B5D !important;
            border-color: #4A6B5D !important;
        }
    </component>

    <AuthenticatedLayout
        header-title=""
        header-desc=""
    >

        <div class="font-sans-modern">
            <div class="max-w-6xl mx-auto px-6">
                
                <!-- Luxury Cover Banner -->
                <div class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-[#2D3330] via-[#3A4540] to-[#4A6B5D] p-8 md:p-10 text-white border border-[#E6E1DA]/10 shadow-lg relative">
                    <!-- Decor blurs -->
                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-[#C5A880]/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#C5A880]/20 border border-[#C5A880]/30 rounded-full text-[10px] font-bold text-[#E6CBA3] uppercase tracking-widest">
                                <i class="fas fa-shopping-basket"></i> {{ t('shopping_cart') || 'Troli Tempahan' }}
                            </div>
                            <h1 class="text-3xl md:text-4xl font-normal font-serif-luxury tracking-wide uppercase leading-tight">
                                {{ t('shopping_cart') || 'Troli Tempahan' }}
                            </h1>
                            <p class="text-xs md:text-sm text-[#E6E1DA]/80 max-w-2xl font-light leading-relaxed">
                                {{ t('cart_desc_banner') || 'Semak semula pakej katering terpilih anda, laras kuantiti tetamu, dan lakukan pembayaran dengan selamat.' }}
                            </p>
                        </div>
                        <div class="flex gap-4 shrink-0">
                            <div class="bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-center backdrop-blur-xs min-w-28">
                                <span class="block text-[9px] font-bold text-[#C5A880] uppercase tracking-widest mb-1">{{ t('orders') || 'Jumlah Pakej' }}</span>
                                <span class="text-2xl font-semibold font-serif-luxury text-white">{{ cartItems.length }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="cartItems.length > 0" class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left: Cart Items Table (8 cols) -->
                    <div class="lg:col-span-8 space-y-4">
                        <!-- Select All Header Card -->
                        <div class="bg-white p-5 rounded-2xl border border-[#E6E1DA] shadow-2xs flex items-center justify-between">
                            <label class="flex items-center gap-3 cursor-pointer text-xs font-semibold text-[#5C6460] select-none">
                                <input 
                                    type="checkbox" 
                                    v-model="selectAll"
                                    class="border-[#E6E1DA] text-[#4A6B5D] focus:ring-[#4A6B5D] w-4.5 h-4.5 cursor-pointer rounded"
                                />
                                <span>Pilih Semua Pakej ({{ cartItems.length }})</span>
                            </label>
                            
                            <span v-if="selectedCartIds.length > 0" class="text-[10px] font-bold text-[#4A6B5D] bg-[#EBEFEF] border border-[#D1DEDB] px-3 py-1 rounded-full uppercase tracking-wider">
                                {{ selectedCartIds.length }} Dipilih
                            </span>
                        </div>

                        <!-- Card List of Packages -->
                        <div class="space-y-4">
                            <div 
                                v-for="item in cartItems" 
                                :key="item.id"
                                class="bg-white p-6 rounded-3xl border border-[#E6E1DA] shadow-xs hover:border-[#4A6B5D]/50 hover:shadow-sm transition-all duration-300 relative group"
                                :class="{ 'border-[#4A6B5D]/30 bg-[#FAFBFB]': selectedCartIds.includes(item.id) }"
                            >
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                                    <!-- Checkbox & Details -->
                                    <div class="flex items-start gap-4 flex-grow">
                                        <!-- Checkbox -->
                                        <div class="pt-1">
                                            <input 
                                                type="checkbox" 
                                                :value="item.id" 
                                                v-model="selectedCartIds"
                                                class="border-[#E6E1DA] text-[#4A6B5D] focus:ring-[#4A6B5D] w-4.5 h-4.5 cursor-pointer rounded"
                                            />
                                        </div>

                                        <!-- Package Info -->
                                        <div class="space-y-3 flex-grow">
                                            <div>
                                                <h3 class="text-xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider block">
                                                    {{ item.package_name }}
                                                </h3>
                                                <div class="text-xs font-semibold text-[#4A6B5D] mt-1">
                                                    Harga Asas: RM {{ parseFloat(item.price).toFixed(2) }} <span class="text-[10px] text-[#8C8275] font-normal">/ pax</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Selected Dishes List -->
                                            <div v-if="item.selected_dishes && item.selected_dishes.length > 0" class="space-y-1.5 pt-1.5 border-t border-[#EBEFEF]">
                                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1">
                                                    <i class="fas fa-utensils text-[8px] text-[#4A6B5D]"></i> Lauk Pilihan:
                                                </span>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <span 
                                                        v-for="dish in item.selected_dishes" 
                                                        :key="dish"
                                                        class="inline-flex items-center gap-1 bg-[#FAF8F5] border border-[#E6E1DA] text-[10px] text-[#2D3330] font-medium px-2.5 py-0.5 rounded-full"
                                                    >
                                                        {{ dish }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Selected Add-ons List -->
                                            <div v-if="item.selected_addons && item.selected_addons.length > 0" class="space-y-1.5 pt-1.5 border-t border-[#EBEFEF]">
                                                <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest flex items-center gap-1">
                                                    <i class="fas fa-plus text-[8px] text-[#C5A880]"></i> Tambahan (Add-ons):
                                                </span>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <span 
                                                        v-for="addon in item.selected_addons" 
                                                        :key="addon"
                                                        class="inline-flex items-center gap-1 bg-[#FFF9EE] border border-[#F5E6CD] text-[10px] text-[#D98A29] font-medium px-2.5 py-0.5 rounded-full"
                                                    >
                                                        {{ addon }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stepper, Subtotal, and Delete -->
                                    <div class="flex flex-row md:flex-col md:items-end justify-between items-center gap-4 shrink-0 pt-4 md:pt-0 border-t md:border-t-0 border-[#EBEFEF]">
                                        <!-- Action Buttons -->
                                        <div class="flex items-center gap-1 order-last md:order-none">
                                            <!-- Edit Button -->
                                            <Link 
                                                v-if="item.package"
                                                :href="(item.selected_dishes && item.selected_dishes.length > 0)
                                                    ? route('cart.customize', { package_id: item.package_id, cart_id: item.id })
                                                    : route('menu.show', item.package_name)"
                                                class="text-[#8C8275] hover:text-[#4A6B5D] transition-colors w-8 h-8 rounded-lg hover:bg-[#FAF7F2] flex items-center justify-center cursor-pointer"
                                                :title="(item.selected_dishes && item.selected_dishes.length > 0) ? 'Edit Pilihan Lauk' : 'Lihat Perincian Pakej'"
                                            >
                                                <i class="fas fa-edit text-xs"></i>
                                            </Link>

                                            <!-- Delete Button -->
                                            <button 
                                                @click="removeCartItem(item.id)" 
                                                class="text-[#8C8275] hover:text-rose-600 transition-colors w-8 h-8 rounded-lg hover:bg-rose-50/50 flex items-center justify-center cursor-pointer"
                                                title="Keluarkan Pakej"
                                            >
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </div>

                                        <!-- Stepper Quantity Control -->
                                        <div class="flex flex-col items-start md:items-end gap-1.5">
                                            <div class="flex items-center shadow-2xs rounded-lg overflow-hidden border border-[#E6E1DA] h-9">
                                                <button 
                                                    type="button"
                                                    @click="adjustCartQuantity(item.id, -50, item.package?.min_order || 20)"
                                                    class="w-8 h-full bg-white text-[#5C6460] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] flex items-center justify-center font-semibold text-xs cursor-pointer border-r border-[#E6E1DA]"
                                                    :disabled="updateForm.quantity[item.id] <= (item.package?.min_order || 20)"
                                                >
                                                    <i class="fas fa-minus text-[9px]"></i>
                                                </button>
                                                <input 
                                                    type="number" 
                                                    v-model.number="updateForm.quantity[item.id]" 
                                                    :min="item.package?.min_order || 20"
                                                    @change="updateQuantity(item.id)"
                                                    class="w-14 text-center font-bold text-xs border-0 h-full focus:outline-none focus:ring-0 bg-white text-[#2D3330]"
                                                />
                                                <button 
                                                    type="button"
                                                    @click="adjustCartQuantity(item.id, 50, item.package?.min_order || 20)"
                                                    class="w-8 h-full bg-white text-[#5C6460] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] flex items-center justify-center font-semibold text-xs cursor-pointer border-l border-[#E6E1DA]"
                                                >
                                                    <i class="fas fa-plus text-[9px]"></i>
                                                </button>
                                            </div>
                                            <span class="text-[9px] text-[#8C8275] font-semibold uppercase tracking-wider">
                                                Min: {{ item.package?.min_order || 20 }} pax
                                            </span>
                                        </div>

                                        <!-- Subtotal Display -->
                                        <div class="text-right">
                                            <div class="text-[9px] font-bold text-[#8C8275] uppercase tracking-wider">Subjumlah:</div>
                                            <div class="text-lg font-normal text-[#2D3330] font-serif-luxury tracking-wide whitespace-nowrap">
                                                RM {{ (parseFloat(item.price) * parseInt(item.quantity)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Summary Card (4 cols) -->
                    <div class="lg:col-span-4 sticky top-24">
                        <div class="bg-white p-6 rounded-3xl border border-[#E6E1DA] shadow-sm space-y-6">
                            <h4 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider border-b border-[#E6E1DA] pb-4">
                                {{ t('order_summary') }}
                            </h4>
                            
                            <div class="space-y-3 text-xs border-b border-[#E6E1DA] pb-5">
                                <div class="flex justify-between text-[#8C8275] uppercase tracking-wider">
                                    <span>{{ t('selected_packages') || 'Pakej Dipilih' }}</span>
                                    <span class="font-bold text-[#2D3330]">{{ selectedCartIds.length }} Pakej</span>
                                </div>
                                <div class="flex justify-between text-[#8C8275] uppercase tracking-wider">
                                    <span>{{ t('subtotal') || 'Subjumlah' }}</span>
                                    <span class="font-bold text-[#2D3330]">
                                        RM {{ selectedSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                    </span>
                                </div>
                                
                                <div v-if="selectedCartIds.length > 0" class="space-y-2 pt-2 border-t border-[#EBEFEF] text-[11px] italic text-[#8C8275]">
                                    <div class="flex justify-between">
                                        <span>Deposit Tempahan (30%)</span>
                                        <span>RM {{ (selectedSubtotal * 0.3).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Baki Perlu Dijelaskan (70%)</span>
                                        <span>RM {{ (selectedSubtotal * 0.7).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-[#FAF8F5] p-4 rounded-xl border border-[#E6E1DA] flex items-center justify-between shadow-2xs">
                                <span class="text-xs font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('total_price') || 'Jumlah Harga' }}:</span>
                                <div class="text-right">
                                    <span class="text-2xl font-normal text-[#4A6B5D] font-serif-luxury tracking-wide block">
                                        RM {{ selectedSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                    </span>
                                    <span class="text-[8px] font-bold text-[#8C8275] uppercase tracking-widest block mt-0.5">SST Termasuk / Halal Certified</span>
                                </div>
                            </div>

                            <button 
                                @click="proceedToCheckout"
                                class="btn-premium-primary w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3.5 px-6 rounded-xl text-xs uppercase tracking-widest shadow-sm cursor-pointer"
                                :disabled="selectedCartIds.length === 0"
                                :class="{ 'opacity-50 cursor-not-allowed': selectedCartIds.length === 0 }"
                            >
                                <i class="fas fa-lock text-[10px]"></i> {{ t('proceed_checkout') }}
                            </button>

                            <div class="text-center pt-2">
                                <Link 
                                    :href="route('menu.index')"
                                    class="text-xs font-semibold text-[#8C8275] hover:text-[#4A6B5D] uppercase tracking-widest transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <i class="fas fa-arrow-left text-[10px]"></i> {{ t('continue_shopping') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-3xl border border-[#E6E1DA] text-center py-20 flex flex-col items-center max-w-xl mx-auto shadow-sm animate-fade-in">
                    <div class="w-16 h-16 rounded-2xl bg-[#FAF8F5] text-[#8C8275] flex items-center justify-center text-xl mb-6 border border-[#E6E1DA]">
                        <i class="fas fa-shopping-basket text-lg"></i>
                    </div>
                    <h4 class="text-[#2D3330] font-normal font-serif-luxury text-2xl uppercase tracking-wider mb-2">{{ t('cart_empty') }}</h4>
                    <p class="text-[#8C8275] text-xs max-w-sm mt-1 mb-8 font-light leading-relaxed">{{ t('cart_empty_desc') }}</p>
                    <Link 
                        :href="route('menu.index')"
                        class="btn-premium-primary bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-8 py-3.5 rounded-xl text-xs uppercase tracking-widest transition-colors shadow-sm"
                    >
                        {{ t('browse_packages_btn') }}
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
