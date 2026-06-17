<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';
import ToastList from '@/Components/ToastList.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    headerTitle: {
        type: String,
        default: '',
    },
    headerDesc: {
        type: String,
        default: '',
    },
});

const isMobileOpen = ref(false);
const isCollapsed = ref(false);
const { t, setLanguage, currentLanguage } = useLocalization();

// Inactivity timeout warning handler
const showTimeoutWarning = ref(false);
const warningCountdown = ref(30); // 30 seconds warning countdown
let inactivityTimer = null;
let countdownInterval = null;

const INACTIVITY_LIMIT = 1 * 60 * 1000; // 1 minute inactivity
const LOGOUT_LIMIT = 30 * 1000; // 30 seconds countdown

function resetInactivityTimer() {
    showTimeoutWarning.value = false;
    warningCountdown.value = 30;
    
    if (countdownInterval) clearInterval(countdownInterval);
    if (inactivityTimer) clearTimeout(inactivityTimer);

    inactivityTimer = setTimeout(() => {
        showTimeoutWarning.value = true;
        startWarningCountdown();
    }, INACTIVITY_LIMIT);
}

function startWarningCountdown() {
    countdownInterval = setInterval(() => {
        warningCountdown.value--;
        if (warningCountdown.value <= 0) {
            clearInterval(countdownInterval);
            forceLogout();
        }
    }, 1000);
}

function keepSessionActive() {
    router.reload({ preserveScroll: true });
    resetInactivityTimer();
}

function forceLogout() {
    router.post(route('logout'), { timeout: 1 });
}

const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

onMounted(() => {
    isCollapsed.value = localStorage.getItem('ssc_sidebar_collapsed') === 'true';
    
    // Register inactivity listeners
    activityEvents.forEach(event => {
        window.addEventListener(event, resetInactivityTimer);
    });
    
    resetInactivityTimer();
});

onUnmounted(() => {
    // Cleanup listeners
    activityEvents.forEach(event => {
        window.removeEventListener(event, resetInactivityTimer);
    });
    if (inactivityTimer) clearTimeout(inactivityTimer);
    if (countdownInterval) clearInterval(countdownInterval);
});

const toggleMobileMenu = () => {
    isMobileOpen.value = !isMobileOpen.value;
};

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    localStorage.setItem('ssc_sidebar_collapsed', isCollapsed.value);
};

const toggleLanguage = () => {
    setLanguage(currentLanguage.value === 'en' ? 'my' : 'en');
};

const page = usePage();
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
    
    if (seconds < 60) return t('time_just_now');
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}${t('time_min_ago')}`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}${t('time_hr_ago')}`;
    const days = Math.floor(hours / 24);
    if (days === 1) return t('time_yesterday');
    if (days < 7) return `${days}${t('time_day_ago')}`;
    return date.toLocaleDateString();
};
</script>

<template>
    <div class="min-h-screen bg-[#FAF7F2] font-sans-modern text-[#2D3330] flex flex-col md:flex-row">
        
        <!-- Google Fonts loading directly -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
            .font-serif-luxury { font-family: 'Plus Jakarta Sans', sans-serif; }
            .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
            
            /* Sidebar styles matching Tajdid VMS (Light/White theme) */
            .sidebar-light {
                background-color: #FFFFFF;
            }
            .sidebar-link-light {
                color: #5C6460;
                transition: all 0.2s ease;
            }
            .sidebar-link-light:hover {
                color: #4A6B5D;
                background-color: rgba(74, 107, 93, 0.04);
            }
            .sidebar-link-light.active {
                color: #4A6B5D;
                background-color: rgba(74, 107, 93, 0.08);
                font-weight: 600;
            }
            
            /* CSS override to unify page title styling from slot header to match VMS dashboard */
            .vms-header h2 {
                font-size: 1.125rem !important; /* text-lg */
                font-weight: 700 !important; /* font-bold */
                color: #2D3330 !important; /* charcoal */
                text-transform: capitalize !important;
                letter-spacing: normal !important;
                margin: 0 !important;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
            }
        </component>

        <!-- 1. DESKTOP SIDEBAR (White/Light Theme) -->
        <aside 
            class="hidden md:flex flex-col justify-between fixed top-0 bottom-0 left-0 sidebar-light text-[#2D3330] border-r border-[#E6E1DA] z-30 p-5 transition-all duration-300 ease-in-out shrink-0"
            :class="isCollapsed ? 'w-20' : 'w-64'"
        >
            <div class="space-y-6">
                <!-- Branding logo -->
                <div 
                    class="flex items-center pb-5 border-b border-[#E6E1DA] text-[#2D3330] transition-all duration-300"
                    :class="isCollapsed ? 'justify-center' : 'justify-start'"
                >
                    <Link :href="route('dashboard')" class="flex items-center text-[#2D3330]">
                        <ApplicationLogo :collapsed="isCollapsed" />
                    </Link>
                </div>

                <!-- Nav Menu Links grouped -->
                <div class="space-y-4">
                    <!-- Group 1: Overview -->
                    <div>
                        <span v-show="!isCollapsed" class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest px-3 mb-2 block select-none">{{ t('nav_group_overview') }}</span>
                        <nav class="space-y-1">
                            <Link 
                                :href="route('dashboard')" 
                                class="sidebar-link-light flex items-center rounded-xl text-xs"
                                :class="[
                                    route().current('dashboard') ? 'active' : '',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('dashboard') : ''"
                            >
                                <i class="fas fa-th-large text-sm w-5 flex justify-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('dashboard') }}</span>
                            </Link>
                        </nav>
                    </div>

                    <!-- Group 2: Catering Flow -->
                    <div>
                        <span v-show="!isCollapsed" class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest px-3 mb-2 block select-none">{{ t('nav_group_catering') }}</span>
                        <nav class="space-y-1">
                            <Link 
                                :href="route('menu.index')" 
                                class="sidebar-link-light flex items-center rounded-xl text-xs"
                                :class="[
                                    (route().current('menu.*') || route().current('cart.customize')) ? 'active' : '',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('our_menu') : ''"
                            >
                                <i class="fas fa-concierge-bell text-sm w-5 flex justify-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('our_menu') }}</span>
                            </Link>

                            <Link 
                                :href="route('budget.planner')" 
                                class="sidebar-link-light flex items-center rounded-xl text-xs"
                                :class="[
                                    route().current('budget.*') ? 'active' : '',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('budget_planner') : ''"
                            >
                                <i class="fas fa-calculator text-sm w-5 flex justify-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('budget_planner') }}</span>
                            </Link>


                            <Link 
                                :href="route('orders.index')" 
                                class="sidebar-link-light flex items-center rounded-xl text-xs"
                                :class="[
                                    route().current('orders.*') ? 'active' : '',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('my_orders') : ''"
                            >
                                <i class="fas fa-receipt text-sm w-5 flex justify-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('my_orders') }}</span>
                            </Link>
                        </nav>
                    </div>

                    <!-- Group 3: Account -->
                    <div>
                        <span v-show="!isCollapsed" class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest px-3 mb-2 block select-none">{{ t('nav_group_account') }}</span>
                        <nav class="space-y-1">
                            <Link 
                                :href="route('profile.edit')" 
                                class="sidebar-link-light flex items-center rounded-xl text-xs"
                                :class="[
                                    route().current('profile.*') ? 'active' : '',
                                    isCollapsed ? 'justify-center p-3' : 'gap-3 px-4 py-2.5'
                                ]"
                                :title="isCollapsed ? t('settings') : ''"
                            >
                                <i class="fas fa-user-cog text-sm w-5 flex justify-center"></i>
                                <span v-show="!isCollapsed" class="whitespace-nowrap">{{ t('settings') }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- User Profile Summary & Log out at bottom -->
            <div class="border-t border-[#E6E1DA] pt-4 mt-auto">
                <div 
                    class="flex items-center justify-between rounded-xl transition-all duration-300"
                    :class="isCollapsed ? 'flex-col gap-3 p-1 bg-transparent border-0' : 'p-3 bg-[#FAF7F2] border border-[#E6E1DA]'"
                >
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <!-- User avatar circle -->
                        <img v-if="$page.props.auth.user.profile_image" :src="'/storage/' + $page.props.auth.user.profile_image" class="w-8 h-8 rounded-full object-cover shrink-0 shadow-sm" />
                        <div v-else class="w-8 h-8 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                            {{ ($page.props.auth.user.name || 'C').charAt(0).toUpperCase() }}
                        </div>
                        <!-- Profile details -->
                        <div class="overflow-hidden transition-all duration-300" :class="isCollapsed ? 'w-0 opacity-0' : 'w-full opacity-100'">
                            <h4 class="text-xs font-semibold text-[#2D3330] truncate">{{ $page.props.auth.user.name }}</h4>
                            <p class="text-[9px] text-[#8C8275] truncate uppercase font-semibold">{{ t('role_customer') }}</p>
                        </div>
                    </div>
                    
                    <!-- Logout button arrow -->
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        class="text-[#8C8275] hover:text-rose-500 p-1.5 rounded-lg hover:bg-rose-500/5 transition-colors cursor-pointer shrink-0 animate-fade-in"
                        :class="isCollapsed ? 'w-8 h-8 border border-[#E6E1DA] rounded-lg bg-white flex items-center justify-center' : ''"
                        :title="t('logout')"
                    >
                        <i class="fas fa-sign-out-alt text-xs"></i>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- 2. CONTENT FRAME (shifted on desktop to clear fixed sidebar) -->
        <div 
            class="flex-grow flex flex-col min-h-screen transition-all duration-300 ease-in-out"
            :class="isCollapsed ? 'md:pl-20' : 'md:pl-64'"
        >
            
            <!-- 3. TOP HEADER BAR (Tajdid Style with Breadcrumbs) -->
            <header class="bg-white border-b border-[#E6E1DA] h-16 px-4 md:px-8 flex items-center justify-between sticky top-0 z-20 w-full">
                <!-- Left Section: Toggle & Breadcrumbs -->
                <div class="flex items-center gap-4">
                    <!-- Sidebar Toggle Button (Only on desktop) -->
                    <button 
                        @click="toggleCollapse"
                        class="hidden md:flex w-9 h-9 border border-[#E6E1DA] rounded-xl items-center justify-center text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors cursor-pointer shrink-0"
                        :title="isCollapsed ? t('expand_sidebar') : t('collapse_sidebar')"
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
                    <div class="flex items-center text-[10px] font-bold tracking-wider select-none font-sans-modern min-w-0">
                        <Link :href="route('dashboard')" class="text-[#8C8275] hover:text-[#4A6B5D] uppercase transition-colors hidden md:inline shrink-0">
                            {{ t('dashboard') }}
                        </Link>
                        <span v-if="headerTitle" class="text-[#8C8275]/60 mx-2 text-xs font-normal hidden md:inline shrink-0">&rsaquo;</span>
                        <span v-if="headerTitle" class="text-[#4A6B5D] uppercase truncate max-w-[100px] xs:max-w-[120px] sm:max-w-none">{{ headerTitle }}</span>
                    </div>
                </div>

                <!-- Right Section: Language, Cart & Profile Quick Actions -->
                <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    <!-- Language selector -->
                    <button 
                        @click="toggleLanguage"
                        class="w-12 sm:w-16 h-9 border border-[#E6E1DA] rounded-xl flex items-center justify-center gap-1 sm:gap-1.5 text-[10px] sm:text-xs font-semibold text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] transition-colors cursor-pointer shrink-0"
                        :title="`Switch to ${currentLanguage === 'en' ? 'Bahasa Melayu' : 'English'}`"
                    >
                        <i class="fas fa-globe text-[#8C8275]"></i>
                        <span>{{ currentLanguage.toUpperCase() }}</span>
                    </button>

                    <!-- Quick Cart Icon with Badge -->
                    <Link 
                        :href="route('cart.index')"
                        class="w-9 h-9 border border-[#E6E1DA] rounded-xl flex items-center justify-center text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] relative transition-colors cursor-pointer shrink-0"
                        :title="t('view_cart')"
                    >
                        <i class="fas fa-shopping-basket"></i>
                        <span 
                            v-if="$page.props.cartCount > 0"
                            class="absolute -top-1 -right-1 bg-[#4A6B5D] text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm"
                        >
                            {{ $page.props.cartCount }}
                        </span>
                    </Link>

                    <!-- Notification Bell Icon (VMS style) & Dropdown -->
                    <div class="relative shrink-0">
                        <button 
                            @click="toggleNotificationsDropdown"
                            class="w-9 h-9 border border-[#E6E1DA] rounded-xl flex items-center justify-center text-[#8C8275] hover:text-[#4A6B5D] hover:bg-[#FAF7F2] relative transition-colors cursor-pointer shrink-0"
                            :title="t('notifications')"
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
                                                    {{ item.title }}
                                                </h4>
                                                <span class="text-[9px] text-[#8C8275] whitespace-nowrap ml-2">
                                                    {{ formatTimeAgo(item.created_at) }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-[#5C6460] leading-relaxed">
                                                {{ item.message }}
                                            </p>
                                        </div>
                                        <span v-if="!item.read_at" class="w-1.5 h-1.5 bg-[#4A6B5D] rounded-full mt-1.5 shrink-0"></span>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- User Detail Avatar with Dropdown -->
                    <div class="flex items-center border-l border-[#E6E1DA] pl-1.5 sm:pl-4 h-9 relative z-30 shrink-0">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-3 text-left cursor-pointer focus:outline-none select-none">
                                    <div class="hidden sm:block text-center">
                                        <div class="text-xs font-semibold text-[#2D3330] leading-none mb-1 text-center">
                                            <span>{{ $page.props.auth.user.name }}</span>
                                        </div>
                                        <div class="text-[9px] font-medium text-[#8C8275] leading-none uppercase tracking-wider text-center">{{ t('role_customer') }}</div>
                                    </div>
                                    <img v-if="$page.props.auth.user.profile_image" :src="'/storage/' + $page.props.auth.user.profile_image" class="w-8 h-8 rounded-full object-cover shrink-0 shadow-xs border border-[#E6E1DA]" />
                                    <div v-else class="w-8 h-8 rounded-full bg-[#FAF7F2] border border-[#E6E1DA] text-[#4A6B5D] flex items-center justify-center font-bold text-xs shadow-xs shrink-0 select-none">
                                        {{ ($page.props.auth.user.name || 'C').charAt(0).toUpperCase() }}
                                    </div>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-user-cog text-[#8C8275]"></i>
                                    <span>{{ t('settings') }}</span>
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-2 text-xs text-left">
                                    <i class="fas fa-sign-out-alt text-[#8C8275]"></i>
                                    <span>{{ t('logout') }}</span>
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Main Page Content Slot -->
            <main class="flex-grow p-3 sm:p-6 md:p-8 space-y-3.5 md:space-y-6">
                <!-- Page Title Card (Tajdid style, in body) -->
                <div v-if="headerTitle" class="bg-white rounded-lg sm:rounded-2xl border border-[#E6E1DA] p-3 sm:p-6 md:p-8 shadow-xs space-y-2">
                    <h1 class="text-sm sm:text-lg font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wide leading-none">
                        {{ headerTitle }}
                    </h1>
                    <p v-if="headerDesc" class="text-[9px] sm:text-xs text-[#8C8275] font-semibold leading-relaxed tracking-wide">
                        {{ headerDesc }}
                    </p>
                </div>
                
                <slot />
            </main>
        </div>

        <!-- 4. MOBILE SIDEBAR DRAWER -->
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
                    <aside v-if="isMobileOpen" class="fixed top-0 bottom-0 left-0 w-64 sidebar-light text-[#2D3330] border-r border-[#E6E1DA] z-50 p-5 flex flex-col justify-between overflow-y-auto">
                        <div class="space-y-6">
                            <!-- Branding logo & close button -->
                            <div class="flex items-center justify-between pb-5 border-b border-[#E6E1DA]">
                                <Link :href="route('dashboard')" class="flex items-center text-[#2D3330]">
                                    <ApplicationLogo />
                                </Link>
                                <button 
                                    @click="isMobileOpen = false"
                                    class="w-8 h-8 border border-[#E6E1DA] rounded-lg flex items-center justify-center text-[#8C8275]"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Nav Menu Links grouped -->
                            <div class="space-y-4" @click="isMobileOpen = false">
                                <!-- Overview -->
                                <div>
                                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest px-3 mb-2 block">{{ t('nav_group_overview') }}</span>
                                    <nav class="space-y-1">
                                        <Link 
                                            :href="route('dashboard')" 
                                            class="sidebar-link-light flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs"
                                            :class="route().current('dashboard') ? 'active' : ''"
                                        >
                                            <i class="fas fa-th-large text-sm w-5 flex justify-center"></i>
                                            <span>{{ t('dashboard') }}</span>
                                        </Link>
                                    </nav>
                                </div>

                                <!-- Catering Flow -->
                                <div>
                                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest px-3 mb-2 block">{{ t('nav_group_catering') }}</span>
                                    <nav class="space-y-1">
                                        <Link 
                                            :href="route('menu.index')" 
                                            class="sidebar-link-light flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs"
                                            :class="(route().current('menu.*') || route().current('cart.customize')) ? 'active' : ''"
                                        >
                                            <i class="fas fa-concierge-bell text-sm w-5 flex justify-center"></i>
                                            <span>{{ t('our_menu') }}</span>
                                        </Link>

                                        <Link 
                                            :href="route('budget.planner')" 
                                            class="sidebar-link-light flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs"
                                            :class="route().current('budget.*') ? 'active' : ''"
                                        >
                                            <i class="fas fa-calculator text-sm w-5 flex justify-center"></i>
                                            <span>{{ t('budget_planner') }}</span>
                                        </Link>


                                        <Link 
                                            :href="route('orders.index')" 
                                            class="sidebar-link-light flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs"
                                            :class="route().current('orders.*') ? 'active' : ''"
                                        >
                                            <i class="fas fa-receipt text-sm w-5 flex justify-center"></i>
                                            <span>{{ t('my_orders') }}</span>
                                        </Link>
                                    </nav>
                                </div>

                                <!-- Account -->
                                <div>
                                    <span class="text-[9px] font-bold text-[#8C8275] uppercase tracking-widest px-3 mb-2 block">{{ t('nav_group_account') }}</span>
                                    <nav class="space-y-1">
                                        <Link 
                                            :href="route('profile.edit')" 
                                            class="sidebar-link-light flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs"
                                            :class="route().current('profile.*') ? 'active' : ''"
                                        >
                                            <i class="fas fa-user-cog text-sm w-5 flex justify-center"></i>
                                            <span>{{ t('settings') }}</span>
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- User profile bottom Mobile -->
                        <div class="border-t border-[#E6E1DA] pt-4">
                            <div class="flex items-center justify-between p-3 bg-[#FAF7F2] border border-[#E6E1DA] rounded-xl">
                                <div class="flex items-center gap-2.5">
                                    <img v-if="$page.props.auth.user.profile_image" :src="'/storage/' + $page.props.auth.user.profile_image" class="w-8 h-8 rounded-full object-cover shadow-sm shrink-0" />
                                    <div v-else class="w-8 h-8 rounded-full bg-[#C5A880] text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                                        {{ ($page.props.auth.user.name || 'C').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-[#2D3330]">{{ $page.props.auth.user.name }}</h4>
                                        <p class="text-[9px] text-[#8C8275] uppercase font-semibold">{{ t('role_customer') }}</p>
                                    </div>
                                </div>
                                <Link 
                                    :href="route('logout')" 
                                    method="post" 
                                    as="button" 
                                    class="text-[#8C8275] hover:text-rose-500 p-1.5 rounded-lg hover:bg-rose-500/5 transition-colors cursor-pointer"
                                    @click="isMobileOpen = false"
                                >
                                    <i class="fas fa-sign-out-alt text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Transition>

        <!-- Session Timeout Warning Modal -->
        <div v-if="showTimeoutWarning" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 z-[60] animate-fade-in font-sans-modern">
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-2xl p-8 max-w-sm w-full text-center space-y-6">
                <div class="w-16 h-16 bg-[#FAF6F0] text-[#C5A880] rounded-full flex items-center justify-center mx-auto text-2xl border border-[#E6E1DA]">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="text-lg font-bold text-[#2D3330]">{{ t('session_timeout_title') }}</h3>
                    <p class="text-xs text-[#5C6460] leading-relaxed">
                        {{ t('session_timeout_desc') }}
                    </p>
                    <div class="text-2xl font-extrabold text-[#8C3A3A] font-serif-luxury tracking-wider py-2">
                        {{ Math.floor(warningCountdown / 60) }}:{{ String(warningCountdown % 60).padStart(2, '0') }}
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <button 
                        @click="keepSessionActive" 
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3 px-4 rounded-xl text-xs uppercase tracking-widest transition-colors shadow-sm cursor-pointer"
                    >
                        {{ t('keep_logged_in') }}
                    </button>
                    <button 
                        @click="forceLogout" 
                        class="bg-white hover:bg-[#FAF7F2] border border-[#E6E1DA] text-[#5C6460] font-semibold py-3 px-4 rounded-xl text-xs uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        {{ t('logout') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Dynamic Toast Notification Banners -->
        <ToastList />

        <!-- Premium Confirmation / Prompt Dialog Modal -->
        <ConfirmModal />
    </div>
</template>
