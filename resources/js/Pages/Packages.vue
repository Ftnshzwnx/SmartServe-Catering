<script setup>
import { ref, inject, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { useLocalization } from '@/Composables/useLocalization';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    packages: { type: Array, default: () => [] },
});

const openDrawer = inject('openDrawer', () => {});
const { t } = useLocalization();
</script>

<template>
    <Head :title="`${t('our_packages')} — SmartServe Catering`" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">

        <!-- Hero Section -->
        <section class="py-20 bg-white border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="max-w-2xl space-y-4">
                    <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('premium_selection') }}</span>
                    <h1 class="text-5xl lg:text-6xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                        {{ t('packages_hero_title_1') }} <span class="italic text-[#4A6B5D]">{{ t('packages_hero_title_2') }}</span>
                    </h1>
                    <p class="text-[#5C6460] text-sm leading-relaxed font-light max-w-lg">
                        {{ t('packages_hero_desc') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Packages Grid -->
        <section class="py-20 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-12">

                <!-- Dynamic packages from DB -->
                <template v-if="packages && packages.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div
                            v-for="pkg in packages"
                            :key="pkg.id"
                            class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between"
                        >
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img
                                        :src="pkg.image ? pkg.image : '/img/hero_catering.png'"
                                        :alt="pkg.package_name"
                                        class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out"
                                    />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">
                                        {{ t('popular_tag') }}
                                    </div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ pkg.package_name }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">
                                        {{ pkg.description || 'Elevate your event with our carefully curated traditional recipes, styled and portioned to absolute perfection.' }}
                                    </p>
                                    <div class="border-t border-[#FAF7F2] pt-4 space-y-2">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span>
                                            <span class="text-[#2D3330]">{{ pkg.min_order }} pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex items-center justify-between gap-4 mt-auto">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM {{ parseFloat(pkg.price).toFixed(2) }}<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <button
                                    v-if="!$page.props.auth.user"
                                    @click="openDrawer('login')"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 cursor-pointer"
                                >
                                    {{ t('select_package') }}
                                </button>
                                <Link
                                    v-else
                                    :href="route('menu.show', pkg.package_name)"
                                    class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 text-center"
                                >
                                    {{ t('select_package') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Fallback static packages -->
                <template v-else>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <!-- Mockup 1 -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img src="/img/hero_catering.png" alt="Wedding Silver Package" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out" />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('popular_tag') }}</div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('mock_pkg_1_name') }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">{{ t('mock_pkg_1_desc') }}</p>
                                    <div class="border-t border-[#FAF7F2] pt-4">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span><span class="text-[#2D3330]">500 pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex items-center justify-between gap-4">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM 15.00<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <button @click="openDrawer('login')" class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 cursor-pointer">{{ t('select_package') }}</button>
                            </div>
                        </div>

                        <!-- Mockup 2 -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img src="/img/catering_dish.png" alt="Corporate Bronze Package" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out" />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('premium_tag') }}</div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('mock_pkg_2_name') }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">{{ t('mock_pkg_2_desc') }}</p>
                                    <div class="border-t border-[#FAF7F2] pt-4">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span><span class="text-[#2D3330]">100 pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex items-center justify-between gap-4">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM 25.00<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <button @click="openDrawer('login')" class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 cursor-pointer">{{ t('select_package') }}</button>
                            </div>
                        </div>

                        <!-- Mockup 3 -->
                        <div class="bg-white border border-[#E6E1DA] rounded-3xl overflow-hidden shadow-xs hover:-translate-y-1 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="aspect-[4/3] bg-[#EADED9] overflow-hidden relative">
                                    <img src="/img/hero_catering.png" alt="Aqiqah & Gathering Package" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500 ease-out" />
                                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-xs px-3 py-1 rounded-full text-[10px] font-bold text-[#4A6B5D] uppercase tracking-wider">{{ t('value_tag') }}</div>
                                </div>
                                <div class="p-6 lg:p-8 space-y-4">
                                    <h2 class="text-xl font-normal text-[#1C201E] font-serif-luxury uppercase tracking-wider">{{ t('mock_pkg_3_name') }}</h2>
                                    <p class="text-xs text-[#5C6460] font-light leading-relaxed min-h-[48px]">{{ t('mock_pkg_3_desc') }}</p>
                                    <div class="border-t border-[#FAF7F2] pt-4">
                                        <div class="flex justify-between items-center text-[10px] text-[#8C8275] uppercase tracking-wider font-semibold">
                                            <span>{{ t('min_booking') }}</span><span class="text-[#2D3330]">100 pax</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 lg:p-8 pt-0 border-t border-[#FAF7F2] flex items-center justify-between gap-4">
                                <div>
                                    <span class="text-[9px] text-[#8C8275] uppercase tracking-wider font-semibold block">{{ t('starting_from') }}</span>
                                    <span class="text-lg font-bold text-[#4A6B5D]">RM 18.00<span class="text-xs font-normal text-[#8C8275]">/pax</span></span>
                                </div>
                                <button @click="openDrawer('login')" class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-colors duration-200 cursor-pointer">{{ t('select_package') }}</button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- CTA Banner -->
                <div class="bg-[#1C201E] rounded-3xl p-10 text-center space-y-5">
                    <p class="text-[10px] text-[#A8C5B8] uppercase tracking-widest font-semibold">{{ t('special_package') }}</p>
                    <h3 class="text-3xl font-light font-serif-luxury text-white leading-tight">{{ t('want_something_special') }}</h3>
                    <p class="text-[#8E9993] text-sm font-light max-w-md mx-auto">{{ t('custom_menu_desc') }}</p>
                    <Link href="/contact" class="inline-block bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-10 py-4 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200">
                        {{ t('contact_us') }}
                    </Link>
                </div>

            </div>
        </section>

    </FrontLayout>
</template>
