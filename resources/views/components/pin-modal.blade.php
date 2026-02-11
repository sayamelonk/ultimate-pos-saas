@props([
    'id' => 'pin-modal',
    'title' => 'Authorization Required',
    'pinLength' => 4,
])

<div
    x-data="pinModal('{{ $id }}', {{ $pinLength }})"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="close()"
>
    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50"
        @click="close()"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-sm bg-surface rounded-xl shadow-xl"
            @click.stop
        >
            <!-- Header -->
            <div class="p-6 pb-0 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-warning/10 flex items-center justify-center">
                    <x-icon name="shield-check" class="w-8 h-8 text-warning" />
                </div>
                <h3 class="text-lg font-semibold text-text" x-text="title">{{ $title }}</h3>
                <p class="text-sm text-muted mt-1" x-text="subtitle">Enter supervisor PIN to continue</p>
            </div>

            <!-- PIN Input -->
            <div class="p-6">
                <!-- Hidden actual input -->
                <input
                    type="password"
                    x-ref="pinInput"
                    x-model="pin"
                    @input="handleInput"
                    @keydown.enter="submit()"
                    maxlength="{{ $pinLength }}"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    class="sr-only"
                    autocomplete="off"
                />

                <!-- Visual PIN dots -->
                <div class="flex justify-center gap-3 mb-6" @click="$refs.pinInput.focus()">
                    @for($i = 0; $i < $pinLength; $i++)
                        <div
                            class="w-12 h-12 border-2 rounded-lg flex items-center justify-center transition-all cursor-pointer"
                            :class="{
                                'border-primary bg-primary/5': pin.length === {{ $i }},
                                'border-border': pin.length !== {{ $i }} && pin.length <= {{ $i }},
                                'border-primary': pin.length > {{ $i }}
                            }"
                        >
                            <div
                                x-show="pin.length > {{ $i }}"
                                class="w-3 h-3 rounded-full bg-primary"
                            ></div>
                        </div>
                    @endfor
                </div>

                <!-- Error Message -->
                <div
                    x-show="error"
                    x-transition
                    class="mb-4 p-3 bg-danger/10 border border-danger/20 rounded-lg text-center"
                >
                    <p class="text-sm text-danger" x-text="error"></p>
                </div>

                <!-- Numeric Keypad -->
                <div class="grid grid-cols-3 gap-2">
                    @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $num)
                        <button
                            type="button"
                            @click="addDigit('{{ $num }}')"
                            class="h-14 text-xl font-semibold rounded-lg bg-secondary-100 hover:bg-secondary-200 text-text transition-colors"
                        >
                            {{ $num }}
                        </button>
                    @endforeach
                    <button
                        type="button"
                        @click="close()"
                        class="h-14 text-sm font-medium rounded-lg bg-secondary-100 hover:bg-secondary-200 text-muted transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="addDigit('0')"
                        class="h-14 text-xl font-semibold rounded-lg bg-secondary-100 hover:bg-secondary-200 text-text transition-colors"
                    >
                        0
                    </button>
                    <button
                        type="button"
                        @click="removeDigit()"
                        class="h-14 rounded-lg bg-secondary-100 hover:bg-secondary-200 text-text transition-colors flex items-center justify-center"
                    >
                        <x-icon name="backspace" class="w-6 h-6" />
                    </button>
                </div>

                <!-- Submit Button -->
                <button
                    type="button"
                    @click="submit()"
                    :disabled="pin.length < pinLength || loading"
                    class="w-full mt-4 h-12 rounded-lg bg-primary text-white font-medium hover:bg-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <span x-show="!loading">Authorize</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Verifying...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function pinModal(id, pinLength) {
    return {
        open: false,
        pin: '',
        pinLength: pinLength,
        loading: false,
        error: '',
        title: 'Authorization Required',
        subtitle: 'Enter supervisor PIN to continue',
        action: '',
        outletId: '',
        referenceType: null,
        referenceId: null,
        referenceNumber: null,
        amount: null,
        reason: null,
        metadata: null,
        onSuccess: null,
        onCancel: null,

        init() {
            window.addEventListener('open-pin-modal', (e) => {
                if (e.detail.id === id) {
                    this.show(e.detail);
                }
            });
        },

        show(options = {}) {
            this.pin = '';
            this.error = '';
            this.loading = false;
            this.title = options.title || 'Authorization Required';
            this.subtitle = options.subtitle || 'Enter supervisor PIN to continue';
            this.action = options.action || '';
            this.outletId = options.outletId || '';
            this.referenceType = options.referenceType || null;
            this.referenceId = options.referenceId || null;
            this.referenceNumber = options.referenceNumber || null;
            this.amount = options.amount || null;
            this.reason = options.reason || null;
            this.metadata = options.metadata || null;
            this.onSuccess = options.onSuccess || null;
            this.onCancel = options.onCancel || null;
            this.open = true;

            this.$nextTick(() => {
                this.$refs.pinInput.focus();
            });
        },

        close() {
            this.open = false;
            if (this.onCancel) {
                this.onCancel();
            }
        },

        addDigit(digit) {
            if (this.pin.length < this.pinLength) {
                this.pin += digit;
                this.error = '';
            }
        },

        removeDigit() {
            this.pin = this.pin.slice(0, -1);
            this.error = '';
        },

        handleInput() {
            // Filter non-numeric characters
            this.pin = this.pin.replace(/[^0-9]/g, '').slice(0, this.pinLength);
            this.error = '';
        },

        async submit() {
            if (this.pin.length < this.pinLength || this.loading) return;

            this.loading = true;
            this.error = '';

            try {
                const response = await fetch('{{ route("pos.auth.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        pin: this.pin,
                        action: this.action,
                        outlet_id: this.outletId,
                        reference_type: this.referenceType,
                        reference_id: this.referenceId,
                        reference_number: this.referenceNumber,
                        amount: this.amount,
                        reason: this.reason,
                        metadata: this.metadata,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.open = false;
                    if (this.onSuccess) {
                        this.onSuccess(data);
                    }
                } else {
                    this.error = data.message || 'Authorization failed';
                    this.pin = '';
                    this.$refs.pinInput.focus();
                }
            } catch (err) {
                this.error = 'An error occurred. Please try again.';
                this.pin = '';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
