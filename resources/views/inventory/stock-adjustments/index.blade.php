<x-app-layout>
    <x-slot name="title">{{ __('inventory.stock_adjustments') }} - Ultimate POS</x-slot>

    @section('page-title', __('inventory.stock_adjustments'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('inventory.stock_adjustments') }}</h2>
                <p class="text-muted mt-1">{{ __('inventory.manage_stock_adjustments') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('inventory.stock-adjustments.stock-take') }}" variant="outline-secondary" icon="clipboard-list">
                    {{ __('inventory.stock_take') }}
                </x-button>
                <x-button href="{{ route('inventory.stock-adjustments.create') }}" icon="plus">
                    {{ __('inventory.create_adjustment') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <x-card>
        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('inventory.stock-adjustments.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <x-input
                        type="search"
                        name="search"
                        :placeholder="__('inventory.search_adjustment')"
                        :value="request('search')"
                    />
                </div>
                <x-select name="outlet_id" class="w-48">
                    <option value="">{{ __('admin.all_outlets') }}</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="type" class="w-40">
                    <option value="">{{ __('inventory.all_types') }}</option>
                    <option value="stock_take" @selected(request('type') === 'stock_take')>{{ __('inventory.type_stock_take') }}</option>
                    <option value="correction" @selected(request('type') === 'correction')>{{ __('inventory.type_correction') }}</option>
                    <option value="damage" @selected(request('type') === 'damage')>{{ __('inventory.type_damage') }}</option>
                    <option value="loss" @selected(request('type') === 'loss')>{{ __('inventory.type_loss') }}</option>
                    <option value="found" @selected(request('type') === 'found')>{{ __('inventory.type_found') }}</option>
                </x-select>
                <x-select name="status" class="w-36">
                    <option value="">{{ __('inventory.all_status') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('app.status_draft') }}</option>
                    <option value="approved" @selected(request('status') === 'approved')>{{ __('app.status_approved') }}</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>{{ __('app.status_rejected') }}</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('app.status_cancelled') }}</option>
                </x-select>
                <x-button type="submit" variant="secondary">
                    {{ __('app.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'outlet_id', 'type', 'status']))
                    <x-button href="{{ route('inventory.stock-adjustments.index') }}" variant="ghost">
                        {{ __('app.clear') }}
                    </x-button>
                @endif
            </form>
        </div>

        <!-- Table -->
        @if($adjustments->count() > 0)
            <x-table>
                <x-slot name="head">
                    <x-th>{{ __('inventory.adjustment_number') }}</x-th>
                    <x-th>{{ __('app.date') }}</x-th>
                    <x-th>{{ __('admin.outlet') }}</x-th>
                    <x-th>{{ __('app.type') }}</x-th>
                    <x-th>{{ __('inventory.reason') }}</x-th>
                    <x-th align="right">{{ __('inventory.items') }}</x-th>
                    <x-th align="center">{{ __('app.status') }}</x-th>
                    <x-th align="right">{{ __('app.actions') }}</x-th>
                </x-slot>

                @foreach($adjustments as $adjustment)
                    <tr>
                        <x-td>
                            <a href="{{ route('inventory.stock-adjustments.show', $adjustment) }}" class="text-accent hover:underline font-medium">
                                {{ $adjustment->adjustment_number }}
                            </a>
                        </x-td>
                        <x-td>{{ $adjustment->adjustment_date->translatedFormat('d M Y') }}</x-td>
                        <x-td>{{ $adjustment->outlet->name }}</x-td>
                        <x-td>
                            @switch($adjustment->type)
                                @case('stock_take')
                                    <x-badge type="info">{{ __('inventory.type_stock_take') }}</x-badge>
                                    @break
                                @case('correction')
                                    <x-badge type="warning">{{ __('inventory.type_correction') }}</x-badge>
                                    @break
                                @case('damage')
                                    <x-badge type="danger">{{ __('inventory.type_damage') }}</x-badge>
                                    @break
                                @case('loss')
                                    <x-badge type="danger">{{ __('inventory.type_loss') }}</x-badge>
                                    @break
                                @case('found')
                                    <x-badge type="success">{{ __('inventory.type_found') }}</x-badge>
                                    @break
                                @default
                                    <x-badge type="secondary">{{ $adjustment->type }}</x-badge>
                            @endswitch
                        </x-td>
                        <x-td>{{ Str::limit($adjustment->reason, 30) }}</x-td>
                        <x-td align="right">{{ $adjustment->items->count() }}</x-td>
                        <x-td align="center">
                            @switch($adjustment->status)
                                @case('draft')
                                    <x-badge type="secondary">{{ __('app.status_draft') }}</x-badge>
                                    @break
                                @case('approved')
                                    <x-badge type="success">{{ __('app.status_approved') }}</x-badge>
                                    @break
                                @case('rejected')
                                    <x-badge type="danger">{{ __('app.status_rejected') }}</x-badge>
                                    @break
                                @case('cancelled')
                                    <x-badge type="warning">{{ __('app.status_cancelled') }}</x-badge>
                                    @break
                            @endswitch
                        </x-td>
                        <x-td align="right">
                            <div x-data>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <button class="p-2 hover:bg-secondary-100 rounded-lg transition-colors">
                                            <x-icon name="dots-vertical" class="w-5 h-5 text-muted" />
                                        </button>
                                    </x-slot>

                                    <x-dropdown-item href="{{ route('inventory.stock-adjustments.show', $adjustment) }}">
                                        <x-icon name="eye" class="w-4 h-4" />
                                        {{ __('app.details') }}
                                    </x-dropdown-item>
                                    @if($adjustment->status === 'draft')
                                        <x-dropdown-item href="{{ route('inventory.stock-adjustments.edit', $adjustment) }}">
                                            <x-icon name="pencil" class="w-4 h-4" />
                                            {{ __('app.edit') }}
                                        </x-dropdown-item>
                                        <x-dropdown-item
                                            type="button"
                                            @click="$dispatch('open-modal', 'approve-adjustment-{{ $loop->index }}')"
                                        >
                                            <x-icon name="check" class="w-4 h-4" />
                                            {{ __('app.approve') }}
                                        </x-dropdown-item>
                                        <x-dropdown-item
                                            type="button"
                                            danger
                                            @click="$dispatch('open-modal', 'delete-adjustment-{{ $loop->index }}')"
                                        >
                                            <x-icon name="trash" class="w-4 h-4" />
                                            {{ __('app.delete') }}
                                        </x-dropdown-item>
                                    @endif
                                </x-dropdown>
                            </div>
                        </x-td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-6">
                <x-pagination :paginator="$adjustments" />
            </div>
        @else
            <x-empty-state
                :title="__('inventory.no_adjustments_found')"
                :description="__('inventory.no_adjustments_description')"
                icon="clipboard-list"
            >
                <x-button href="{{ route('inventory.stock-adjustments.create') }}" icon="plus">
                    {{ __('inventory.create_adjustment') }}
                </x-button>
            </x-empty-state>
        @endif
    </x-card>

    {{-- Approve/Delete Modals --}}
    @foreach($adjustments as $adjustment)
        @if($adjustment->status === 'draft')
            <x-confirm-modal
                name="approve-adjustment-{{ $loop->index }}"
                :title="__('inventory.approve_adjustment')"
                :message="__('inventory.confirm_approve_adjustment')"
                :confirmText="__('app.approve')"
                type="success"
                @click="document.getElementById('approve-form-{{ $loop->index }}').submit()"
            />
            <form id="approve-form-{{ $loop->index }}" action="{{ route('inventory.stock-adjustments.approve', $adjustment) }}" method="POST" class="hidden">
                @csrf
            </form>

            <x-confirm-modal
                name="delete-adjustment-{{ $loop->index }}"
                :title="__('inventory.delete_adjustment')"
                :message="__('inventory.confirm_delete_adjustment', ['number' => $adjustment->adjustment_number])"
                :confirmText="__('app.delete')"
                type="danger"
                @click="document.getElementById('delete-form-{{ $loop->index }}').submit()"
            />
            <form id="delete-form-{{ $loop->index }}" action="{{ route('inventory.stock-adjustments.destroy', $adjustment) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
</x-app-layout>
