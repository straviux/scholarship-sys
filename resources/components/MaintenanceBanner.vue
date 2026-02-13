<template>
    <div class="maintenance-banner" :class="[
        type === 'info' ? 'bg-blue-50 border-blue-500 text-blue-900' : '',
        type === 'warning' ? 'bg-yellow-50 border-yellow-500 text-yellow-900' : '',
        type === 'critical' ? 'bg-red-50 border-red-500 text-red-900' : '',
    ]">
        <div class="border-l-4 p-4 rounded">
            <div class="flex items-center">
                <div class="flex-1">
                    <h3 class="font-bold text-lg" :class="typeClasses">{{ title }}</h3>
                    <p class="mt-2 text-sm text-center">{{ message }}</p>

                    <div v-if="countdown && countdown.status === 'upcoming'" class="mt-4 pt-4 border-t">
                        <p class="text-sm font-semibold">{{ countdown.message }}</p>
                        <p class="text-2xl font-bold mt-1 font-mono">{{ countdownDisplay }}</p>
                    </div>

                    <div v-if="countdown && countdown.status === 'active'" class="mt-4 pt-4 border-t">
                        <p class="text-sm font-semibold animate-pulse">⚠️ Maintenance active now</p>
                    </div>
                </div>

                <button v-if="dismissible" @click="dismiss" class="ml-4 text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MaintenanceBanner',
    props: {
        title: {
            type: String,
            default: 'System Maintenance',
        },
        message: {
            type: String,
            required: true,
        },
        type: {
            type: String,
            enum: ['info', 'warning', 'critical'],
            default: 'warning',
        },
        countdown: {
            type: Object,
            default: null,
        },
        dismissible: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            countdownDisplay: '',
            countdownInterval: null,
        };
    },
    computed: {
        typeClasses() {
            return {
                'text-blue-600': this.type === 'info',
                'text-yellow-600': this.type === 'warning',
                'text-red-600': this.type === 'critical',
            };
        },
    },
    methods: {
        updateCountdown() {
            if (!this.countdown) return;

            if (this.countdown.status === 'upcoming') {
                const startTime = new Date(this.countdown.start_time).getTime();
                const now = new Date().getTime();
                const secondsRemaining = Math.floor((startTime - now) / 1000);

                if (secondsRemaining <= 0) {
                    this.countdownDisplay = 'Starting...';
                    return;
                }

                const hours = Math.floor(secondsRemaining / 3600);
                const minutes = Math.floor((secondsRemaining % 3600) / 60);
                const seconds = secondsRemaining % 60;

                this.countdownDisplay = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        },
        dismiss() {
            this.$emit('dismiss');
        },
    },
    mounted() {
        this.updateCountdown();
        this.countdownInterval = setInterval(() => {
            this.updateCountdown();
        }, 1000);
    },
    beforeUnmount() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    },
};
</script>

<style scoped>
.maintenance-banner {
    position: relative;
    margin-bottom: 1rem;
}
</style>
