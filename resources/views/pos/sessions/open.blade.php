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

                    <x-input
                        label="Opening Cash (Rp)"
                        name="opening_cash"
                        type="number"
                        :value="old('opening_cash', 0)"
                        required
                        prefix="Rp"
                    />

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
