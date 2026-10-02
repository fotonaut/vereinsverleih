<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <!-- Einfarbige Variante des Logos (Würfel = Inventar, Pfeile = Verleih); Farbe über currentColor -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" :class="className" v-bind="$attrs">
        <polygon points="32,8 54,20 32,32 10,20" fill="currentColor" />
        <polygon points="10,20 32,32 32,56 10,44" fill="currentColor" opacity=".7" />
        <polygon points="54,20 32,32 32,56 54,44" fill="currentColor" opacity=".45" />
    </svg>
</template>
