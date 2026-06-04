<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useLocalization } from '@/Composables/useLocalization';

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
function removeCartItem(id) {
    if (confirm(t('confirm_remove_cart') || 'Are you sure you want to remove this package from your cart?')) {
        deleteForm.delete(route('cart.destroy', { id: id }), {
            onSuccess: () => {
                toastMessage.value = t('toast_item_removed') || 'Item removed from shopping cart.';
                showSuccessToast.value = true;
                setTimeout(() => {
                    showSuccessToast.value = false;
                }, 3000);
                
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
    if (newQty < (item.package?.min_order || 100)) {
        alert(t('qty_min_order_error') + ' ' + (item.package?.min_order || 100) + ' pax.');
        updateForm.quantity[id] = item.package?.min_order || 100;
        return;
    }

    useForm({ quantity: newQty }).post(route('cart.update', { id: id }), {
        onSuccess: () => {
            toastMessage.value = t('toast_qty_updated') || 'Quantity updated successfully.';
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 2000);
        }
    });
}

// Proceed to checkout
const checkoutForm = useForm({
    selected_items: [],
});

function proceedToCheckout() {
    if (selectedCartIds.value.length === 0) {
        alert(t('select_item_checkout_error') || 'Please select at least one item to checkout.');
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
        .cart-card {
            background: #ffffff;
            border-radius: 0px;
            padding: 30px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
        }
        .qty-input {
            width: 80px;
            border-radius: 0px;
            border: 1px solid #E6E1DA;
            text-align: center;
            padding: 6px;
            font-weight: 700;
            color: #2D3330;
            background: white;
        }
        .qty-input:focus {
            border-color: #4A6B5D;
            outline: none;
            box-shadow: none;
        }
        .summary-card {
            background: #ffffff;
            border-radius: 0px;
            padding: 30px;
            border: 1px solid #E6E1DA;
            box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.01);
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

    <component :is="'style'">
        /* Custom checkbox style overlay to override default blue */
        input[type="checkbox"]:checked {
            background-color: #4A6B5D !important;
            border-color: #4A6B5D !important;
        }
    </component>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                {{ t('shopping_cart') }}
            </h2>
        </template>

        <div class="py-12 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-6xl mx-auto px-6">
                
                <div v-if="cartItems.length > 0" class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left: Cart Items Table (8 cols) -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="cart-card overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse align-middle">
                                    <thead>
                                        <tr class="border-b border-[#E6E1DA] text-[#8C8275] text-[10px] font-bold uppercase tracking-widest">
                                            <th class="py-4 pl-2 text-center w-12">
                                                <input 
                                                    type="checkbox" 
                                                    v-model="selectAll"
                                                    class="border-[#E6E1DA] text-[#4A6B5D] focus:ring-[#4A6B5D] w-4 h-4 cursor-pointer rounded-none"
                                                />
                                            </th>
                                            <th class="py-4 pl-4">{{ t('package') }}</th>
                                            <th class="py-4 text-center">{{ t('price') }}</th>
                                            <th class="py-4 text-center w-24">{{ t('quantity') }}</th>
                                            <th class="py-4 text-right pr-4">{{ t('subtotal') }}</th>
                                            <th class="py-4 text-center w-16">{{ t('action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#EBEFEF] text-sm text-[#5C6460]">
                                        <tr v-for="item in cartItems" :key="item.id" class="hover:bg-[#FAF6F0]/30 transition-colors">
                                            <!-- Select Checkbox -->
                                            <td class="py-6 text-center">
                                                <input 
                                                    type="checkbox" 
                                                    :value="item.id" 
                                                    v-model="selectedCartIds"
                                                    class="border-[#E6E1DA] text-[#4A6B5D] focus:ring-[#4A6B5D] w-4 h-4 cursor-pointer rounded-none"
                                                />
                                            </td>

                                            <!-- Package Details -->
                                            <td class="py-6 pl-4">
                                                <div class="space-y-1">
                                                    <span class="font-normal text-[#2D3330] font-serif-luxury text-base uppercase tracking-wider block">{{ item.package_name }}</span>
                                                    
                                                    <!-- Selected Add-ons List -->
                                                    <div v-if="item.selected_addons && item.selected_addons.length > 0" class="space-y-1">
                                                        <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest block mt-2">Add-ons:</span>
                                                        <div class="flex flex-wrap gap-1">
                                                            <span 
                                                                v-for="addon in item.selected_addons" 
                                                                :key="addon"
                                                                class="inline-flex items-center gap-1 bg-[#FAF6F0] border border-[#E6E1DA] text-[9px] text-[#4A6B5D] font-medium px-2 py-0.5 rounded-none"
                                                            >
                                                                <i class="fas fa-plus text-[6px]"></i> {{ addon }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Price -->
                                            <td class="py-6 text-center whitespace-nowrap text-[#2D3330]">
                                                RM {{ parseFloat(item.price).toFixed(2) }}
                                            </td>

                                            <!-- Quantity Selector -->
                                            <td class="py-6 text-center">
                                                <div class="flex flex-col items-center gap-1.5">
                                                    <input 
                                                        type="number" 
                                                        v-model="updateForm.quantity[item.id]" 
                                                        class="qty-input"
                                                        :min="item.package?.min_order || 100"
                                                        @change="updateQuantity(item.id)"
                                                    />
                                                    <span class="text-[9px] text-[#8C8275] font-semibold uppercase tracking-wider">Min: {{ item.package?.min_order || 100 }}</span>
                                                </div>
                                            </td>

                                            <!-- Subtotal -->
                                            <td class="py-6 text-right pr-4 font-normal text-[#2D3330] font-serif-luxury text-base whitespace-nowrap">
                                                RM {{ (parseFloat(item.price) * parseInt(item.quantity)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                            </td>

                                            <!-- Delete Button -->
                                            <td class="py-6 text-center">
                                                <button 
                                                    @click="removeCartItem(item.id)" 
                                                    class="text-red-500 hover:text-red-700 transition-colors w-8 h-8 rounded-none hover:bg-red-50/50 flex items-center justify-center"
                                                >
                                                    <i class="fas fa-trash-alt text-sm"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Summary Card (4 cols) -->
                    <div class="lg:col-span-4 sticky top-24">
                        <div class="summary-card space-y-6">
                            <h4 class="text-lg font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider mb-2">{{ t('order_summary') }}</h4>
                            
                            <div class="space-y-3 text-xs border-b border-[#E6E1DA] pb-4">
                                <div class="flex justify-between text-[#8C8275] uppercase tracking-wider">
                                    <span>{{ t('selected_packages') }}</span>
                                    <span class="font-bold text-[#2D3330]">{{ selectedCartIds.length }} Items</span>
                                </div>
                                <div class="flex justify-between text-[#8C8275] uppercase tracking-wider">
                                    <span>{{ t('subtotal') }}</span>
                                    <span class="font-bold text-[#2D3330]">RM {{ selectedSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-2">
                                <span class="text-xs font-semibold text-[#8C8275] uppercase tracking-wider">{{ t('total_price') }}:</span>
                                <span class="text-2xl font-normal text-[#4A6B5D] font-serif-luxury tracking-wide">
                                    RM {{ selectedSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                </span>
                            </div>

                            <button 
                                @click="proceedToCheckout"
                                class="w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3.5 px-6 rounded-none text-xs uppercase tracking-widest transition-colors shadow-sm"
                                :disabled="selectedCartIds.length === 0"
                                :class="{ 'opacity-50 cursor-not-allowed': selectedCartIds.length === 0 }"
                            >
                                <i class="fas fa-lock mr-1 text-[10px]"></i> {{ t('proceed_checkout') }}
                            </button>

                            <div class="text-center pt-2">
                                <Link 
                                    :href="route('menu.index')"
                                    class="text-xs font-semibold text-[#8C8275] hover:text-[#4A6B5D] uppercase tracking-widest transition-colors"
                                >
                                    <i class="fas fa-arrow-left mr-1"></i> {{ t('continue_shopping') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-else class="cart-card text-center py-20 flex flex-col items-center">
                    <div class="w-16 h-16 rounded-none bg-[#FAF6F0] text-[#8C8275] flex items-center justify-center text-xl mb-6 border border-[#E6E1DA]">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <h4 class="text-[#2D3330] font-normal font-serif-luxury text-2xl uppercase tracking-wider mb-2">{{ t('cart_empty') }}</h4>
                    <p class="text-[#8C8275] text-xs max-w-sm mt-1 mb-8 font-light leading-relaxed">{{ t('cart_empty_desc') }}</p>
                    <Link 
                        :href="route('menu.index')"
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold px-8 py-3.5 rounded-none text-xs uppercase tracking-widest transition-colors"
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
