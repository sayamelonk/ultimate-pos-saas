<x-app-layout>
    <x-slot name="title">Create User - Ultimate POS</x-slot>

    @section('page-title', 'Create User')

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.users.index') }}" variant="ghost" icon="arrow-left" size="sm">
                Back
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">Create User</h2>
                <p class="text-muted mt-1">Add a new staff member</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl" x-data="userForm()">
        <x-card>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
                @csrf

                <x-input
                    name="name"
                    label="Full Name"
                    placeholder="Enter full name"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="email"
                        name="email"
                        label="Email Address"
                        placeholder="Enter email"
                        required
                    />

                    <x-input
                        type="tel"
                        name="phone"
                        label="Phone Number"
                        placeholder="Enter phone number"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            Password <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               name="password"
                               required
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                      focus:ring-2 focus:ring-accent/20 focus:border-accent
                                      placeholder:text-muted transition-colors"
                               placeholder="Create password">
                        @error('password')
                            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               required
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                      focus:ring-2 focus:ring-accent/20 focus:border-accent
                                      placeholder:text-muted transition-colors"
                               placeholder="Confirm password">
                    </div>
                </div>

                <!-- Roles -->
                <div>
                    <label class="block text-sm font-medium text-text mb-2">
                        Roles <span class="text-danger">*</span>
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
                            Tenant <span class="text-danger">*</span>
                        </label>
                        <select name="tenant_id"
                                x-model="selectedTenant"
                                @change="loadOutlets()"
                                class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                       focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors"
                                required>
                            <option value="">-- Select Tenant --</option>
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
                            Assigned Outlets
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
                        <p class="mt-1.5 text-sm text-muted">First selected outlet will be the default.</p>
                    </div>

                    <!-- Loading indicator -->
                    <div x-show="loading" class="flex items-center gap-2 text-muted">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Loading outlets...</span>
                    </div>

                    <!-- No outlets message -->
                    <div x-show="selectedTenant && !loading && outlets.length === 0" class="text-sm text-warning bg-warning/10 p-3 rounded-lg">
                        No active outlets found for this tenant.
                    </div>
                @else
                    <!-- Non-Super Admin: Show outlets directly -->
                    @if($outlets->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">
                                Assigned Outlets
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
                            <p class="mt-1.5 text-sm text-muted">First selected outlet will be the default.</p>
                        </div>
                    @endif
                @endif

                <x-checkbox
                    name="is_active"
                    label="Active"
                    hint="Inactive users cannot log in"
                    checked
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.users.index') }}" variant="outline-secondary">
                        Cancel
                    </x-button>
                    <x-button type="submit">
                        Create User
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
