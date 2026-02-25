<x-app-layout>
    <x-slot name="title">Open Session - Ultimate POS</x-slot>

    @section('page-title', 'Open Session')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pos.sessions.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Open Session</h2>
                <p class="text-muted mt-1">Start a new cashier shift</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <x-card title="Session Details">
            <form method="POST" action="{{ route('pos.sessions.store') }}">
                @csrf

                <div class="space-y-4">
                    <x-select label="Outlet" name="outlet_id" required>
                        <option value="">Select Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('outlet_id') === $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </x-select>

                    <div x-data="{
                        rawValue: {{ old('opening_cash', 0) }},
                        get formatted() {
                            return this.rawValue.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        },
                        updateValue(e) {
                            const val = e.target.value.replace(/\./g, '');
                            this.rawValue = parseInt(val) || 0;
                        }
                    }">
                        <label class="block text-sm font-medium text-text mb-1.5">
                            Opening Cash (Rp) <span class="text-danger">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted">Rp</span>
                            <input
                                type="text"
                                :value="formatted"
                                @input="updateValue($event)"
                                class="block w-full pl-10 pr-4 py-2.5 text-sm rounded-lg border border-border text-text placeholder-muted focus:border-accent focus:ring-2 focus:ring-accent/20 bg-surface"
                                required
                            >
                            <input type="hidden" name="opening_cash" :value="rawValue">
                        </div>
                        @error('opening_cash')
                            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-textarea
                        label="Notes"
                        name="opening_notes"
                        rows="3"
                        placeholder="Optional notes for this session..."
                    >{{ old('opening_notes') }}</x-textarea>

                    <div class="pt-4">
                        <x-button type="submit" class="w-full">
                            Open Session
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
