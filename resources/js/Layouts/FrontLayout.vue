<script setup>
import { ref, onMounted, watch, provide, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ToastList from '@/Components/ToastList.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useToast } from '@/Composables/useToast';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const { t, setLanguage, currentLanguage } = useLocalization();
const { toast } = useToast();

const currentDrawer = ref(null); // 'login', 'register', 'forgot-password', 'reset-password'

// Password visibility toggles
const showLoginPassword = ref(false);
const showRegisterPassword = ref(false);
const showRegisterConfirmPassword = ref(false);
const showResetPassword = ref(false);
const showResetConfirmPassword = ref(false);

const openDrawer = (type) => {
    currentDrawer.value = type;
    const url = new URL(window.location.href);
    url.searchParams.set('drawer', type);
    window.history.pushState({}, '', url);
};

const closeDrawer = () => {
    currentDrawer.value = null;
    const url = new URL(window.location.href);
    url.searchParams.delete('drawer');
    url.searchParams.delete('token');
    url.searchParams.delete('email');
    window.history.pushState({}, '', url);
};

// Provide openDrawer function globally to child pages
provide('openDrawer', openDrawer);

// Forms state
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

const registerForm = useForm({
    name: '',
    full_name: '',
    email: '',
    phone: '',
    address: '',
    password: '',
    password_confirmation: '',
});

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};

const forgotPasswordForm = useForm({
    email: '',
});

const forgotPasswordStatus = ref('');

const submitForgotPassword = () => {
    forgotPasswordForm.post(route('password.email'), {
        onSuccess: (page) => {
            forgotPasswordStatus.value = page.props.flash?.status || 'Password reset link sent!';
        }
    });
};

const resetPasswordForm = useForm({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitResetPassword = () => {
    resetPasswordForm.post(route('password.store'), {
        onFinish: () => resetPasswordForm.reset('password', 'password_confirmation'),
        onSuccess: () => {
            closeDrawer();
            toast('Password reset successfully! You can now log in.');
        }
    });
};

// Password strength evaluation logic
const getPasswordStrength = (password) => {
    if (!password) return { score: 0, text: '', color: 'text-gray-400', barColor: 'bg-gray-200', width: 'w-0' };
    
    let score = 0;
    
    // Length check
    if (password.length >= 8) {
        score += 2;
    } else if (password.length >= 6) {
        score += 1;
    }
    
    // Complexity check
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSymbol = /[^A-Za-z0-9]/.test(password);
    
    let varietyCount = 0;
    if (hasUpperCase) varietyCount++;
    if (hasLowerCase) varietyCount++;
    if (hasNumber) varietyCount++;
    if (hasSymbol) varietyCount++;
    
    score += varietyCount;
    
    // Determine rating
    if (password.length < 6) {
        return { score: 1, text: 'Lemah (Weak)', color: 'text-rose-500', barColor: 'bg-rose-500', width: 'w-1/3' };
    }
    
    if (score <= 3) {
        return { score: 1, text: 'Lemah (Weak)', color: 'text-rose-500', barColor: 'bg-rose-500', width: 'w-1/3' };
    } else if (score <= 5) {
        return { score: 2, text: 'Sederhana (Medium)', color: 'text-amber-500', barColor: 'bg-amber-500', width: 'w-2/3' };
    } else {
        return { score: 3, text: 'Kuat (Strong)', color: 'text-emerald-500', barColor: 'bg-emerald-500', width: 'w-full' };
    }
};

const registerPasswordStrength = computed(() => getPasswordStrength(registerForm.password));
const resetPasswordStrength = computed(() => getPasswordStrength(resetPasswordForm.password));

const showTimeoutModal = ref(false);

const closeTimeoutModal = () => {
    showTimeoutModal.value = false;
    openDrawer('login');
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const drawerParam = urlParams.get('drawer');
    if (drawerParam) {
        currentDrawer.value = drawerParam;
        
        if (drawerParam === 'reset-password') {
            resetPasswordForm.token = urlParams.get('token') || '';
            resetPasswordForm.email = urlParams.get('email') || '';
        }
    }

    if (urlParams.get('timeout') === '1') {
        showTimeoutModal.value = true;
        
        // Remove timeout parameter from URL
        const url = new URL(window.location.href);
        url.searchParams.delete('timeout');
        window.history.replaceState({}, '', url);
    }
});

// Watch history changes
watch(() => window.location.search, () => {
    const urlParams = new URLSearchParams(window.location.search);
    currentDrawer.value = urlParams.get('drawer');
});
</script>

<template>
    <div class="min-h-screen bg-[#FAF7F2] text-[#2D3330] font-sans selection:bg-[#4A6B5D] selection:text-white">
        <!-- Google Fonts loading directly via CSS import -->
        <component :is="'style'">
            @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
            .font-serif-luxury { font-family: 'Cormorant Garamond', serif; }
            .font-sans-modern { font-family: 'Plus Jakarta Sans', sans-serif; }
            
            .glass-nav {
                background: rgba(250, 247, 242, 0.85);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }
            .image-frame {
                position: relative;
            }
            .image-frame::after {
                content: '';
                position: absolute;
                top: 12px;
                left: 12px;
                right: -12px;
                bottom: -12px;
                border: 1px solid #D1C8BD;
                z-index: 0;
                pointer-events: none;
                transition: transform 0.3s ease;
            }
            .image-frame:hover::after {
                transform: translate(-4px, -4px);
            }
        </component>

        <!-- Elegant Sticky Navbar -->
        <nav class="sticky top-0 z-50 w-full border-b border-[#E6E1DA] glass-nav transition-all duration-300 font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center">
                    <Link href="/" class="flex items-center">
                        <ApplicationLogo />
                    </Link>
                </div>

                <!-- Navigation Links / Auth Actions -->
                <div class="flex items-center gap-4 lg:gap-6">
                    <Link href="/" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('home_nav') }}</Link>
                    <Link href="/about" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('about_nav') }}</Link>
                    <Link href="/packages" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('package_nav') }}</Link>
                    <Link href="/faq" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('faq_nav') }}</Link>
                    <Link href="/contact" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('contact_nav') }}</Link>

                    
                    <!-- Language Toggle -->
                    <div class="flex items-center gap-1.5 border-l border-[#E6E1DA] pl-6 h-6 ml-2 font-sans-modern">
                        <button 
                            @click="setLanguage('en')" 
                            class="text-[10px] font-bold uppercase tracking-wider transition-colors"
                            :class="currentLanguage === 'en' ? 'text-[#4A6B5D]' : 'text-[#8C8275] hover:text-[#2D3330]'"
                        >
                            EN
                        </button>
                        <span class="text-[#E6E1DA] text-xs">|</span>
                        <button 
                            @click="setLanguage('my')" 
                            class="text-[10px] font-bold uppercase tracking-wider transition-colors"
                            :class="currentLanguage === 'my' ? 'text-[#4A6B5D]' : 'text-[#8C8275] hover:text-[#2D3330]'"
                        >
                            BM
                        </button>
                    </div>

                    <div v-if="canLogin" class="flex items-center gap-4 border-l border-[#E6E1DA] pl-6 h-6">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="bg-[#4A6B5D] text-white hover:bg-[#3D574B] px-6 py-2.5 rounded-full text-xs font-semibold uppercase tracking-widest transition-all duration-200"
                        >
                            {{ t('dashboard') }}
                        </Link>

                        <template v-else>
                            <button
                                @click="openDrawer('login')"
                                class="text-xs font-semibold uppercase tracking-widest text-[#2D3330] hover:text-[#4A6B5D] transition-colors duration-200 py-2 cursor-pointer"
                            >
                                {{ t('sign_in') }}
                            </button>

                            <button
                                v-if="canRegister"
                                @click="openDrawer('register')"
                                class="bg-[#4A6B5D] text-white hover:bg-[#3D574B] px-6 py-2.5 rounded-full text-xs font-semibold uppercase tracking-widest transition-all duration-200 cursor-pointer"
                            >
                                {{ t('register') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-[#242A27] text-[#D1C8BD] py-20 font-sans-modern border-t border-[#2D3330]">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 grid md:grid-cols-3 gap-16">
                <!-- Brand Info -->
                <div class="space-y-6">
                    <Link href="/" class="text-white inline-block hover:opacity-90 transition-opacity">
                        <ApplicationLogo />
                    </Link>
                    <p class="text-xs text-[#8E9993] leading-relaxed max-w-sm font-light">
                        {{ t('footer_desc') }}
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <!-- Facebook -->
                        <a 
                            class="w-10 h-10 rounded-full bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#8E9993] hover:text-white hover:bg-[#1877F2] hover:border-[#1877F2] hover:-translate-y-1 hover:scale-110 active:scale-95 transition-all duration-300 ease-out shadow-xs" 
                            href="https://www.facebook.com/people/Azilina-Katering/100063705123584/" 
                            target="_blank" 
                            title="Facebook"
                        >
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        <!-- Instagram -->
                        <a 
                            class="w-10 h-10 rounded-full bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#8E9993] hover:text-white hover:bg-gradient-to-tr hover:from-[#f9ce34] hover:via-[#ee2a7b] hover:to-[#6228d7] hover:border-transparent hover:-translate-y-1 hover:scale-110 active:scale-95 transition-all duration-300 ease-out shadow-xs" 
                            href="https://www.instagram.com/azilina_restaurant/" 
                            target="_blank" 
                            title="Instagram"
                        >
                            <i class="fab fa-instagram text-sm"></i>
                        </a>
                        <!-- TikTok -->
                        <a 
                            class="w-10 h-10 rounded-full bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#8E9993] hover:text-white hover:bg-black hover:border-black hover:shadow-[0_0_10px_rgba(254,44,85,0.4),0_0_10px_rgba(37,244,238,0.4)] hover:-translate-y-1 hover:scale-110 active:scale-95 transition-all duration-300 ease-out shadow-xs" 
                            href="https://www.tiktok.com/@azilinamustapha" 
                            target="_blank" 
                            title="TikTok"
                        >
                            <i class="fab fa-tiktok text-sm"></i>
                        </a>
                        <!-- WhatsApp -->
                        <a 
                            class="w-10 h-10 rounded-full bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#8E9993] hover:text-white hover:bg-[#25D366] hover:border-[#25D366] hover:-translate-y-1 hover:scale-110 active:scale-95 transition-all duration-300 ease-out shadow-xs" 
                            :href="'https://wa.me/' + ($page.props.settings.contact_phone || '019-2094670').replace(/[^0-9]/g, '').replace(/^0/, '60')" 
                            target="_blank"
                            title="WhatsApp"
                        >
                            <i class="fab fa-whatsapp text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="space-y-6 flex flex-col items-center">
                    <h4 class="text-white text-xs font-bold uppercase tracking-widest border-b border-[#2D3330] pb-3 w-full text-center">{{ t('explore_title') }}</h4>
                    <ul class="space-y-4 text-xs text-[#8E9993] flex flex-col items-center w-full">
                        <li>
                            <Link href="/" class="group relative pb-1 hover:text-white transition-colors uppercase tracking-widest font-light text-center block after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-0 after:h-[1.5px] after:bg-[#4A6B5D] hover:after:w-8 after:transition-all after:duration-300 ease-out">
                                {{ t('home_nav') }}
                            </Link>
                        </li>
                        <li>
                            <Link href="/about" class="group relative pb-1 hover:text-white transition-colors uppercase tracking-widest font-light text-center block after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-0 after:h-[1.5px] after:bg-[#4A6B5D] hover:after:w-8 after:transition-all after:duration-300 ease-out">
                                {{ t('about_nav') }}
                            </Link>
                        </li>
                        <li>
                            <Link href="/packages" class="group relative pb-1 hover:text-white transition-colors uppercase tracking-widest font-light text-center block after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-0 after:h-[1.5px] after:bg-[#4A6B5D] hover:after:w-8 after:transition-all after:duration-300 ease-out">
                                {{ t('package_nav') }}
                            </Link>
                        </li>
                        <li>
                            <Link href="/faq" class="group relative pb-1 hover:text-white transition-colors uppercase tracking-widest font-light text-center block after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-0 after:h-[1.5px] after:bg-[#4A6B5D] hover:after:w-8 after:transition-all after:duration-300 ease-out">
                                {{ t('faq_nav') }}
                            </Link>
                        </li>
                        <li>
                            <Link href="/contact" class="group relative pb-1 hover:text-white transition-colors uppercase tracking-widest font-light text-center block after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-0 after:h-[1.5px] after:bg-[#4A6B5D] hover:after:w-8 after:transition-all after:duration-300 ease-out">
                                {{ t('contact_nav') }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Contact Details -->
                <div class="space-y-6">
                    <h4 class="text-white text-xs font-bold uppercase tracking-widest border-b border-[#2D3330] pb-3">{{ t('connect_title') }}</h4>
                    <ul class="space-y-4 text-xs text-[#8E9993] font-light">
                        <li class="group">
                            <a 
                                :href="'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent($page.props.settings.business_address || '30899 TAMAN DESA YT, HADAPAN KOMPLEKS SUKAN GONG BADAK 21300 SEB. TAKIR, KUALA TERENGGANU, TERENGGANU')"
                                target="_blank"
                                class="flex items-start gap-3 hover:text-white transition-colors"
                            >
                                <span class="w-8 h-8 rounded-lg bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#4A6B5D] group-hover:text-white group-hover:bg-[#4A6B5D] group-hover:border-[#4A6B5D] transition-all duration-300 flex-shrink-0 shadow-xs">
                                    <i class="fa fa-map-marker-alt text-xs"></i> 
                                </span>
                                <span class="leading-relaxed">{{ $page.props.settings.business_address || 'Gong Badak, Kuala Terengganu, Malaysia' }}</span>
                            </a>
                        </li>
                        <li class="group">
                            <a 
                                :href="'tel:' + ($page.props.settings.contact_phone || '019-2094670')" 
                                class="flex items-center gap-3 hover:text-white transition-colors"
                            >
                                <span class="w-8 h-8 rounded-lg bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#4A6B5D] group-hover:text-white group-hover:bg-[#4A6B5D] group-hover:border-[#4A6B5D] transition-all duration-300 flex-shrink-0 shadow-xs">
                                    <i class="fa fa-phone-alt text-xs"></i> 
                                </span>
                                <span class="font-normal">{{ $page.props.settings.contact_phone || '019-2094670' }}</span>
                            </a>
                        </li>
                        <li class="group">
                            <a 
                                :href="'mailto:' + ($page.props.settings.contact_email || 'info@smartservecatering.com')" 
                                class="flex items-center gap-3 hover:text-white transition-colors"
                            >
                                <span class="w-8 h-8 rounded-lg bg-[#2E3532] border border-[#3E4743] flex items-center justify-center text-[#4A6B5D] group-hover:text-white group-hover:bg-[#4A6B5D] group-hover:border-[#4A6B5D] transition-all duration-300 flex-shrink-0 shadow-xs">
                                    <i class="fa fa-envelope text-xs"></i> 
                                </span>
                                <span class="font-normal break-all">{{ $page.props.settings.contact_email || 'info@smartservecatering.com' }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="max-w-7xl mx-auto px-6 lg:px-8 mt-16 pt-8 border-t border-[#2D3330] text-center text-[10px] text-[#8E9993] tracking-widest uppercase font-light">
                &copy; {{ new Date().getFullYear() }} {{ t('copyright') }}
            </div>
        </footer>

        <!-- Drawer Component overlay -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="currentDrawer" 
                class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs font-sans-modern"
                @click.self="closeDrawer"
            >
                <!-- Drawer Panel -->
                <Transition
                    enter-active-class="transition duration-300 ease-out transform"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition duration-200 ease-in transform"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div 
                        v-if="currentDrawer"
                        class="w-full max-w-md bg-[#FAF7F2] h-full shadow-2xl border-l border-[#E6E1DA] rounded-l-3xl p-8 md:p-10 flex flex-col justify-between overflow-y-auto relative z-50 text-[#2D3330]"
                    >
                        <!-- Close button -->
                        <button 
                            @click="closeDrawer"
                            class="absolute top-6 right-6 text-[#8C8275] hover:text-[#2D3330] transition-colors text-lg cursor-pointer"
                        >
                            <i class="fas fa-times"></i>
                        </button>

                        <div class="space-y-8 my-auto">
                            <!-- Logo Brand -->
                            <div class="flex flex-col items-center text-center">
                                <img src="/img/logo.png" class="h-16 w-auto object-contain mb-4" alt="SmartServe Logo" />
                                <h3 class="text-2xl font-normal font-serif-luxury uppercase tracking-wider text-[#2D3330]">
                                    <template v-if="currentDrawer === 'login'">{{ t('sign_in') }}</template>
                                    <template v-else-if="currentDrawer === 'register'">{{ t('register') }}</template>
                                    <template v-else-if="currentDrawer === 'forgot-password'">{{ t('reset_password') }}</template>
                                    <template v-else-if="currentDrawer === 'reset-password'">{{ t('set_new_password') }}</template>
                                </h3>
                            </div>

                            <!-- 1. LOGIN FORM -->
                            <form v-if="currentDrawer === 'login'" @submit.prevent="submitLogin" class="space-y-5">
                                <div>
                                    <InputLabel for="login-email" :value="t('email_address_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="login-email"
                                        type="email"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="loginForm.email"
                                        required
                                        autofocus
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-1" :message="loginForm.errors.email" />
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <InputLabel for="login-password" :value="t('password_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <button 
                                            type="button" 
                                            @click="openDrawer('forgot-password')"
                                            class="text-xs text-[#8C8275] hover:text-[#4A6B5D] transition-colors cursor-pointer"
                                        >
                                            {{ t('forgot_password_btn') }}
                                        </button>
                                    </div>
                                    <div class="relative mt-1.5">
                                        <TextInput
                                            id="login-password"
                                            :type="showLoginPassword ? 'text' : 'password'"
                                            class="block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3 pr-10"
                                            v-model="loginForm.password"
                                            required
                                            autocomplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            @click="showLoginPassword = !showLoginPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 cursor-pointer"
                                        >
                                            <i class="fas" :class="showLoginPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <InputError class="mt-1" :message="loginForm.errors.password" />
                                </div>

                                <div>
                                    <Checkbox name="remember" v-model:checked="loginForm.remember" class="rounded text-[#4A6B5D] focus:ring-0" />
                                    <span class="ms-2 text-xs text-[#8C8275]">{{ t('remember_session') }}</span>
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 cursor-pointer"
                                    :disabled="loginForm.processing"
                                >
                                    {{ loginForm.processing ? t('signing_in_status') : t('sign_in') }}
                                </button>

                                <div class="text-center text-xs text-[#8C8275] pt-2">
                                    {{ t('no_account_notice') }} 
                                    <button type="button" @click="openDrawer('register')" class="text-[#4A6B5D] font-bold hover:underline cursor-pointer ml-1">
                                        {{ t('register') }}
                                    </button>
                                </div>
                            </form>

                            <!-- 2. REGISTER FORM -->
                            <form v-else-if="currentDrawer === 'register'" @submit.prevent="submitRegister" class="space-y-4">
                                <div>
                                    <InputLabel for="reg-name" :value="t('your_name_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reg-name"
                                        type="text"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="registerForm.name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                    />
                                    <InputError class="mt-1" :message="registerForm.errors.name" />
                                </div>

                                <div>
                                    <InputLabel for="reg-fullname" :value="t('full_name_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reg-fullname"
                                        type="text"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="registerForm.full_name"
                                        required
                                        autocomplete="name"
                                    />
                                    <InputError class="mt-1" :message="registerForm.errors.full_name" />
                                </div>

                                <div>
                                    <InputLabel for="reg-email" :value="t('email_address_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reg-email"
                                        type="email"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="registerForm.email"
                                        required
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-1" :message="registerForm.errors.email" />
                                </div>

                                <div>
                                    <InputLabel for="reg-phone" :value="t('phone_number_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reg-phone"
                                        type="text"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="registerForm.phone"
                                        required
                                        autocomplete="tel"
                                    />
                                    <InputError class="mt-1" :message="registerForm.errors.phone" />
                                </div>

                                <div>
                                    <InputLabel for="reg-address" :value="t('delivery_address_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <textarea
                                        id="reg-address"
                                        rows="2"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-2 px-3 focus:outline-none"
                                        v-model="registerForm.address"
                                        required
                                    ></textarea>
                                    <InputError class="mt-1" :message="registerForm.errors.address" />
                                </div>

                                <div>
                                    <InputLabel for="reg-password" :value="t('password_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <div class="relative mt-1.5">
                                        <TextInput
                                            id="reg-password"
                                            :type="showRegisterPassword ? 'text' : 'password'"
                                            class="block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3 pr-10"
                                            v-model="registerForm.password"
                                            required
                                            autocomplete="new-password"
                                        />
                                        <button
                                            type="button"
                                            @click="showRegisterPassword = !showRegisterPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 cursor-pointer"
                                        >
                                            <i class="fas" :class="showRegisterPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <InputError class="mt-1" :message="registerForm.errors.password" />
                                    <!-- Password Strength Indicator -->
                                    <div class="mt-2 space-y-1" v-if="registerForm.password">
                                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                                            <span class="text-[#8C8275]">Kekuatan Kata Laluan:</span>
                                            <span :class="registerPasswordStrength.color">{{ registerPasswordStrength.text }}</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-[#E6E1DA] rounded-full overflow-hidden">
                                            <div 
                                                class="h-full transition-all duration-300 rounded-full" 
                                                :class="[registerPasswordStrength.barColor, registerPasswordStrength.width]"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="reg-password-confirm" :value="t('confirm_password_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <div class="relative mt-1.5">
                                        <TextInput
                                            id="reg-password-confirm"
                                            :type="showRegisterConfirmPassword ? 'text' : 'password'"
                                            class="block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3 pr-10"
                                            v-model="registerForm.password_confirmation"
                                            required
                                            autocomplete="new-password"
                                        />
                                        <button
                                            type="button"
                                            @click="showRegisterConfirmPassword = !showRegisterConfirmPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 cursor-pointer"
                                        >
                                            <i class="fas" :class="showRegisterConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <InputError class="mt-1" :message="registerForm.errors.password_confirmation" />
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 mt-2 cursor-pointer"
                                    :disabled="registerForm.processing"
                                >
                                    {{ registerForm.processing ? t('registering_status') : t('register') }}
                                </button>

                                <div class="text-center text-xs text-[#8C8275] pt-2">
                                    {{ t('already_registered_notice') }} 
                                    <button type="button" @click="openDrawer('login')" class="text-[#4A6B5D] font-bold hover:underline cursor-pointer ml-1">
                                        {{ t('sign_in') }}
                                    </button>
                                </div>
                            </form>

                            <!-- 3. FORGOT PASSWORD FORM -->
                            <form v-else-if="currentDrawer === 'forgot-password'" @submit.prevent="submitForgotPassword" class="space-y-5">
                                <p class="text-xs text-[#8C8275] leading-relaxed font-light">
                                    {{ t('forgot_password_desc') }}
                                </p>

                                <div v-if="forgotPasswordStatus" class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-medium">
                                    {{ forgotPasswordStatus }}
                                </div>

                                <div>
                                    <InputLabel for="forgot-email" :value="t('email_address_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="forgot-email"
                                        type="email"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="forgotPasswordForm.email"
                                        required
                                        autofocus
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-1" :message="forgotPasswordForm.errors.email" />
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 cursor-pointer"
                                    :disabled="forgotPasswordForm.processing"
                                >
                                    {{ forgotPasswordForm.processing ? t('sending_status') : t('send_reset_link_btn') }}
                                </button>

                                <div class="text-center text-xs text-[#8C8275] pt-2">
                                    {{ t('back_to_btn') }} 
                                    <button type="button" @click="openDrawer('login')" class="text-[#4A6B5D] font-bold hover:underline cursor-pointer ml-1">
                                        {{ t('sign_in') }}
                                    </button>
                                </div>
                            </form>

                            <!-- 4. RESET PASSWORD FORM -->
                            <form v-else-if="currentDrawer === 'reset-password'" @submit.prevent="submitResetPassword" class="space-y-4">
                                <div>
                                    <InputLabel for="reset-email" :value="t('email_address_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reset-email"
                                        type="email"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="resetPasswordForm.email"
                                        required
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-1" :message="resetPasswordForm.errors.email" />
                                </div>

                                <div>
                                    <InputLabel for="reset-password" :value="t('new_password_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <div class="relative mt-1.5">
                                        <TextInput
                                            id="reset-password"
                                            :type="showResetPassword ? 'text' : 'password'"
                                            class="block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3 pr-10"
                                            v-model="resetPasswordForm.password"
                                            required
                                            autocomplete="new-password"
                                        />
                                        <button
                                            type="button"
                                            @click="showResetPassword = !showResetPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 cursor-pointer"
                                        >
                                            <i class="fas" :class="showResetPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <InputError class="mt-1" :message="resetPasswordForm.errors.password" />
                                    <!-- Password Strength Indicator -->
                                    <div class="mt-2 space-y-1" v-if="resetPasswordForm.password">
                                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                                            <span class="text-[#8C8275]">Kekuatan Kata Laluan:</span>
                                            <span :class="resetPasswordStrength.color">{{ resetPasswordStrength.text }}</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-[#E6E1DA] rounded-full overflow-hidden">
                                            <div 
                                                class="h-full transition-all duration-300 rounded-full" 
                                                :class="[resetPasswordStrength.barColor, resetPasswordStrength.width]"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="reset-password-confirm" :value="t('confirm_new_password_label')" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <div class="relative mt-1.5">
                                        <TextInput
                                            id="reset-password-confirm"
                                            :type="showResetConfirmPassword ? 'text' : 'password'"
                                            class="block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3 pr-10"
                                            v-model="resetPasswordForm.password_confirmation"
                                            required
                                            autocomplete="new-password"
                                        />
                                        <button
                                            type="button"
                                            @click="showResetConfirmPassword = !showResetConfirmPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 cursor-pointer"
                                        >
                                            <i class="fas" :class="showResetConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                    <InputError class="mt-1" :message="resetPasswordForm.errors.password_confirmation" />
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 mt-2 cursor-pointer"
                                    :disabled="resetPasswordForm.processing"
                                >
                                    {{ resetPasswordForm.processing ? t('resetting_status') : t('reset_password') }}
                                </button>
                            </form>
                        </div>

                        <!-- Footer -->
                        <div class="text-center text-[10px] text-[#8C8275] uppercase tracking-widest border-t border-[#E6E1DA]/60 pt-6">
                            SmartServe Catering Gong Badak
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Session Expired Modal -->
        <div v-if="showTimeoutModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 z-[60] animate-fade-in font-sans-modern">
            <div class="bg-white rounded-2xl border border-[#E6E1DA] shadow-2xl p-8 max-w-sm w-full text-center space-y-6">
                <div class="w-16 h-16 bg-[#FAF6F0] text-[#C5A880] rounded-full flex items-center justify-center mx-auto text-2xl border border-[#E6E1DA]">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="text-lg font-bold text-[#2D3330] font-serif-luxury uppercase tracking-wider">
                        {{ t('session_expired_title') }}
                    </h3>
                    <p class="text-xs text-[#5C6460] leading-relaxed">
                        {{ t('session_expired_desc') }}
                    </p>
                </div>
                <div class="flex flex-col gap-2">
                    <button 
                        @click="closeTimeoutModal" 
                        class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white font-semibold py-3 px-4 rounded-xl text-xs uppercase tracking-widest transition-colors shadow-sm cursor-pointer"
                    >
                        OK
                    </button>
                </div>
            </div>
        </div>

        <!-- Dynamic Toast Notifications -->
        <ToastList />

        <!-- Premium Confirmation / Prompt Dialog Modal -->
        <ConfirmModal />
    </div>
</template>
