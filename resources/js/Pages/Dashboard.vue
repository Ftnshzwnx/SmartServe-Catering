<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';

defineProps({
    cartCount: {
        type: Number,
        default: 0,
    },
    ordersCount: {
        type: Number,
        default: 0,
    },
});

const { t } = useLocalization();
</script>

<template>
    <Head :title="t('dashboard')" />

    <!-- Style injection for Outfit / Cormorant / Plus Jakarta fonts and premium elements -->
    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
        .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
        .banner-gradient {
            background: linear-gradient(135deg, #4A6B5D 0%, #364F44 100%);
        }
        .action-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .action-card:hover {
            transform: translateY(-4px);
            border-color: #4A6B5D;
            box-shadow: 0 12px 20px -8px rgba(74, 107, 93, 0.15);
        }
    </component>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-normal text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                {{ t('dashboard') }}
            </h2>
        </template>

        <div class="py-10 bg-[#FAF7F2] min-h-[calc(100vh-80px)] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-10">
                
                <!-- Welcome Banner -->
                <div class="banner-gradient rounded-3xl p-8 lg:p-12 text-white relative overflow-hidden shadow-md">
                    <!-- Subtle oatmeal circle background element -->
                    <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-white/5 blur-2xl"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h3 class="text-3xl lg:text-4xl font-light font-serif-luxury mb-2 tracking-wide">
                                {{ t('welcome_back') }}, {{ $page.props.auth.user.full_name || $page.props.auth.user.name }}!
                            </h3>
                            <p class="text-[#E2ECE8] text-xs lg:text-sm tracking-wide uppercase font-light max-w-xl">
                                {{ t('welcome_desc') }}
                            </p>
                        </div>
                        <div>
                            <Link 
                                :href="route('menu.index')"
                                class="inline-flex items-center gap-2 bg-[#FAF7F2] hover:bg-[#FAF7F2]/90 text-[#4A6B5D] text-xs font-semibold uppercase tracking-widest px-6 py-3 rounded-xl shadow-sm transition-all duration-200"
                            >
                                <i class="fas fa-utensils"></i> {{ t('order_now') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stat Card 1 -->
                    <div class="bg-white p-6 rounded-2xl border border-[#E6E1DA] shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest block mb-1">{{ t('shopping_cart') }}</span>
                            <span class="text-2xl font-normal text-[#2D3330] font-serif-luxury">{{ cartCount }} {{ t('packages') }}</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-lg">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                    </div>

                    <!-- Stat Card 2 -->
                    <div class="bg-white p-6 rounded-2xl border border-[#E6E1DA] shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-semibold text-[#8C8275] uppercase tracking-widest block mb-1">{{ t('total_bookings') }}</span>
                            <span class="text-2xl font-normal text-[#2D3330] font-serif-luxury">{{ ordersCount }} {{ t('orders') }}</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-lg">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div>
                    <h4 class="text-xs font-bold text-[#8C8275] uppercase tracking-widest mb-6">{{ t('quick_actions') }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Action 1 -->
                        <div class="bg-white p-6 rounded-2xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-56 action-card">
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base mb-4">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h5 class="font-normal text-[#2D3330] text-lg font-serif-luxury tracking-wide mb-1">{{ t('our_menu') }}</h5>
                                <p class="text-xs text-[#8C8275] leading-relaxed font-light">{{ t('our_menu_desc') }}</p>
                            </div>
                            <Link :href="route('menu.index')" class="text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-1 mt-4">
                                {{ t('browse_menu') }} <i class="fas fa-chevron-right text-[10px]"></i>
                            </Link>
                        </div>

                        <!-- Action 2 -->
                        <div class="bg-white p-6 rounded-2xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-56 action-card">
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base mb-4">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <h5 class="font-normal text-[#2D3330] text-lg font-serif-luxury tracking-wide mb-1">{{ t('budget_planner') }}</h5>
                                <p class="text-xs text-[#8C8275] leading-relaxed font-light">{{ t('budget_planner_desc') }}</p>
                            </div>
                            <Link :href="route('budget.planner')" class="text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-1 mt-4">
                                {{ t('open_planner') }} <i class="fas fa-chevron-right text-[10px]"></i>
                            </Link>
                        </div>

                        <!-- Action 3 -->
                        <div class="bg-white p-6 rounded-2xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-56 action-card">
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base mb-4">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <h5 class="font-normal text-[#2D3330] text-lg font-serif-luxury tracking-wide mb-1">{{ t('my_orders') }}</h5>
                                <p class="text-xs text-[#8C8275] leading-relaxed font-light">{{ t('my_orders_desc') }}</p>
                            </div>
                            <Link :href="route('orders.index')" class="text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-1 mt-4">
                                {{ t('track_orders') }} <i class="fas fa-chevron-right text-[10px]"></i>
                            </Link>
                        </div>

                        <!-- Action 4 -->
                        <div class="bg-white p-6 rounded-2xl border border-[#E6E1DA] shadow-sm flex flex-col justify-between h-56 action-card">
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-[#EBEFEF] text-[#4A6B5D] flex items-center justify-center text-base mb-4">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <h5 class="font-normal text-[#2D3330] text-lg font-serif-luxury tracking-wide mb-1">{{ t('edit_profile') }}</h5>
                                <p class="text-xs text-[#8C8275] leading-relaxed font-light">{{ t('edit_profile_desc') }}</p>
                            </div>
                            <Link :href="route('profile.edit')" class="text-xs font-bold uppercase tracking-wider text-[#4A6B5D] hover:text-[#3D574B] flex items-center gap-1 mt-4">
                                {{ t('settings') }} <i class="fas fa-chevron-right text-[10px]"></i>
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
