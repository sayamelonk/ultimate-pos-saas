{{--
    Global Confirmation Dialog Component
    Usage: Add this component once in your layout, then trigger via Alpine.js events

    Trigger example:
    <button @click="$dispatch('confirm', {
        title: 'Delete Item',
        message: 'Are you sure you want to delete this item?',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'danger',
        onConfirm: () => $refs.deleteForm.submit()
    })">Delete</button>
--}}

<div x-data="confirmDialog()"
     x-show="open"
     x-cloak
     @confirm.window="show($event.detail)"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-secondary-900/50 backdrop-blur-sm"
         @click="close()">
    </div>

    {{-- Dialog --}}
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">

            {{-- Header with Icon --}}
            <div class="p-6 pb-4">
                <div class="flex items-start gap-4">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <template x-if="variant === 'danger'">
                            <div class="w-12 h-12 rounded-full bg-danger-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="variant === 'warning'">
                            <div class="w-12 h-12 rounded-full bg-warning-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="variant === 'info'">
                            <div class="w-12 h-12 rounded-full bg-info-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="variant === 'success'">
                            <div class="w-12 h-12 rounded-full bg-success-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </template>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-text" x-text="title"></h3>
                        <p class="mt-2 text-sm text-muted" x-text="message"></p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 bg-secondary-50 flex justify-end gap-3">
                <button @click="close()"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-lg hover:bg-secondary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-500 transition-colors"
                        x-text="cancelText">
                </button>
                <button @click="confirm()"
                        type="button"
                        :class="{
                            'bg-danger-600 hover:bg-danger-700 focus:ring-danger-500 text-white': variant === 'danger',
                            'bg-warning-600 hover:bg-warning-700 focus:ring-warning-500 text-white': variant === 'warning',
                            'bg-info-600 hover:bg-info-700 focus:ring-info-500 text-white': variant === 'info',
                            'bg-success-600 hover:bg-success-700 focus:ring-success-500 text-white': variant === 'success',
                            'bg-primary hover:bg-primary-600 focus:ring-primary text-white': variant === 'primary'
                        }"
                        class="px-4 py-2 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                        x-text="confirmText">
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDialog() {
    return {
        open: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        variant: 'danger',
        onConfirm: null,
        onCancel: null,

        show(options) {
            this.title = options.title || 'Confirm Action';
            this.message = options.message || 'Are you sure you want to proceed?';
            this.confirmText = options.confirmText || 'Confirm';
            this.cancelText = options.cancelText || 'Cancel';
            this.variant = options.variant || 'danger';
            this.onConfirm = options.onConfirm || null;
            this.onCancel = options.onCancel || null;
            this.open = true;
        },

        confirm() {
            if (this.onConfirm && typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            this.open = false;
        },

        close() {
            if (this.onCancel && typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.open = false;
        }
    }
}
</script>
