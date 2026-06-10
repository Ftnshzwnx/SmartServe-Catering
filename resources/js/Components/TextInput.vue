<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-[#E6E1DA] text-[#2D3330] shadow-xs focus:border-[#4A6B5D] focus:ring-2 focus:ring-[#4A6B5D]/20 transition-all duration-150 text-sm p-3"
        v-model="model"
        ref="input"
    />
</template>
