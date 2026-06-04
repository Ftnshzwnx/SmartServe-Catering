<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useLocalization } from '@/Composables/useLocalization';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const { t, setLanguage, currentLanguage } = useLocalization();
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
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-[#4A6B5D] flex items-center justify-center text-white text-xs font-bold font-sans-modern">SS</span>
                    <span class="text-xl font-bold tracking-wider text-[#2D3330] uppercase">
                        Smart<span class="text-[#4A6B5D]">Serve</span>
                    </span>
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
                            class="bg-[#4A6B5D] text-white hover:bg-[#3D574B] px-6 py-2.5 rounded-none text-xs font-semibold uppercase tracking-widest transition-all duration-200"
                        >
                            {{ t('dashboard') }}
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-xs font-semibold uppercase tracking-widest text-[#2D3330] hover:text-[#4A6B5D] transition-colors duration-200 py-2"
                            >
                                {{ t('sign_in') }}
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-[#4A6B5D] text-white hover:bg-[#3D574B] px-6 py-2.5 rounded-none text-xs font-semibold uppercase tracking-widest transition-all duration-200"
                            >
                                {{ t('register') }}
                            </Link>
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
                            <Link
                                :href="route('login')"
                                class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white text-center px-8 py-4 rounded-none text-xs font-semibold uppercase tracking-widest transition-all duration-200 shadow-md"
                            >
                                {{ t('begin_experience') }}
                            </Link>
                            <a
                                href="#about"
                                class="bg-transparent hover:bg-[#FAF7F2] border border-[#D1C8BD] text-[#2D3330] text-center px-8 py-4 rounded-none text-xs font-semibold uppercase tracking-widest transition-all duration-200"
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
    </div>
</template>
