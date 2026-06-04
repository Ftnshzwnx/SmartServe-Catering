<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';

defineProps({
    packages: {
        type: Array,
        required: true,
    },
    cartCount: {
        type: Number,
        default: 0,
    },
});

const { t } = useLocalization();

function getCategoryIcon(name) {
    const lower = name.toLowerCase();
    if (lower.includes('wedding') || lower.includes('kahwin') || lower.includes('sanding')) {
        return 'fa-heart text-[#8C3A3A] bg-[#FDF2F2] border border-[#FADCDD]';
    }
    if (lower.includes('corporate') || lower.includes('seminar') || lower.includes('office') || lower.includes('mesyuarat')) {
        return 'fa-briefcase text-[#3D574B] bg-[#EBEFEF] border border-[#D1DEDB]';
    }
    if (lower.includes('aqiqah') || lower.includes('cukur') || lower.includes('baby') || lower.includes('birthday') || lower.includes('lahir')) {
        return 'fa-birthday-cake text-[#6E5D4F] bg-[#FAF6F0] border border-[#EADED9]';
    }
    return 'fa-utensils text-[#4A6B5D] bg-[#FAF7F2] border border-[#E6E1DA]';
}
</script>

<template>
    <Head :title="t('catering_packages')" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .menu-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .menu-card:hover {
            transform: translateY(-4px);
            border-color: #4A6B5D;
            box-shadow: 0 12px 20px -8px rgba(74, 107, 93, 0.15);
        }
    </component>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between font-sans-modern">
                <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                    {{ t('catering_packages') }}
                </h2>
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

        <div class="py-16 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                <p class="text-[#8C8275] font-bold text-xs uppercase tracking-widest mb-2">{{ t('premium_selection') }}</p>
                <h3 class="text-4xl font-light text-[#1C201E] font-serif-luxury tracking-wide mb-12">{{ t('select_occasion') }}</h3>

                <div v-if="packages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Package Category Card -->
                    <div 
                        v-for="pkg in packages" 
                        :key="pkg.package_name"
                        class="bg-white p-8 rounded-lg border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-76 text-left menu-card"
                    >
                        <div>
                            <!-- Dynamic Icon based on package name instead of image -->
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-xl mb-6 border" :class="getCategoryIcon(pkg.package_name)">
                                <i class="fas" :class="getCategoryIcon(pkg.package_name).split(' ')[0]"></i>
                            </div>
                            
                            <h4 class="text-xl font-normal text-[#2D3330] font-serif-luxury mb-3 uppercase tracking-wider">
                                {{ pkg.package_name }}
                            </h4>
                            
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#EBEFEF] text-[#4A6B5D] border border-[#D1DEDB]">
                                {{ t('starting_from') }} RM {{ parseFloat(pkg.price).toFixed(2) }} / {{ t('pax') }}
                            </span>
                        </div>

                        <div class="mt-6">
                            <Link 
                                :href="route('menu.show', { category: pkg.package_name })"
                                class="w-full inline-flex items-center justify-center gap-2 bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3.5 px-6 rounded-lg text-xs uppercase tracking-widest transition-all duration-200"
                            >
                                <i class="fas fa-search-plus text-[10px]"></i> {{ t('view_packages') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 bg-white rounded-lg border border-[#E6E1DA] shadow-sm max-w-xl mx-auto">
                    <i class="fas fa-utensils fa-2x text-[#8C8275] mb-4"></i>
                    <h5 class="text-[#2D3330] font-serif-luxury text-xl mb-1">{{ t('no_packages_available') }}</h5>
                    <p class="text-xs text-[#8C8275]">{{ t('contact_admin_menu') }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
