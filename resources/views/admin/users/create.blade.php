<x-app-layout>
    <x-slot name="title">{{ __('admin.create_user') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.create_user'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.users.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.create_user') }}</h2>
                <p class="text-muted mt-1">{{ __('admin.add_new_staff') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl" x-data="userForm()">
        <x-card>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
                @csrf

                <x-input
                    name="name"
                    label="{{ __('admin.full_name') }}"
                    placeholder="{{ __('admin.enter_full_name') }}"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="email"
                        name="email"
                        label="{{ __('admin.email') }}"
                        placeholder="{{ __('admin.enter_email') }}"
                        required
                    />

                    <x-input
                        type="tel"
                        name="phone"
                        label="{{ __('admin.phone') }}"
                        placeholder="{{ __('admin.enter_phone') }}"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            {{ __('admin.password') }} <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               name="password"
                               required
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                      focus:ring-2 focus:ring-accent/20 focus:border-accent
                                      placeholder:text-muted transition-colors"
                               placeholder="{{ __('admin.create_password') }}">
                        @error('password')
                            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            {{ __('admin.confirm_password') }} <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               required
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                      focus:ring-2 focus:ring-accent/20 focus:border-accent
                                      placeholder:text-muted transition-colors"
                               placeholder="{{ __('admin.confirm_password') }}">
                    </div>
                </div>

                <!-- PIN -->
                <div>
                    <label class="block text-sm font-medium text-text mb-1.5">
                        PIN
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <input type="password"
                                   name="pin"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   maxlength="{{ $pinLength }}"
                                   class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                          focus:ring-2 focus:ring-accent/20 focus:border-accent
                                          placeholder:text-muted transition-colors"
                                   placeholder="Masukkan {{ $pinLength }} digit PIN">
                            @error('pin')
                                <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <input type="password"
                                   name="pin_confirmation"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   maxlength="{{ $pinLength }}"
                                   class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                          focus:ring-2 focus:ring-accent/20 focus:border-accent
                                          placeholder:text-muted transition-colors"
                                   placeholder="Konfirmasi PIN">
                        </div>
                    </div>
                    <p class="mt-1.5 text-sm text-muted">PIN untuk login Kitchen & Waiter app ({{ $pinLength }} digit). Kosongkan jika tidak perlu.</p>
                </div>

                <!-- Roles -->
                <div>
                    <label class="block text-sm font-medium text-text mb-2">
                        {{ __('admin.roles') }} <span class="text-danger">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 p-3 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                                <input type="checkbox"
                                       name="roles[]"
                                       value="{{ $role->id }}"
                                       class="w-4 h-4 rounded border-border text-primary focus:ring-primary/20"
                                       @checked(in_array($role->id, old('roles', [])))>
                                <div>
                                    <span class="text-sm font-medium text-text">{{ $role->name }}</span>
                                    @if($role->description)
                                        <p class="text-xs text-muted">{{ Str::limit($role->description, 50) }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tenant Selection (Super Admin only) -->
                @if($tenants->count() > 0)
                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            {{ __('admin.tenant') }} <span class="text-danger">*</span>
                        </label>
                        <select name="tenant_id"
                                x-model="selectedTenant"
                                @change="loadOutlets()"
                                class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                       focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
                                required>
                            <option value="">-- {{ __('admin.select_tenant') }} --</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                    {{ $tenant->name }} ({{ $tenant->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('tenant_id')
                            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Dynamic Outlets based on selected Tenant -->
                    <div x-show="outlets.length > 0" x-cloak>
                        <label class="block text-sm font-medium text-text mb-2">
                            {{ __('admin.assigned_outlets') }}
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="outlet in outlets" :key="outlet.id">
                                <label class="flex items-center gap-2 p-3 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                                    <input type="checkbox"
                                           name="outlets[]"
                                           :value="outlet.id"
                                           class="w-4 h-4 rounded border-border text-accent focus:ring-accent/20">
                                    <div>
                                        <span class="text-sm font-medium text-text" x-text="outlet.name"></span>
                                        <p class="text-xs text-muted" x-text="outlet.code"></p>
                                    </div>
                                </label>
                            </template>
                        </div>
                        <p class="mt-1.5 text-sm text-muted">{{ __('admin.first_outlet_default') }}</p>
                    </div>

                    <!-- Loading indicator -->
                    <div x-show="loading" class="flex items-center gap-2 text-muted">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">{{ __('admin.loading_outlets') }}</span>
                    </div>

                    <!-- No outlets message -->
                    <div x-show="selectedTenant && !loading && outlets.length === 0" class="text-sm text-warning bg-warning/10 p-3 rounded-lg">
                        {{ __('admin.no_outlets_for_tenant') }}
                    </div>
                @else
                    <!-- Non-Super Admin: Show outlets directly -->
                    @if($outlets->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">
                                {{ __('admin.assigned_outlets') }}
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($outlets as $outlet)
                                    <label class="flex items-center gap-2 p-3 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                                        <input type="checkbox"
                                               name="outlets[]"
                                               value="{{ $outlet->id }}"
                                               class="w-4 h-4 rounded border-border text-accent focus:ring-accent/20"
                                               @checked(in_array($outlet->id, old('outlets', [])))>
                                        <div>
                                            <span class="text-sm font-medium text-text">{{ $outlet->name }}</span>
                                            <p class="text-xs text-muted">{{ $outlet->code }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1.5 text-sm text-muted">{{ __('admin.first_outlet_default') }}</p>
                        </div>
                    @endif
                @endif

                <x-checkbox
                    name="is_active"
                    label="{{ __('app.active') }}"
                    hint="{{ __('admin.inactive_cannot_login') }}"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.users.index') }}" variant="outline-secondary">
                        {{ __('app.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.create_user') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    @if($tenants->count() > 0)
    @push('scripts')
    <script>
        function userForm() {
            return {
                selectedTenant: '{{ old('tenant_id', '') }}',
                outlets: [],
                loading: false,

                init() {
                    if (this.selectedTenant) {
                        this.loadOutlets();
                    }
                },

                async loadOutlets() {
                    if (!this.selectedTenant) {
                        this.outlets = [];
                        return;
                    }

                    this.loading = true;
                    try {
                        const response = await fetch(`/admin/users-outlets-by-tenant/${this.selectedTenant}`);
                        if (response.ok) {
                            this.outlets = await response.json();
                        } else {
                            this.outlets = [];
                        }
                    } catch (error) {
                        console.error('Failed to load outlets:', error);
                        this.outlets = [];
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
    @endpush
    @endif
</x-app-layout>
