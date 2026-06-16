<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useLocalization } from '@/Composables/useLocalization';
import ToastList from '@/Components/ToastList.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Admin Panel',
    },
    headerTitle: {
        type: String,
        required: true,
    },
    headerDesc: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const isCollapsed = ref(false);
const isMobileOpen = ref(false);

const { t, setLanguage, currentLanguage, translateNotification } = useLocalization();

onMounted(() => {
    isCollapsed.value = localStorage.getItem('ssc_admin_sidebar_collapsed') === 'true';
});

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    localStorage.setItem('ssc_admin_sidebar_collapsed', isCollapsed.value);
};

const toggleMobileMenu = () => {
    isMobileOpen.value = !isMobileOpen.value;
};

const toggleLanguage = () => {
    setLanguage(currentLanguage.value === 'en' ? 'my' : 'en');
};

const notifications = computed(() => page.props.auth.notifications || []);
const unreadCount = computed(() => page.props.auth.unread_notifications_count || 0);

const showNotificationsDropdown = ref(false);
const toggleNotificationsDropdown = () => {
    showNotificationsDropdown.value = !showNotificationsDropdown.value;
};

const handleNotificationClick = (notification) => {
    showNotificationsDropdown.value = false;
    router.post(route('notifications.read', notification.id), { redirect: true }, {
        preserveScroll: true
    });
};

const markAllNotificationsAsRead = () => {
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true
    });
};

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days === 1) return 'Yesterday';
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
};
</script>

<template>
    <Head :title="title" />

    <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        .font-sans-modern {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-serif-luxury {
            font-family: 'Cormorant Garamond', serif;
        }
        
        /* Premium Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #FAF8F5;
        }
        ::-webkit-scrollbar-thumb {
            background: #E6E1DA;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #C5A880;
        }
    </component>

    <div class="min-h-screen bg-[#FAF8F5] font-sans-modern text-[#2D3330] flex flex-col md:flex-row">
        
        <!-- 1. DESKTOP SIDEBAR (Deep Forest Green Theme, Sticky/Fixed) -->
        <aside 
            class="hidden md:flex flex-col justify-between fixed top-0 bottom-0 left-0 bg-[#1B2A22] text-white p-5 border-r border-[#24372D] z-30 transition-all duration-300 ease-in-out shrink-0"
            :class="isCollapsed ? 'w-20' : 'w-64'"
        >
            <div class="space-y-6">
                <!-- Branding logo -->
                <div 
                    class="flex items-center pb-5 border-b border-[#24372D] text-white transition-all duration-300"
                    :class="isCollapsed ? 'justify-center' : 'justify-start gap-3'"
                >
                    <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center border border-white/10 shrink-0">
                        <!-- Crisp Vector SVG Icon -->
                        <svg class="h-7 w-7 shrink-0" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Spoon (Gold) -->
                            <path d="M56 26 C56 18, 64 18, 64 26 C64 34, 56 34, 56 26 Z" fill="#C5A880" />
                            <path d="M60 32 L60 58" stroke="#C5A880" stroke-width="3" stroke-linecap="round" />
                            
                            <!-- Fork (Sage Green) -->
                            <path d="M40 28 L40 39" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                            <path d="M34 28 C34 36, 46 36, 46 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" fill="none" />
                            <path d="M34 22 L34 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                            <path d="M46 22 L46 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                            <path d="M40 22 L40 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                            
                            <!-- Leaves / Organic accents -->
                            <!-- Left leaf -->
                            <path d="M22 38 C26 34, 30 40, 24 44 C20 46, 18 42, 22 38 Z" fill="#4A6B5D" opacity="0.85" />
                            <!-- Right leaf -->
                            <path d="M78 38 C74 34, 70 40, 76 44 C80 46, 82 42, 78 38 Z" fill="#C5A880" opacity="0.85" />
                            
                            <!-- Elegant connecting horizontal line with a small diamond in center -->
                            <path d="M15 52 L85 52" stroke="#E6E1DA" stroke-width="1.5" />
                            <path d="M50 49 L53 52 L50 55 L47 52 Z" fill="#C5A880" />
                        </svg>
                    </div>
                    <div v-show="!isCollapsed" class="overflow-hidden transition-all duration-300">
                        <span class="text-base font-extrabold tracking-tight font-serif-luxury block leading-none">
                            Smart<span class="text-[#C5A880]">Serve</span>
                        </span>
                        <span class="text-[9px] uppercase tracking-widest font-bold text-white/50 block mt-0.5">{{ t('admin_portal') }}</span>
                    </div>
                </div>

                <!-- Nav Menu Links -->
                <div class="space-y-4">
                    <div>
                        <span v-show="!isCollapsed" class="text-[9px] uppercase tracking-widest font-bold text-white/40 px-3 mb-2 block select-none">{{ t('admin_management') }}</span>
                        <nav class="space-y-1">
                            <Link 
                                :href="route('admin.dashboard')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.dashboard') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_dashboard') : ''"
                            >
                                <i class="fas fa-chart-line text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_dashboard') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.customers')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.customers') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_customers') : ''"
                            >
                                <i class="fas fa-users text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_customers') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.orders')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.orders') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_manage_orders') : ''"
                            >
                                <i class="fas fa-receipt text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_manage_orders') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.packages')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.packages') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_catering_packages') : ''"
                            >
                                <i class="fas fa-utensils text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_catering_packages') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.calendar')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.calendar') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_booking_calendar') : ''"
                            >
                                <i class="fas fa-calendar text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_booking_calendar') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.reviews')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.reviews') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_customer_reviews') : ''"
                            >
                                <i class="fas fa-star text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_customer_reviews') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.promos')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.promos') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_promo_codes') : ''"
                            >
                                <i class="fas fa-ticket-alt text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_promo_codes') }}</span>
                            </Link>

                            <Link 
                                :href="route('admin.reports')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.reports') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_reports_analytics') : ''"
                            >
                                <i class="fas fa-chart-bar text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_reports_analytics') }}</span>
                            </Link>
                        </nav>
                    </div>

                    <div>
                        <span v-show="!isCollapsed" class="text-[9px] uppercase tracking-widest font-bold text-white/40 px-3 mb-2 block select-none">{{ t('admin_configuration') }}</span>
                        <nav class="space-y-1">
                            <Link 
                                :href="route('admin.settings')" 
                                class="flex items-center rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 border-l-4"
                                :class="[
                                    route().current('admin.settings') 
                                        ? 'bg-white/5 text-[#C5A880] border-[#C5A880]' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5 border-transparent',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('admin_system_settings') : ''"
                            >
                                <i class="fas fa-cogs text-sm w-5 text-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('admin_system_settings') }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Bottom Profile & Logout (VMS style, logout next to name) -->
            <div class="border-t border-[#24372D] pt-4 mt-auto">
                <div 
                    class="flex items-center justify-between rounded-xl transition-all duration-300"
                    :class="isCollapsed ? 'flex-col gap-3 p-1 bg-transparent border-0' : 'p-3 bg-white/5 border border-white/10'"
                >
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <!-- Avatar -->
                        <img v-if="user?.profile_image" :src="'/storage/' + user.profile_image" class="w-8 h-8 rounded-full object-cover shrink-0 shadow-sm" />
                        <div v-else class="w-8 h-8 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-xs shadow-md shrink-0 select-none">
                            {{ (user?.name || 'A').charAt(0).toUpperCase() }}
                        </div>
                        <!-- Profile details -->
                        <div class="overflow-hidden transition-all duration-300" :class="isCollapsed ? 'w-0 opacity-0' : 'w-full opacity-100'">
                            <h4 class="text-xs font-bold text-white truncate">{{ user?.name || 'Admin' }}</h4>
                            <p class="text-[9px] text-[#C5A880] truncate uppercase font-semibold">{{ t('admin_role') }}</p>
                        </div>
                    </div>
                    
                    <!-- Logout button arrow next to name -->
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        class="text-white/60 hover:text-rose-400 p-1.5 rounded-lg hover:bg-rose-500/10 transition-colors cursor-pointer shrink-0"
                        :class="isCollapsed ? 'w-8 h-8 border border-white/10 rounded-lg bg-white/5 flex items-center justify-center' : ''"
                        :title="t('admin_logout')"
                    >
                        <i class="fas fa-sign-out-alt text-xs"></i>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- 2. CONTENT FRAME (shifted to clear fixed sidebar, scrollable content) -->
        <div 
            class="flex-grow flex flex-col min-h-screen transition-all duration-300 ease-in-out"
            :class="isCollapsed ? 'md:pl-20' : 'md:pl-64'"
        >
            
            <!-- 3. TOP HEADER BAR (Sticky, VMS Style with notifications & profile dropdown) -->
            <header class="bg-white border-b border-[#E6E1DA] h-16 px-6 md:px-8 flex items-center justify-between sticky top-0 z-40 w-full">
                    <!-- Left Section: Toggle & Breadcrumbs -->
                    <div class="flex items-center gap-4">
                        <!-- Desktop Sidebar Toggle -->
                        <button 
                            @click="toggleCollapse"
                            class="hidden md:flex w-9 h-9 border border-[#E6E1DA] rounded-xl items-center justify-center text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors cursor-pointer shrink-0"
                            :title="isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
                        >
                            <i class="fas" :class="isCollapsed ? 'fa-indent' : 'fa-outdent'"></i>
                        </button>
                        
                        <!-- Mobile Sidebar Toggle -->
                        <button 
                            @click="toggleMobileMenu"
                            class="md:hidden w-9 h-9 border border-[#E6E1DA] rounded-xl flex items-center justify-center text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors cursor-pointer shrink-0"
                        >
                            <i class="fas fa-bars"></i>
                        </button>
 
                        <!-- Breadcrumbs (Tajdid style) -->
                        <div class="flex items-center text-[10px] font-bold tracking-wider select-none font-sans-modern">
                            <Link :href="route('admin.dashboard')" class="text-[#8C8275] hover:text-[#4A6B5D] uppercase transition-colors">
                                Dashboard
                            </Link>
                            <span class="text-[#8C8275]/60 mx-2 text-xs font-normal">&rsaquo;</span>
                            <span class="text-[#4A6B5D] uppercase">
                                {{ headerTitle }}
                            </span>
                        </div>
                    </div>
 
                <!-- Right Section: Language, Notification, Profile Dropdown -->
                <div class="flex items-center gap-3">
                    <!-- Language selector -->
                    <button 
                        @click="toggleLanguage"
                        class="w-16 h-9 border border-[#E6E1DA] rounded-xl flex items-center justify-center gap-1.5 text-xs font-semibold text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors cursor-pointer shrink-0"
                        :title="`Switch to ${currentLanguage === 'en' ? 'Bahasa Melayu' : 'English'}`"
                    >
                        <i class="fas fa-globe text-[#8C8275]"></i>
                        <span>{{ currentLanguage.toUpperCase() }}</span>
                    </button>
 
                    <!-- Notification Bell Icon (VMS style) & Dropdown -->
                    <div class="relative">
                        <button 
                            @click="toggleNotificationsDropdown"
                            class="w-9 h-9 border border-[#E6E1DA] rounded-xl flex items-center justify-center text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] relative transition-colors cursor-pointer"
                            title="Notifications"
                        >
                            <i class="far fa-bell text-[#8C8275]"></i>
                            <!-- Soft dot indicator for notifications -->
                            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-[#8C3A3A] text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm">
                                {{ unreadCount }}
                            </span>
                        </button>

                        <!-- Dropdown Overlay to click-away -->
                        <div v-if="showNotificationsDropdown" class="fixed inset-0 z-40" @click="showNotificationsDropdown = false"></div>

                        <!-- Dropdown List -->
                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div 
                                v-show="showNotificationsDropdown" 
                                class="absolute right-0 mt-2 w-80 bg-white border border-[#E6E1DA] rounded-2xl shadow-xl z-50 overflow-hidden"
                            >
                                <!-- Header -->
                                <div class="px-4 py-3 border-b border-[#E6E1DA] flex justify-between items-center bg-[#FAF8F5]">
                                    <span class="text-xs font-bold text-[#2D3330] uppercase tracking-wider">{{ t('notifications') }}</span>
                                    <button 
                                        v-if="unreadCount > 0"
                                        @click="markAllNotificationsAsRead"
                                        class="text-[10px] text-[#4A6B5D] hover:underline font-semibold"
                                    >
                                        {{ t('mark_all_read') }}
                                    </button>
                                </div>

                                <!-- Body / List -->
                                <div class="max-h-80 overflow-y-auto divide-y divide-[#E6E1DA]/60">
                                    <div v-if="notifications.length === 0" class="p-6 text-center text-xs text-[#8C8275]">
                                        <i class="far fa-bell-slash text-lg mb-2 block opacity-40"></i>
                                        {{ t('no_notifications_yet') }}
                                    </div>
                                    <div 
                                        v-else 
                                        v-for="item in notifications" 
                                        :key="item.id"
                                        @click="handleNotificationClick(item)"
                                        class="px-4 py-3.5 hover:bg-[#FAF7F2]/50 transition-colors cursor-pointer flex gap-3 text-left items-start"
                                        :class="!item.read_at ? 'bg-[#FAF7F2]' : ''"
                                    >
                                        <div class="flex-grow space-y-1">
                                            <div class="flex justify-between items-start">
                                                <h4 class="text-xs font-bold text-[#2D3330] leading-snug">
                                                    {{ translateNotification(item).title }}
                                                </h4>
                                                <span class="text-[9px] text-[#8C8275] whitespace-nowrap ml-2">
                                                    {{ formatTimeAgo(item.created_at) }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-[#5C6460] leading-relaxed">
                                                {{ translateNotification(item).message }}
                                            </p>
                                        </div>
                                        <span v-if="!item.read_at" class="w-1.5 h-1.5 bg-[#4A6B5D] rounded-full mt-1.5 shrink-0"></span>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
 
                    <!-- User Detail Avatar with Dropdown -->
                    <div class="flex items-center border-l border-[#E6E1DA] pl-4 relative z-50">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-3 text-left cursor-pointer focus:outline-none select-none">
                                    <div class="hidden sm:block text-center">
                                        <div class="text-xs font-semibold text-[#2D3330] leading-none mb-1 text-center">
                                            <span class="capitalize">{{ user?.name || 'Admin' }}</span>
                                        </div>
                                        <div class="text-[9px] font-medium text-[#8C8275] leading-none uppercase tracking-wider text-center">{{ t('admin_role') }}</div>
                                    </div>
                                    <img v-if="user?.profile_image" :src="'/storage/' + user.profile_image" class="w-8 h-8 rounded-full object-cover shrink-0 shadow-xs border border-[#E6E1DA]" />
                                    <div v-else class="w-8 h-8 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center font-bold text-xs shadow-xs shrink-0 select-none">
                                        {{ (user?.name || 'A').charAt(0).toUpperCase() }}
                                    </div>
                                </button>
                            </template>
 
                            <template #content>
                                <DropdownLink :href="route('admin.settings')" class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-cogs text-[#8C8275]"></i>
                                    <span>{{ t('admin_system_settings') }}</span>
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-2 text-xs text-left">
                                    <i class="fas fa-sign-out-alt text-[#8C8275]"></i>
                                    <span>{{ t('admin_logout') }}</span>
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>
 
            <!-- Main Page Content Slot -->
            <main class="flex-grow p-6 md:p-8 space-y-6 overflow-y-auto">
                <!-- Page Title Card (Tajdid style, in body) -->
                <div v-if="headerTitle" class="bg-white rounded-3xl border border-[#E6E1DA] p-6 md:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-2">
                        <h1 class="text-xl font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide leading-none">
                            {{ headerTitle }}
                        </h1>
                        <p v-if="headerDesc" class="text-xs text-[#8C8275] font-semibold leading-relaxed tracking-wide">
                            {{ headerDesc }}
                        </p>
                    </div>
                    <!-- Right header action/badge slot -->
                    <div v-if="$slots['header-action']" class="flex-shrink-0">
                        <slot name="header-action" />
                    </div>
                </div>
                
                <slot />
            </main>
        </div>

        <!-- 4. MOBILE SIDEBAR DRAWER OVERLAY -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="isMobileOpen" 
                class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-xs md:hidden"
                @click.self="isMobileOpen = false"
            >
                <Transition
                    enter-active-class="transition duration-300 ease-out transform"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition duration-200 ease-in transform"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <aside v-if="isMobileOpen" class="fixed top-0 bottom-0 left-0 w-64 bg-[#1B2A22] text-white border-r border-[#24372D] z-50 p-5 flex flex-col justify-between overflow-y-auto">
                        <div class="space-y-6">
                            <!-- Branding logo & close button -->
                            <div class="flex items-center justify-between pb-5 border-b border-[#24372D]">
                                <Link :href="route('admin.dashboard')" class="flex items-center text-white gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center border border-white/10 shrink-0">
                                        <!-- Crisp Vector SVG Icon -->
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <!-- Spoon (Gold) -->
                                            <path d="M56 26 C56 18, 64 18, 64 26 C64 34, 56 34, 56 26 Z" fill="#C5A880" />
                                            <path d="M60 32 L60 58" stroke="#C5A880" stroke-width="3" stroke-linecap="round" />
                                            
                                            <!-- Fork (Sage Green) -->
                                            <path d="M40 28 L40 39" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                                            <path d="M34 28 C34 36, 46 36, 46 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" fill="none" />
                                            <path d="M34 22 L34 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                                            <path d="M46 22 L46 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                                            <path d="M40 22 L40 28" stroke="#4A6B5D" stroke-width="3" stroke-linecap="round" />
                                            
                                            <!-- Leaves / Organic accents -->
                                            <!-- Left leaf -->
                                            <path d="M22 38 C26 34, 30 40, 24 44 C20 46, 18 42, 22 38 Z" fill="#4A6B5D" opacity="0.85" />
                                            <!-- Right leaf -->
                                            <path d="M78 38 C74 34, 70 40, 76 44 C80 46, 82 42, 78 38 Z" fill="#C5A880" opacity="0.85" />
                                            
                                            <!-- Elegant connecting horizontal line with a small diamond in center -->
                                            <path d="M15 52 L85 52" stroke="#E6E1DA" stroke-width="1.5" />
                                            <path d="M50 49 L53 52 L50 55 L47 52 Z" fill="#C5A880" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-bold font-serif-luxury leading-none">Smart<span class="text-[#C5A880]">Serve</span> Admin</span>
                                </Link>
                                <button 
                                    @click="isMobileOpen = false"
                                    class="w-8 h-8 border border-white/10 rounded-lg flex items-center justify-center text-white/60 hover:text-white"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Nav Menu Links (Mobile) -->
                            <div class="space-y-4" @click="isMobileOpen = false">
                                <div>
                                    <span class="text-[9px] uppercase tracking-widest font-bold text-white/40 px-3 mb-2 block select-none">{{ t('admin_management') }}</span>
                                    <nav class="space-y-1">
                                        <Link :href="route('admin.dashboard')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-chart-line text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_dashboard') }}</span>
                                        </Link>
                                        <Link :href="route('admin.customers')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-users text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_customers') }}</span>
                                        </Link>
                                        <Link :href="route('admin.orders')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-receipt text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_manage_orders') }}</span>
                                        </Link>
                                        <Link :href="route('admin.packages')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-utensils text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_catering_packages') }}</span>
                                        </Link>
                                        <Link :href="route('admin.calendar')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-calendar text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_booking_calendar') }}</span>
                                        </Link>
                                        <Link :href="route('admin.reviews')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-star text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_customer_reviews') }}</span>
                                        </Link>
                                        <Link :href="route('admin.promos')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-ticket-alt text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_promo_codes') }}</span>
                                        </Link>
                                        <Link :href="route('admin.reports')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-chart-bar text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_reports_analytics') }}</span>
                                        </Link>
                                    </nav>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase tracking-widest font-bold text-white/40 px-3 mb-2 block select-none">{{ t('admin_configuration') }}</span>
                                    <nav class="space-y-1">
                                        <Link :href="route('admin.settings')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
                                            <i class="fas fa-cogs text-sm w-5 text-center"></i>
                                            <span>{{ t('admin_system_settings') }}</span>
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- User profile bottom Mobile -->
                        <div class="border-t border-white/10 pt-4">
                            <div class="flex items-center justify-between p-3 bg-white/5 border border-white/10 rounded-xl">
                                <div class="flex items-center gap-2.5">
                                    <img v-if="user?.profile_image" :src="'/storage/' + user.profile_image" class="w-8 h-8 rounded-full object-cover shadow-sm shrink-0" />
                                    <div v-else class="w-8 h-8 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-xs shadow-md shrink-0">
                                        {{ (user?.name || 'A').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-white">{{ user?.name || 'Admin' }}</h4>
                                        <p class="text-[9px] text-[#C5A880] uppercase tracking-wider font-semibold">{{ t('admin_role') }}</p>
                                    </div>
                                </div>
                                <Link 
                                    :href="route('logout')" 
                                    method="post" 
                                    as="button" 
                                    class="text-white/60 hover:text-rose-400 p-1.5 rounded-lg hover:bg-rose-500/10 transition-colors cursor-pointer"
                                    @click="isMobileOpen = false"
                                    :title="t('admin_logout')"
                                >
                                    <i class="fas fa-sign-out-alt text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Transition>

        <!-- Dynamic Toast Notification Banners -->
        <ToastList />

        <!-- Premium Confirmation / Prompt Dialog Modal -->
        <ConfirmModal />
    </div>
</template>
