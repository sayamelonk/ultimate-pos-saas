<x-app-layout>
    <x-slot name="title">{{ __('admin.edit_user') }} - Ultimate POS</x-slot>

    @section('page-title', __('admin.edit_user'))

    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-button href="{{ route('admin.users.index') }}" variant="ghost" icon="arrow-left" size="sm">
                {{ __('app.back') }}
            </x-button>
            <div>
                <h2 class="text-2xl font-bold text-text">{{ __('admin.edit_user') }}</h2>
                <p class="text-muted mt-1">{{ $user->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input
                    name="name"
                    label="{{ __('admin.full_name') }}"
                    placeholder="{{ __('admin.enter_full_name') }}"
                    :value="$user->name"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        type="email"
                        name="email"
                        label="{{ __('admin.email') }}"
                        placeholder="{{ __('admin.enter_email') }}"
                        :value="$user->email"
                        required
                    />

                    <x-input
                        type="tel"
                        name="phone"
                        label="{{ __('admin.phone') }}"
                        placeholder="{{ __('admin.enter_phone') }}"
                        :value="$user->phone"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            {{ __('admin.new_password') }}
                        </label>
                        <input type="password"
                               name="password"
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                      focus:ring-2 focus:ring-accent/20 focus:border-accent
                                      placeholder:text-muted transition-colors"
                               placeholder="{{ __('admin.leave_blank') }}">
                        @error('password')
                            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text mb-1.5">
                            {{ __('admin.confirm_password') }}
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-border rounded-lg bg-surface text-text
                                      focus:ring-2 focus:ring-accent/20 focus:border-accent
                                      placeholder:text-muted transition-colors"
                               placeholder="{{ __('admin.confirm_new_password') }}">
                    </div>
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
                                       @checked(in_array($role->id, old('roles', $userRoles)))>
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
                            {{ __('admin.assigned_outlets') }}
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($outlets as $outlet)
                                <label class="flex items-center gap-2 p-3 border border-border rounded-lg cursor-pointer hover:bg-secondary-50 transition-colors">
                                    <input type="checkbox"
                                           name="outlets[]"
                                           value="{{ $outlet->id }}"
                                           class="w-4 h-4 rounded border-border text-accent focus:ring-accent/20"
                                           @checked(in_array($outlet->id, old('outlets', $userOutlets)))>
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

                <x-checkbox
                    name="is_active"
                    label="{{ __('app.active') }}"
                    hint="{{ __('admin.inactive_cannot_login') }}"
                    :checked="$user->is_active"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <x-button href="{{ route('admin.users.index') }}" variant="outline-secondary">
                        {{ __('app.cancel') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin.update_user') }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
