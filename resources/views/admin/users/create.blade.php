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

    <div class="max-w-2xl">
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

                <!-- Outlets -->
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
</x-app-layout>
