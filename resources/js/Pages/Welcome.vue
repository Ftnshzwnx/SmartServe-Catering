<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const { t, setLanguage, currentLanguage } = useLocalization();

const currentDrawer = ref(null); // 'login', 'register', 'forgot-password', 'reset-password'

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
    email: '',
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
            alert('Password reset successfully! You can now log in.');
        }
    });
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
});

// Watch history changes
watch(() => window.location.search, () => {
    const urlParams = new URLSearchParams(window.location.search);
    currentDrawer.value = urlParams.get('drawer');
});
</script>

<template>
    <Head title="Premium Catering Services" />

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
                <div class="flex items-center gap-6 lg:gap-8">
                    <a href="#about" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('our_story') }}</a>
                    <a href="#philosophy" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('philosophy') }}</a>
                    <a href="#policies" class="text-xs font-semibold uppercase tracking-widest text-[#5C6460] hover:text-[#4A6B5D] transition-colors duration-200 hidden md:block">{{ t('policies') }}</a>
                    
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

        <!-- Asymmetrical Hero Section -->
        <header class="relative overflow-hidden py-16 lg:py-28 font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    <!-- Left Column: Copy -->
                    <div class="lg:col-span-7 space-y-8 text-left z-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#EBEFEF] text-[#4A6B5D] text-xs font-semibold tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#4A6B5D] animate-pulse"></span>
                            {{ t('hero_subtitle') }}
                        </div>
                        
                        <h1 class="text-5xl lg:text-7xl font-light tracking-tight text-[#1C201E] leading-[1.1] font-serif-luxury">
                            {{ t('hero_title_1') }} <br />
                            <span class="italic text-[#4A6B5D]">{{ t('hero_title_2') }}</span><br />
                            {{ t('hero_title_3') }}
                        </h1>
                        
                        <p class="text-base lg:text-lg text-[#5C6460] leading-relaxed max-w-xl font-light">
                            {{ t('hero_desc') }}
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button
                                v-if="!$page.props.auth.user"
                                @click="openDrawer('login')"
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-8 py-4 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md cursor-pointer"
                            >
                                {{ t('begin_experience') }}
                            </button>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-8 py-4 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md"
                            >
                                {{ t('begin_experience') }}
                            </Link>
                            <a
                                href="#about"
                                class="bg-transparent hover:bg-[#FAF7F2] border border-[#D1C8BD] text-[#2D3330] text-center px-8 py-4 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all duration-200"
                            >
                                {{ t('read_story') }}
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Framed Hero Image -->
                    <div class="lg:col-span-5 flex justify-center lg:justify-end">
                        <div class="image-frame w-full max-w-[420px] aspect-[4/5] bg-[#EADED9] overflow-hidden shadow-lg z-10">
                            <img 
                                src="/img/hero_catering.png" 
                                alt="Minimalist Luxury Buffet Catering Design" 
                                class="w-full h-full object-cover grayscale-[10%] hover:scale-105 transition-transform duration-700 ease-out"
                            />
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- Our Story Section -->
        <section id="about" class="py-24 bg-white border-y border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-12 items-baseline">
                    
                    <!-- Intro Header -->
                    <div class="lg:col-span-1">
                        <span class="text-[#4A6B5D] font-bold text-xs uppercase tracking-widest block mb-4">{{ t('service_standard') }}</span>
                        <h2 class="text-3xl lg:text-4xl font-normal text-[#1C201E] font-serif-luxury leading-snug">
                            {{ t('service_standard_desc') }}
                        </h2>
                    </div>

                    <!-- Core Value 1 -->
                    <div class="space-y-4">
                        <span class="text-xs font-semibold text-[#8C8275] tracking-widest uppercase block">{{ t('story_title_1') }}</span>
                        <h3 class="text-lg font-semibold text-[#1C201E]">{{ t('story_header_1') }}</h3>
                        <p class="text-sm text-[#5C6460] leading-relaxed font-light">
                            {{ t('story_desc_1') }}
                        </p>
                    </div>

                    <!-- Core Value 2 -->
                    <div class="space-y-4">
                        <span class="text-xs font-semibold text-[#8C8275] tracking-widest uppercase block">{{ t('story_title_2') }}</span>
                        <h3 class="text-lg font-semibold text-[#1C201E]">{{ t('story_header_2') }}</h3>
                        <p class="text-sm text-[#5C6460] leading-relaxed font-light">
                            {{ t('story_desc_2') }}
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- Culinary Philosophy Section -->
        <section id="philosophy" class="py-24 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-12 items-center">
                    
                    <!-- Left: Gourmet Platter Image -->
                    <div class="lg:col-span-5 order-2 lg:order-1 flex justify-center">
                        <div class="image-frame w-full max-w-[360px] aspect-square bg-[#EADED9] overflow-hidden shadow-lg">
                            <img 
                                src="/img/catering_dish.png" 
                                alt="Modern Gourmet Presentation of Traditional Malaysian Dishes" 
                                class="w-full h-full object-cover hover:scale-105 transition-transform duration-700 ease-out"
                            />
                        </div>
                    </div>

                    <!-- Right: Narrative -->
                    <div class="lg:col-span-7 order-1 lg:order-2 space-y-6">
                        <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('philosophy_tag') }}</span>
                        <h2 class="text-4xl lg:text-5xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                            {{ t('philosophy_title_1') }} <br /><span class="italic text-[#4A6B5D]">{{ t('philosophy_title_2') }}</span>
                        </h2>
                        <p class="text-[#5C6460] font-light leading-relaxed">
                            {{ t('philosophy_desc_1') }}
                        </p>
                        <p class="text-[#5C6460] font-light leading-relaxed">
                            {{ t('philosophy_desc_2') }}
                        </p>
                        <div class="pt-2">
                            <Link 
                                :href="route('login')" 
                                class="text-xs font-semibold tracking-widest uppercase text-[#4A6B5D] hover:text-[#3D574B] inline-flex items-center gap-2 group transition-colors"
                            >
                                {{ t('browse_packages') }} 
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Policy Section -->
        <section id="policies" class="py-24 bg-white border-t border-[#E6E1DA] font-sans-modern">
            <div class="max-w-4xl mx-auto px-6">
                <div class="bg-[#FAF7F2] border border-[#E6E1DA] p-8 lg:p-12 space-y-8 shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#E6E1DA] pb-6">
                        <span class="w-2 h-2 rounded-full bg-[#8C3A3A]"></span>
                        <h3 class="text-xl font-normal font-serif-luxury uppercase tracking-wider text-[#1C201E]">{{ t('booking_policies') }}</h3>
                    </div>
                    
                    <div class="space-y-8 font-sans-modern">
                        <!-- Policy Item 1 -->
                        <div class="flex gap-4">
                            <span class="text-[#4A6B5D] font-bold text-xs uppercase tracking-widest mt-1">{{ t('policy_title_1') }}</span>
                            <div>
                                <h4 class="text-sm font-semibold text-[#1C201E] uppercase tracking-wider mb-1">{{ t('policy_header_1') }}</h4>
                                <p class="text-[#5C6460] text-sm leading-relaxed font-light">
                                    {{ t('policy_desc_1') }}
                                </p>
                            </div>
                        </div>

                        <!-- Policy Item 2 -->
                        <div class="flex gap-4">
                            <span class="text-[#4A6B5D] font-bold text-xs uppercase tracking-widest mt-1">{{ t('policy_title_2') }}</span>
                            <div>
                                <h4 class="text-sm font-semibold text-[#1C201E] uppercase tracking-wider mb-1">{{ t('policy_header_2') }}</h4>
                                <p class="text-[#5C6460] text-sm leading-relaxed font-light">
                                    {{ t('policy_desc_2') }}
                                </p>
                            </div>
                        </div>

                        <!-- Terms Summary -->
                        <div class="pt-4 border-t border-[#E6E1DA] text-[11px] text-[#8C8275] tracking-wide font-light">
                            By placing deposit transactions via our integrated checkout flow, customers formally agree to the Terms of Service and Event Booking Guidelines.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-[#242A27] text-[#D1C8BD] py-16 font-sans-modern border-t border-[#2D3330]">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 grid md:grid-cols-3 gap-12">
                
                <!-- Brand Info -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-[#4A6B5D] flex items-center justify-center text-white text-xs font-bold font-sans-modern">SS</span>
                        <span class="text-lg font-bold tracking-wider text-white uppercase">
                            Smart<span class="text-[#4A6B5D]">Serve</span>
                        </span>
                    </div>
                    <p class="text-xs text-[#8E9993] leading-relaxed max-w-sm font-light">
                        Serving Kuala Terengganu with modern event culinary services. Bringing local recipes to life with design and dining elegance.
                    </p>
                    <div class="flex gap-4 pt-2">
                        <a class="text-[#8E9993] hover:text-white transition-colors" href="#"><i class="fab fa-facebook-f text-sm"></i></a>
                        <a class="text-[#8E9993] hover:text-white transition-colors" href="#"><i class="fab fa-instagram text-sm"></i></a>
                        <a class="text-[#8E9993] hover:text-white transition-colors" href="https://wa.me/60123456789" target="_blank"><i class="fab fa-whatsapp text-sm"></i></a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="space-y-4">
                    <h4 class="text-white text-xs font-bold uppercase tracking-widest">Explore</h4>
                    <ul class="space-y-2.5 text-xs text-[#8E9993]">
                        <li><Link :href="route('login')" class="hover:text-white transition-colors uppercase tracking-widest font-light">{{ t('menu') }}</Link></li>
                        <li><a href="#about" class="hover:text-white transition-colors uppercase tracking-widest font-light">{{ t('our_story') }}</a></li>
                        <li><Link :href="route('login')" class="hover:text-white transition-colors uppercase tracking-widest font-light">{{ t('dashboard') }}</Link></li>
                    </ul>
                </div>

                <!-- Contact Details -->
                <div class="space-y-4">
                    <h4 class="text-white text-xs font-bold uppercase tracking-widest">Connect</h4>
                    <ul class="space-y-3 text-xs text-[#8E9993] font-light">
                        <li class="flex items-start gap-2.5">
                            <i class="fa fa-map-marker-alt text-[#4A6B5D] mt-0.5"></i> 
                            <span>Gong Badak, Kuala Terengganu, Malaysia</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fa fa-phone-alt text-[#4A6B5D]"></i> 
                            <span>+60 12-345 6789</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fa fa-envelope text-[#4A6B5D]"></i> 
                            <span>info@smartservecatering.com</span>
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
                        <!-- Close button top left/right -->
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
                                    <template v-else-if="currentDrawer === 'forgot-password'">Reset Password</template>
                                    <template v-else-if="currentDrawer === 'reset-password'">Set New Password</template>
                                </h3>
                            </div>

                            <!-- Drawer forms -->
                            
                            <!-- 1. LOGIN FORM -->
                            <form v-if="currentDrawer === 'login'" @submit.prevent="submitLogin" class="space-y-5">
                                <div>
                                    <InputLabel for="login-email" value="Email Address" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
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
                                        <InputLabel for="login-password" value="Password" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                        <button 
                                            type="button" 
                                            @click="openDrawer('forgot-password')"
                                            class="text-xs text-[#8C8275] hover:text-[#4A6B5D] transition-colors cursor-pointer"
                                        >
                                            Forgot Password?
                                        </button>
                                    </div>
                                    <TextInput
                                        id="login-password"
                                        type="password"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="loginForm.password"
                                        required
                                        autocomplete="current-password"
                                    />
                                    <InputError class="mt-1" :message="loginForm.errors.password" />
                                </div>

                                <div class="flex items-center">
                                    <Checkbox name="remember" v-model:checked="loginForm.remember" class="rounded text-[#4A6B5D] focus:ring-0" />
                                    <span class="ms-2 text-xs text-[#8C8275]">Remember my session</span>
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 cursor-pointer"
                                    :disabled="loginForm.processing"
                                >
                                    {{ loginForm.processing ? 'Signing In...' : t('sign_in') }}
                                </button>

                                <div class="text-center text-xs text-[#8C8275] pt-2">
                                    Don't have an account? 
                                    <button type="button" @click="openDrawer('register')" class="text-[#4A6B5D] font-bold hover:underline cursor-pointer">
                                        {{ t('register') }}
                                    </button>
                                </div>
                            </form>

                            <!-- 2. REGISTER FORM -->
                            <form v-else-if="currentDrawer === 'register'" @submit.prevent="submitRegister" class="space-y-4">
                                <div>
                                    <InputLabel for="reg-name" value="Your Name" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
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
                                    <InputLabel for="reg-email" value="Email Address" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
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
                                    <InputLabel for="reg-password" value="Password" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reg-password"
                                        type="password"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="registerForm.password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-1" :message="registerForm.errors.password" />
                                </div>

                                <div>
                                    <InputLabel for="reg-password-confirm" value="Confirm Password" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reg-password-confirm"
                                        type="password"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="registerForm.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-1" :message="registerForm.errors.password_confirmation" />
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 mt-2 cursor-pointer"
                                    :disabled="registerForm.processing"
                                >
                                    {{ registerForm.processing ? 'Registering...' : t('register') }}
                                </button>

                                <div class="text-center text-xs text-[#8C8275] pt-2">
                                    Already registered? 
                                    <button type="button" @click="openDrawer('login')" class="text-[#4A6B5D] font-bold hover:underline cursor-pointer">
                                        {{ t('sign_in') }}
                                    </button>
                                </div>
                            </form>

                            <!-- 3. FORGOT PASSWORD FORM -->
                            <form v-else-if="currentDrawer === 'forgot-password'" @submit.prevent="submitForgotPassword" class="space-y-5">
                                <p class="text-xs text-[#8C8275] leading-relaxed font-light">
                                    Forgot your password? No problem. Just let us know your email address and we will email you a password reset link to choose a new one.
                                </p>

                                <div v-if="forgotPasswordStatus" class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-medium">
                                    {{ forgotPasswordStatus }}
                                </div>

                                <div>
                                    <InputLabel for="forgot-email" value="Email Address" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
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
                                    {{ forgotPasswordForm.processing ? 'Sending Link...' : 'Email Reset Link' }}
                                </button>

                                <div class="text-center text-xs text-[#8C8275] pt-2">
                                    Back to 
                                    <button type="button" @click="openDrawer('login')" class="text-[#4A6B5D] font-bold hover:underline cursor-pointer">
                                        {{ t('sign_in') }}
                                    </button>
                                </div>
                            </form>

                            <!-- 4. RESET PASSWORD FORM -->
                            <form v-else-if="currentDrawer === 'reset-password'" @submit.prevent="submitResetPassword" class="space-y-4">
                                <div>
                                    <InputLabel for="reset-email" value="Email Address" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
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
                                    <InputLabel for="reset-password" value="New Password" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reset-password"
                                        type="password"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="resetPasswordForm.password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-1" :message="resetPasswordForm.errors.password" />
                                </div>

                                <div>
                                    <InputLabel for="reset-password-confirm" value="Confirm New Password" class="text-xs uppercase tracking-widest text-[#8C8275] font-semibold" />
                                    <TextInput
                                        id="reset-password-confirm"
                                        type="password"
                                        class="mt-1.5 block w-full rounded-lg border-[#E6E1DA] focus:border-[#4A6B5D] focus:ring-0 bg-white text-xs py-3"
                                        v-model="resetPasswordForm.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-1" :message="resetPasswordForm.errors.password_confirmation" />
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center py-3.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 mt-2 cursor-pointer"
                                    :disabled="resetPasswordForm.processing"
                                >
                                    {{ resetPasswordForm.processing ? 'Resetting...' : 'Reset Password' }}
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
    </div>
</template>
