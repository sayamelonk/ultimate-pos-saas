{{-- Toast Container - Add this to your layout --}}
<div x-data="toastManager()"
     x-on:toast.window="addToast($event.detail)"
     class="fixed top-4 right-4 z-50 space-y-3 pointer-events-none">

    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transform transition ease-out duration-300"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transform transition ease-in duration-200"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0"
             class="pointer-events-auto max-w-sm w-full bg-surface rounded-lg shadow-lg border border-border overflow-hidden">

            <div class="p-4">
                <div class="flex items-start gap-3">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <template x-if="toast.type === 'success'">
                            <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        <template x-if="toast.type === 'error'">
                            <svg class="w-5 h-5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        <template x-if="toast.type === 'warning'">
                            <svg class="w-5 h-5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </template>
                        <template x-if="toast.type === 'info'">
                            <svg class="w-5 h-5 text-info-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p x-show="toast.title" class="text-sm font-medium text-text" x-text="toast.title"></p>
                        <p class="text-sm text-muted" :class="{ 'mt-1': toast.title }" x-text="toast.message"></p>
                    </div>

                    {{-- Close Button --}}
                    <button @click="removeToast(toast.id)"
                            class="flex-shrink-0 p-1 -m-1 text-muted hover:text-text rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="h-1 bg-secondary-100">
                <div class="h-full transition-all duration-100"
                     :class="{
                         'bg-success-500': toast.type === 'success',
                         'bg-danger-500': toast.type === 'error',
                         'bg-warning-500': toast.type === 'warning',
                         'bg-info-500': toast.type === 'info'
                     }"
                     :style="{ width: toast.progress + '%' }">
                </div>
            </div>
        </div>
    </template>
</div>

@pushOnce('scripts')
<script>
function toastManager() {
    return {
        toasts: [],
        counter: 0,

        addToast(detail) {
            const id = ++this.counter;
            const toast = {
                id,
                type: detail.type || 'info',
                title: detail.title || null,
                message: detail.message || '',
                duration: detail.duration || 5000,
                progress: 100,
                visible: true
            };

            this.toasts.push(toast);

            // Progress animation
            const startTime = Date.now();
            const progressInterval = setInterval(() => {
                const elapsed = Date.now() - startTime;
                const remaining = toast.duration - elapsed;
                toast.progress = Math.max(0, (remaining / toast.duration) * 100);

                if (remaining <= 0) {
                    clearInterval(progressInterval);
                    this.removeToast(id);
                }
            }, 50);
        },

        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index > -1) {
                this.toasts[index].visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300);
            }
        }
    }
}
</script>
@endPushOnce
