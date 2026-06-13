<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';

const toasts = ref([]);
let idCounter = 0;
const { t, currentLanguage } = useLocalization();

function translateFlashMessage(message) {
    if (!message) return '';
    
    const normalized = message.trim();
    const isEn = currentLanguage.value === 'en';
    
    // Dynamic message: "Customer has been suspended successfully."
    if (normalized.startsWith('Customer has been suspended successfully.')) {
        return t('admin_toast_customer_suspended');
    }
    if (normalized.startsWith('Customer has been active successfully.') || normalized.startsWith('Customer has been activated successfully.')) {
        return t('admin_toast_customer_activated');
    }
    
    // Map of English backend flash messages to translation keys
    const mapping = {
        'Thank you for your feedback! Your review has been submitted.': 'review_success',
        'Order cancelled successfully.': 'toast_order_cancelled',
        'Receipt uploaded. Waiting for verification.': 'toast_receipt_uploaded',
        'Package added to cart successfully!': 'toast_added_to_cart',
        'Cart item updated successfully!': 'toast_cart_updated',
        'Cart updated successfully.': 'toast_cart_updated',
        'Item removed from cart.': 'toast_item_removed',
        'Order status updated and customer notified.': 'admin_toast_status_updated',
        'Tempahan telah ditandakan sebagai sedang dihantar dan pelanggan telah dimaklumkan.': 'admin_toast_delivered_updated',
        'Order status has been updated to Delivered and customer notified.': 'admin_toast_delivered_updated',
        'Custom menu proposal sent successfully to customer.': 'admin_proposal_sent_toast',
        'Category added successfully.': 'admin_toast_cat_created',
        'Category updated successfully.': 'admin_toast_cat_updated',
        'Category deleted successfully.': 'admin_toast_cat_deleted',
        'Package created successfully.': 'admin_toast_pkg_created',
        'Package updated successfully.': 'admin_toast_pkg_updated',
        'Package deleted.': 'admin_toast_pkg_deleted',
        'Addon added successfully.': 'admin_toast_addon_created',
        'Addon updated successfully.': 'admin_toast_addon_updated',
        'Addon deleted successfully.': 'admin_toast_addon_deleted',
        'Dish added successfully.': 'admin_toast_dish_created',
        'Dish updated successfully.': 'admin_toast_dish_updated',
        'Dish deleted successfully.': 'admin_toast_dish_deleted',
        'Settings updated.': 'admin_settings_toast_settings_updated',
        'Delivery zone created successfully.': 'admin_settings_toast_zone_created',
        'Delivery zone updated successfully.': 'admin_settings_toast_zone_updated',
        'Delivery zone deleted successfully.': 'admin_settings_toast_zone_deleted',
        'Reply submitted successfully.': 'admin_reviews_toast_reply_submitted',
        'Date has been blocked successfully.': 'admin_calendar_toast_date_blocked',
        'Blocked date removed.': 'admin_calendar_toast_unblocked',
        'Promo code created successfully.': 'admin_toast_promo_created',
        'Promo code deleted.': 'admin_toast_promo_deleted',
        'Your message has been sent successfully. We will get back to you soon!': 'inquiry_success',
        'All categories have been successfully deleted.': 'admin_toast_all_categories_deleted',
        'All packages have been successfully deleted.': 'admin_toast_all_packages_deleted',
        'All add-ons have been successfully deleted.': 'admin_toast_all_addons_deleted',
        'All dishes have been successfully deleted.': 'admin_toast_all_dishes_deleted',
        
        // Errors / other statuses
        'Please select items to checkout.': 'checkout_select_items',
        'No valid items selected.': 'checkout_no_valid_items',
        'Checkout session expired.': 'checkout_session_expired',
        'No items in checkout.': 'checkout_no_items',
        'This order cannot be cancelled.': 'order_cannot_be_cancelled',
        'Receipt is not available yet.': 'receipt_not_available',
    };

    // Handle dynamic message format: "Order #X placed! Waiting for admin verification."
    const orderPlacedMatch = normalized.match(/^Order #\d+ placed! Waiting for admin verification\.$/);
    if (orderPlacedMatch) {
        const idMatch = normalized.match(/#\d+/);
        const orderId = idMatch ? idMatch[0] : '';
        return isEn 
            ? `Order ${orderId} placed! Waiting for admin verification.`
            : `Tempahan ${orderId} berjaya dibuat! Menunggu pengesahan admin.`;
    }

    // Handle dynamic cancel policy notice
    const cancelPolicyMatch = normalized.match(/^Cancellations must be made at least (\d+) days before the event\.$/);
    if (cancelPolicyMatch) {
        const days = cancelPolicyMatch[1];
        return isEn 
            ? `Cancellations must be made at least ${days} days before the event.`
            : `Pembatalan mestilah dibuat sekurang-kurangnya ${days} hari sebelum tarikh majlis.`;
    }

    if (normalized === 'You have already submitted a review for this order.') {
        return isEn 
            ? 'You have already submitted a review for this order.'
            : 'Anda telah pun menghantar ulasan untuk tempahan ini.';
    }

    if (normalized === 'Your custom menu request has been submitted! Waiting for owner proposal.') {
        return isEn
            ? 'Your custom menu request has been submitted! Waiting for owner proposal.'
            : 'Permintaan menu khas anda telah dihantar! Menunggu sebut harga daripada pemilik.';
    }

    if (normalized === 'Proposal approved! Please upload your 30% deposit payment slip to confirm your booking date.') {
        return isEn
            ? 'Proposal approved! Please upload your 30% deposit payment slip to confirm your booking date.'
            : 'Cadangan menu khas diluluskan! Sila muat naik resit bayaran deposit 30% untuk mengesahkan tarikh tempahan anda.';
    }

    if (normalized === 'Proposal rejected and cancelled.') {
        return isEn
            ? 'Proposal rejected and cancelled.'
            : 'Cadangan menu ditolak dan dibatalkan.';
    }

    if (normalized === 'Tempahan telah ditandakan sebagai sedang dihantar dan pelanggan telah dimaklumkan.') {
        return isEn 
            ? 'Order status has been updated to Delivered and customer notified.'
            : 'Tempahan telah ditandakan sebagai dihantar dan pelanggan telah dimaklumkan.';
    }

    const key = mapping[normalized];
    return key ? t(key) : message;
}

function addToast(message, type = 'success') {
    if (!message) return;
    
    // De-duplicate: check if a toast with the same message and type was added very recently
    const exists = toasts.value.some(t => t.message === message && t.type === type);
    if (exists) return;
    
    const id = idCounter++;
    toasts.value.push({ id, message, type });
    
    // Auto remove after 4.5 seconds
    setTimeout(() => {
        removeToast(id);
    }, 4500);
}

function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

// Global window event listener for client-side toasts
const handleToastEvent = (e) => {
    if (e.detail) {
        addToast(e.detail.message, e.detail.type);
    }
};

const page = usePage();

// Watch for Inertia flash messages from Laravel redirect
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            addToast(translateFlashMessage(flash.success), 'success');
            page.props.flash.success = null;
        }
        if (flash?.error) {
            addToast(translateFlashMessage(flash.error), 'error');
            page.props.flash.error = null;
        }
        if (flash?.status) {
            addToast(translateFlashMessage(flash.status), 'info');
            page.props.flash.status = null;
        }
    },
    { deep: true, immediate: true }
);

onMounted(() => {
    window.addEventListener('toast-notify', handleToastEvent);
});

onUnmounted(() => {
    window.removeEventListener('toast-notify', handleToastEvent);
});
</script>

<template>
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-4"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full items-center gap-3 rounded-2xl bg-white p-4 shadow-xl border-l-4 transition-all duration-300 relative overflow-hidden"
                :class="[
                    toast.type === 'success' ? 'border-[#4A6B5D]' : '',
                    toast.type === 'error' ? 'border-rose-600' : '',
                    toast.type === 'info' ? 'border-[#C5A880]' : '',
                ]"
            >
                <!-- Icon badge -->
                <div class="flex-shrink-0 flex items-center justify-center">
                    <span v-if="toast.type === 'success'" class="text-[#4A6B5D] text-lg flex items-center">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <span v-else-if="toast.type === 'error'" class="text-rose-600 text-lg flex items-center">
                        <i class="fas fa-exclamation-circle"></i>
                    </span>
                    <span v-else class="text-[#C5A880] text-lg flex items-center">
                        <i class="fas fa-info-circle"></i>
                    </span>
                </div>
                
                <!-- Content text -->
                <div class="flex-1 pr-6 flex items-center">
                    <p class="text-xs font-bold text-[#2D3330] leading-relaxed">{{ toast.message }}</p>
                </div>
                
                <!-- Close Button -->
                <button
                    @click="removeToast(toast.id)"
                    class="text-[#8C8275] hover:text-[#2D3330] p-1 rounded-lg hover:bg-[#FAF7F2] transition-colors cursor-pointer absolute top-1/2 -translate-y-1/2 right-3"
                >
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
