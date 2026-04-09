<x-app-layout :title="__('Kelola Meja')">
    <div x-data="tableManager()" class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-foreground">Kelola Meja & Floor</h1>
        </div>

        {{-- Floors Section --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-foreground">Floor / Area</h2>
                <button @click="showAddFloor = true"
                        style="padding:8px 16px;background:#6366f1;color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;">
                    + Tambah Floor
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($floors as $floor)
                    <div class="bg-surface rounded-xl border border-border p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-foreground">{{ $floor->name }}</h3>
                                @if($floor->description)
                                    <p class="text-xs text-muted mt-0.5">{{ $floor->description }}</p>
                                @endif
                                <p class="text-xs text-muted mt-1">{{ $floor->tables->count() }} meja</p>
                            </div>
                            <button @click="deleteFloor('{{ $floor->id }}')"
                                    style="padding:4px 8px;background:#fee2e2;color:#dc2626;font-size:11px;border-radius:6px;border:none;cursor:pointer;">
                                Hapus
                            </button>
                        </div>
                    </div>
                @endforeach
                @if($floors->count() === 0)
                    <div class="col-span-full bg-surface rounded-xl border border-border p-8 text-center">
                        <p class="text-muted">Belum ada floor. Tambahkan floor terlebih dahulu.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Add Floor Modal --}}
        <div x-show="showAddFloor" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showAddFloor = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Tambah Floor</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Floor *</label>
                        <input type="text" x-model="newFloor.name" placeholder="cth: Lantai 1, Garden, VIP"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <input type="text" x-model="newFloor.description" placeholder="Opsional"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showAddFloor = false"
                            style="padding:8px 16px;background:#f3f4f6;color:#374151;font-size:13px;border-radius:8px;border:none;cursor:pointer;">
                        Batal
                    </button>
                    <button @click="createFloor()" :disabled="!newFloor.name || floorLoading"
                            style="padding:8px 16px;background:#6366f1;color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;opacity:1;"
                            :style="(!newFloor.name || floorLoading) && 'opacity:0.5'">
                        <span x-text="floorLoading ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tables Section --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-foreground">Daftar Meja</h2>
                @if($floors->count() > 0)
                <button @click="showAddTable = true"
                        style="padding:8px 16px;background:#16a34a;color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;">
                    + Tambah Meja
                </button>
                @endif
            </div>
            <div class="bg-surface rounded-xl border border-border overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Nomor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Floor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Kapasitas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted uppercase w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($tables as $table)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ $table->number }}</td>
                                <td class="px-4 py-3 text-sm text-muted">{{ $table->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted">{{ $table->floor?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted">{{ $table->capacity }} orang</td>
                                <td class="px-4 py-3">
                                    @if($table->is_active)
                                        <span style="display:inline-block;padding:2px 8px;background:#dcfce7;color:#166534;font-size:11px;font-weight:500;border-radius:9999px;">Aktif</span>
                                    @else
                                        <span style="display:inline-block;padding:2px 8px;background:#f3f4f6;color:#6b7280;font-size:11px;font-weight:500;border-radius:9999px;">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div style="display:flex;justify-content:flex-end;gap:6px;">
                                        <button @click="editTable({{ $table->toJson() }})"
                                                style="padding:4px 10px;background:#eef2ff;color:#4f46e5;font-size:12px;border-radius:6px;border:none;cursor:pointer;">
                                            Edit
                                        </button>
                                        <button @click="deleteTable('{{ $table->id }}')"
                                                style="padding:4px 10px;background:#fee2e2;color:#dc2626;font-size:12px;border-radius:6px;border:none;cursor:pointer;">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-muted">Belum ada meja.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Table Modal --}}
        <div x-show="showAddTable" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showAddTable = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Tambah Meja</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Floor *</label>
                        <select x-model="newTable.floor_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Pilih Floor</option>
                            @foreach($floors as $floor)
                                <option value="{{ $floor->id }}">{{ $floor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Meja *</label>
                        <input type="text" x-model="newTable.number" placeholder="cth: 1, A1, VIP-01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Meja</label>
                        <input type="text" x-model="newTable.name" placeholder="cth: Meja Teras, VIP Room (opsional)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas</label>
                        <input type="number" x-model="newTable.capacity" min="1" max="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div x-show="tableError" class="mt-3 p-2 bg-red-50 text-red-700 rounded-lg text-sm" x-text="tableError"></div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showAddTable = false"
                            style="padding:8px 16px;background:#f3f4f6;color:#374151;font-size:13px;border-radius:8px;border:none;cursor:pointer;">
                        Batal
                    </button>
                    <button @click="createTable()" :disabled="!newTable.floor_id || !newTable.number || tableLoading"
                            style="padding:8px 16px;background:#16a34a;color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;opacity:1;"
                            :style="(!newTable.floor_id || !newTable.number || tableLoading) && 'opacity:0.5'">
                        <span x-text="tableLoading ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Edit Table Modal --}}
        <div x-show="showEditTable" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showEditTable = false">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Edit Meja</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Meja *</label>
                        <input type="text" x-model="editingTable.number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Meja</label>
                        <input type="text" x-model="editingTable.name" placeholder="Opsional"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas</label>
                        <input type="number" x-model="editingTable.capacity" min="1" max="100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" x-model="editingTable.is_active" id="edit_active" class="rounded text-primary">
                        <label for="edit_active" class="text-sm text-gray-700">Aktif</label>
                    </div>
                </div>
                <div x-show="tableError" class="mt-3 p-2 bg-red-50 text-red-700 rounded-lg text-sm" x-text="tableError"></div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showEditTable = false"
                            style="padding:8px 16px;background:#f3f4f6;color:#374151;font-size:13px;border-radius:8px;border:none;cursor:pointer;">
                        Batal
                    </button>
                    <button @click="updateTable()" :disabled="tableLoading"
                            style="padding:8px 16px;background:#6366f1;color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;opacity:1;"
                            :style="tableLoading && 'opacity:0.5'">
                        <span x-text="tableLoading ? 'Menyimpan...' : 'Update'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function tableManager() {
            return {
                showAddFloor: false,
                showAddTable: false,
                showEditTable: false,
                floorLoading: false,
                tableLoading: false,
                tableError: '',
                newFloor: { name: '', description: '' },
                newTable: { floor_id: '', number: '', name: '', capacity: 4 },
                editingTable: { id: '', number: '', name: '', capacity: 4, is_active: true },

                headers() {
                    return {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    };
                },

                async createFloor() {
                    this.floorLoading = true;
                    try {
                        const res = await fetch('{{ route("tables.floors.store") }}', {
                            method: 'POST',
                            headers: this.headers(),
                            body: JSON.stringify(this.newFloor)
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert('Gagal menambah floor');
                    }
                    this.floorLoading = false;
                },

                async deleteFloor(id) {
                    if (!confirm('Hapus floor ini?')) return;
                    try {
                        const res = await fetch(`/tables/floors/${id}`, {
                            method: 'DELETE',
                            headers: this.headers()
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert('Gagal menghapus floor');
                    }
                },

                async createTable() {
                    this.tableLoading = true;
                    this.tableError = '';
                    try {
                        const res = await fetch('{{ route("tables.store") }}', {
                            method: 'POST',
                            headers: this.headers(),
                            body: JSON.stringify(this.newTable)
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            this.tableError = data.message;
                        }
                    } catch (e) {
                        this.tableError = 'Gagal menambah meja';
                    }
                    this.tableLoading = false;
                },

                editTable(table) {
                    this.editingTable = {
                        id: table.id,
                        number: table.number,
                        name: table.name || '',
                        capacity: table.capacity,
                        is_active: table.is_active
                    };
                    this.tableError = '';
                    this.showEditTable = true;
                },

                async updateTable() {
                    this.tableLoading = true;
                    this.tableError = '';
                    try {
                        const res = await fetch(`/tables/${this.editingTable.id}`, {
                            method: 'PUT',
                            headers: this.headers(),
                            body: JSON.stringify(this.editingTable)
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            this.tableError = data.message;
                        }
                    } catch (e) {
                        this.tableError = 'Gagal update meja';
                    }
                    this.tableLoading = false;
                },

                async deleteTable(id) {
                    if (!confirm('Hapus meja ini?')) return;
                    try {
                        const res = await fetch(`/tables/${id}`, {
                            method: 'DELETE',
                            headers: this.headers()
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        alert('Gagal menghapus meja');
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
