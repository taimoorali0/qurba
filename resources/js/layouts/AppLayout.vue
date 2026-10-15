<script setup lang="ts">
// Account/settings pages share the same Qurba shell as the rest of the app.
import QurbaShell from '@/layouts/QurbaShell.vue';
import type { BreadcrumbItemType } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), { breadcrumbs: () => [] });
</script>

<template>
    <QurbaShell>
        <slot />
    </QurbaShell>
</template>
