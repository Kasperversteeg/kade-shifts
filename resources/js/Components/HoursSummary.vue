<script setup>
import dayjs from 'dayjs';
import { computed } from 'vue';

const props = defineProps({
    total: Number,
    month: String,
});

const monthName = computed(() => {
    return dayjs(props.month + '-01').format('MMMM YYYY');
});
</script>

<template>
    <div class="stats shadow w-full">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">{{ monthName }}</div>
            <div class="stat-value text-primary">{{ total }}{{ $t('summary.hoursUnit') }}</div>
            <div class="stat-desc">{{ $t('summary.totalHours') }}</div>
        </div>
    </div>
</template>
