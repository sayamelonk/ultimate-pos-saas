<?php

namespace App\Console\Commands;

use App\Models\HeldOrder;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetFloorAndKitchen extends Command
{
    protected $signature = 'pos:reset-floor
                            {--outlet= : Reset only for specific outlet ID}
                            {--tenant= : Reset only for specific tenant ID}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Reset semua meja ke available, clear kitchen orders, dan hapus held orders. Products, transactions, dan users tetap aman.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Ini akan mereset semua meja ke available, clear kitchen orders aktif, dan hapus held orders. Lanjutkan?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $outletId = $this->option('outlet');
        $tenantId = $this->option('tenant');

        DB::beginTransaction();

        try {
            // 1. Close active table sessions
            $sessionsClosed = $this->closeActiveSessions($outletId, $tenantId);

            // 2. Reset all tables to available
            $tablesReset = $this->resetTables($outletId, $tenantId);

            // 3. Mark active kitchen orders as served
            $kitchenCleared = $this->clearKitchenOrders($outletId, $tenantId);

            // 4. Delete held orders
            $heldDeleted = $this->deleteHeldOrders($outletId, $tenantId);

            DB::commit();

            $this->newLine();
            $this->info('Reset selesai:');
            $this->table(
                ['Action', 'Count'],
                [
                    ['Table sessions ditutup', $sessionsClosed],
                    ['Meja direset ke available', $tablesReset],
                    ['Kitchen orders di-clear', $kitchenCleared],
                    ['Held orders dihapus', $heldDeleted],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal reset: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function closeActiveSessions(?string $outletId, ?string $tenantId): int
    {
        $query = TableSession::where('status', TableSession::STATUS_ACTIVE);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($outletId) {
            $query->whereHas('table', fn ($q) => $q->where('outlet_id', $outletId));
        }

        $count = $query->count();

        $query->update([
            'status' => TableSession::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $this->comment("Closed {$count} active table sessions.");

        return $count;
    }

    private function resetTables(?string $outletId, ?string $tenantId): int
    {
        $query = Table::where('status', '!=', Table::STATUS_AVAILABLE);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $count = $query->count();

        $query->update(['status' => Table::STATUS_AVAILABLE]);

        $this->comment("Reset {$count} tables to available.");

        return $count;
    }

    private function clearKitchenOrders(?string $outletId, ?string $tenantId): int
    {
        $query = KitchenOrder::active();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $orderIds = $query->pluck('id');
        $count = $orderIds->count();

        if ($count > 0) {
            // Mark all active kitchen order items as ready
            KitchenOrderItem::whereIn('kitchen_order_id', $orderIds)
                ->whereIn('status', [
                    KitchenOrderItem::STATUS_PENDING,
                    KitchenOrderItem::STATUS_PREPARING,
                ])
                ->update([
                    'status' => KitchenOrderItem::STATUS_READY,
                    'completed_at' => now(),
                ]);

            // Mark all active kitchen orders as served
            KitchenOrder::whereIn('id', $orderIds)->update([
                'status' => KitchenOrder::STATUS_SERVED,
                'completed_at' => DB::raw('COALESCE(completed_at, NOW())'),
                'served_at' => now(),
            ]);
        }

        $this->comment("Cleared {$count} active kitchen orders.");

        return $count;
    }

    private function deleteHeldOrders(?string $outletId, ?string $tenantId): int
    {
        $query = HeldOrder::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $count = $query->count();
        $query->delete();

        $this->comment("Deleted {$count} held orders.");

        return $count;
    }
}
