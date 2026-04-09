<x-app-layout>
    <x-slot name="title">{{ __('pos.open_session') }} - Ultimate POS</x-slot>

    @section('page-title', __('pos.open_session'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('pos.sessions.index') }}" variant="ghost" size="sm">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('pos.open_session') }}</h2>
                <p class="text-muted mt-1">{{ __('pos.start_new_shift') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <x-card title="{{ __('pos.session_details') }}">
            <form method="POST" action="{{ route('pos.sessions.store') }}">
                @csrf

                <div class="space-y-4">
                    <x-select label="{{ __('pos.outlet') }}" name="outlet_id" required>
                        <option value="">{{ __('pos.select_outlet') }}</option>
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
                            {{ __('pos.opening_cash') }} (Rp) <span class="text-danger">*</span>
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
                        label="{{ __('pos.notes') }}"
                        name="opening_notes"
                        rows="3"
                        placeholder="{{ __('pos.notes_placeholder') }}"
                    >{{ old('opening_notes') }}</x-textarea>

                    <div class="pt-4">
                        <x-button type="submit" class="w-full">
                            {{ __('pos.open_session') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
