<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import FrontLayout from '@/Layouts/FrontLayout.vue';
import { useLocalization } from '@/Composables/useLocalization';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const { t } = useLocalization();

const activeFaqIndex = ref(null);

const toggleFaq = (index) => {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
};

const faqs = computed(() => [
    {
        q: t('faq_q1_text'),
        a: t('faq_a1_text')
    },
    {
        q: t('faq_q2_text'),
        a: t('faq_a2_text')
    },
    {
        q: t('faq_q3_text'),
        a: t('faq_a3_text')
    },
    {
        q: t('faq_q4_text'),
        a: t('faq_a4_text')
    },
    {
        q: t('faq_q5_text'),
        a: t('faq_a5_text')
    },
    {
        q: t('faq_q6_text'),
        a: t('faq_a6_text')
    },
    {
        q: t('faq_q7_text'),
        a: t('faq_a7_text')
    },
    {
        q: t('faq_q8_text'),
        a: t('faq_a8_text')
    },
]);
</script>

<template>
    <Head title="FAQ — SmartServe Catering" />

    <FrontLayout :canLogin="canLogin" :canRegister="canRegister">

        <!-- Hero Section -->
        <section class="py-20 bg-white border-b border-[#E6E1DA] font-sans-modern">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="max-w-2xl space-y-4">
                    <span class="text-xs font-semibold text-[#4A6B5D] tracking-widest uppercase block">{{ t('faq') }}</span>
                    <h1 class="text-5xl lg:text-6xl font-light text-[#1C201E] font-serif-luxury leading-tight">
                        {{ t('faq_title_1') }} <span class="italic text-[#4A6B5D]">{{ t('faq_title_2') }}</span> {{ t('faq_title_3') }}
                    </h1>
                    <p class="text-[#5C6460] text-sm leading-relaxed font-light max-w-lg">
                        {{ t('faq_subtitle_desc') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- FAQ Accordion -->
        <section class="py-20 bg-[#FAF7F2] font-sans-modern">
            <div class="max-w-4xl mx-auto px-6 space-y-12">

                <!-- Stats quick bar -->
                <div class="grid grid-cols-3 gap-6">
                    <div class="bg-white border border-[#E6E1DA] rounded-2xl p-5 text-center space-y-1">
                        <div class="text-2xl font-light text-[#4A6B5D] font-serif-luxury">500+</div>
                        <div class="text-[10px] text-[#8C8275] uppercase tracking-widest font-semibold">{{ t('stats_events') }}</div>
                    </div>
                    <div class="bg-white border border-[#E6E1DA] rounded-2xl p-5 text-center space-y-1">
                        <div class="text-2xl font-light text-[#4A6B5D] font-serif-luxury">100%</div>
                        <div class="text-[10px] text-[#8C8275] uppercase tracking-widest font-semibold">{{ t('stats_halal') }}</div>
                    </div>
                    <div class="bg-white border border-[#E6E1DA] rounded-2xl p-5 text-center space-y-1">
                        <div class="text-2xl font-light text-[#4A6B5D] font-serif-luxury">15+</div>
                        <div class="text-[10px] text-[#8C8275] uppercase tracking-widest font-semibold">{{ t('years_serving') }}</div>
                    </div>
                </div>

                <!-- FAQ Items -->
                <div class="space-y-3">
                    <div
                        v-for="(item, index) in faqs"
                        :key="index"
                        class="bg-white border border-[#E6E1DA] rounded-2xl overflow-hidden transition-all duration-300"
                        :class="activeFaqIndex === index ? 'border-[#4A6B5D]/40 shadow-md' : 'shadow-xs'"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none group cursor-pointer"
                        >
                            <span class="text-sm font-semibold text-[#1C201E] group-hover:text-[#4A6B5D] transition-colors pr-4">
                                {{ item.q }}
                            </span>
                            <span
                                class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs transition-all duration-300"
                                :class="activeFaqIndex === index ? 'bg-[#4A6B5D] text-white rotate-45' : 'bg-[#EBEFEF] text-[#8C8275]'"
                            >
                                <i class="fas fa-plus"></i>
                            </span>
                        </button>

                        <div
                            v-show="activeFaqIndex === index"
                            class="px-6 pb-6 border-t border-[#FAF7F2] pt-4"
                        >
                            <p class="text-sm text-[#5C6460] leading-relaxed font-light">{{ item.a }}</p>
                        </div>
                    </div>
                </div>

                <!-- Still have questions? CTA -->
                <div class="bg-[#1C201E] rounded-3xl p-10 flex flex-col md:flex-row items-center gap-8 justify-between">
                    <div class="space-y-2">
                        <p class="text-white text-lg font-semibold font-serif-luxury uppercase tracking-wider">{{ t('still_have_questions') }}</p>
                        <p class="text-[#8E9993] text-sm font-light">{{ t('still_have_questions_desc') }}</p>
                    </div>
                    <div class="flex gap-4 flex-shrink-0">
                        <Link href="/contact" class="bg-[#4A6B5D] hover:bg-[#3D574B] text-white px-7 py-3 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 whitespace-nowrap">
                            {{ t('contact_us') }}
                        </Link>
                        <a href="https://wa.me/60123456789" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-7 py-3 rounded-xl text-xs font-semibold uppercase tracking-widest transition-colors duration-200 flex items-center gap-2 whitespace-nowrap">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>

            </div>
        </section>

    </FrontLayout>
</template>
