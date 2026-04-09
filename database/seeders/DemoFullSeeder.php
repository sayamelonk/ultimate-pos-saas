<?php

namespace Database\Seeders;

use App\Models\CashDrawerLog;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\HeldOrder;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\QrOrder;
use App\Models\QrOrderItem;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionDiscount;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoFullSeeder extends Seeder
{
    private Tenant $tenant;

    private Outlet $outlet;

    private User $cashier;

    private User $manager;

    private User $waiter;

    /** @var Collection<int, Product> */
    private $products;

    /** @var Collection<int, PaymentMethod> */
    private $paymentMethods;

    /** @var Collection<int, Customer> */
    private $customers;

    /** @var Collection<int, Discount> */
    private $discounts;

    /** @var Collection<int, KitchenStation> */
    private $stations;

    /** @var Collection<int, Table> */
    private $tables;

    private PaymentMethod $cashMethod;

    public function run(): void
    {
        $this->tenant = Tenant::where('code', 'DEMO001')->first();
        if (! $this->tenant) {
            $this->command->error('Demo tenant (DEMO001) not found. Run TenantSeeder first.');

            return;
        }

        $this->outlet = Outlet::where('tenant_id', $this->tenant->id)->where('code', 'MAIN')->first();
        $this->cashier = User::where('tenant_id', $this->tenant->id)->where('email', 'cashier@demo.com')->first();
        $this->manager = User::where('tenant_id', $this->tenant->id)->where('email', 'manager@demo.com')->first();
        $this->waiter = User::where('tenant_id', $this->tenant->id)->where('email', 'waiter@demo.com')->first();

        if (! $this->outlet || ! $this->cashier || ! $this->manager) {
            $this->command->error('Required demo users or outlet not found.');

            return;
        }

        $this->products = Product::where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->with(['variants'])
            ->get();

        $this->paymentMethods = PaymentMethod::where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->get();

        $this->cashMethod = $this->paymentMethods->where('code', 'CASH')->first();
        $this->customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $this->discounts = Discount::where('tenant_id', $this->tenant->id)->where('is_active', true)->get();
        $this->stations = KitchenStation::where('tenant_id', $this->tenant->id)->get();
        $this->tables = Table::where('tenant_id', $this->tenant->id)->where('is_active', true)->get();

        if ($this->products->isEmpty()) {
            $this->command->error('No products found. Run ProductSeeder first.');

            return;
        }

        // Phase 1: Yesterday's closed session with completed transactions
        $this->seedYesterdaySession();

        // Phase 2: Today's active session with ongoing transactions
        $this->seedTodaySession();

        // Phase 3: Active table sessions (dine-in)
        $this->seedActiveTableSessions();

        // Phase 4: Kitchen orders in various statuses
        $this->seedKitchenOrders();

        // Phase 5: Held orders
        $this->seedHeldOrders();

        // Phase 6: QR orders
        $this->seedQrOrders();

        $this->command->info('Demo full data seeded successfully!');
    }

    private function seedYesterdaySession(): void
    {
        $yesterday = Carbon::yesterday()->setTimezone('Asia/Jakarta');
        $openedAt = $yesterday->copy()->setTime(8, 0, 0);
        $closedAt = $yesterday->copy()->setTime(21, 30, 0);

        // Create closed POS session
        $session = PosSession::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->cashier->id,
            'session_number' => 'SES-'.$yesterday->format('Ymd').'-001',
            'opening_cash' => 500000,
            'closing_cash' => 2850000,
            'expected_cash' => 2800000,
            'cash_difference' => 50000,
            'opening_notes' => 'Opening shift pagi',
            'closing_notes' => 'Closing shift, selisih +50.000 (tips)',
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'closed_by' => $this->manager->id,
            'status' => PosSession::STATUS_CLOSED,
        ]);

        // Opening cash drawer log
        CashDrawerLog::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->cashier->id,
            'type' => CashDrawerLog::TYPE_OPENING,
            'amount' => 500000,
            'balance_before' => 0,
            'balance_after' => 500000,
            'reference' => $session->session_number,
            'reason' => 'Opening cash',
        ]);

        // Create 15 completed transactions for yesterday
        $runningBalance = 500000;
        $orderScenarios = $this->getYesterdayScenarios($yesterday);

        foreach ($orderScenarios as $i => $scenario) {
            $txTime = $openedAt->copy()->addMinutes($i * 50 + rand(5, 45));
            $txn = $this->createTransaction($session, $scenario, $txTime, $i + 1, $yesterday);

            // Cash drawer log for cash transactions
            if ($txn && $scenario['payment'] === 'CASH') {
                $runningBalance += $txn->grand_total;
                CashDrawerLog::create([
                    'tenant_id' => $this->tenant->id,
                    'outlet_id' => $this->outlet->id,
                    'pos_session_id' => $session->id,
                    'user_id' => $this->cashier->id,
                    'transaction_id' => $txn->id,
                    'type' => CashDrawerLog::TYPE_SALE,
                    'amount' => $txn->grand_total,
                    'balance_before' => $runningBalance - $txn->grand_total,
                    'balance_after' => $runningBalance,
                    'reference' => $txn->transaction_number,
                    'reason' => 'Cash sale',
                ]);
            }
        }

        // Closing cash drawer log
        CashDrawerLog::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->manager->id,
            'type' => CashDrawerLog::TYPE_CLOSING,
            'amount' => 2850000,
            'balance_before' => $runningBalance,
            'balance_after' => 0,
            'reference' => $session->session_number,
            'reason' => 'Closing shift',
        ]);

        $this->command->info("Yesterday's session: {$session->session_number} with ".count($orderScenarios).' transactions.');
    }

    private function seedTodaySession(): void
    {
        $today = Carbon::today()->setTimezone('Asia/Jakarta');
        $openedAt = $today->copy()->setTime(8, 0, 0);

        // Create open POS session for today
        $session = PosSession::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->cashier->id,
            'session_number' => 'SES-'.$today->format('Ymd').'-001',
            'opening_cash' => 500000,
            'opening_notes' => 'Opening shift pagi hari ini',
            'opened_at' => $openedAt,
            'status' => PosSession::STATUS_OPEN,
        ]);

        CashDrawerLog::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->cashier->id,
            'type' => CashDrawerLog::TYPE_OPENING,
            'amount' => 500000,
            'balance_before' => 0,
            'balance_after' => 500000,
            'reference' => $session->session_number,
            'reason' => 'Opening cash',
        ]);

        // Create 8 completed transactions for today so far
        $runningBalance = 500000;
        $todayScenarios = $this->getTodayScenarios($today);

        foreach ($todayScenarios as $i => $scenario) {
            $txTime = $openedAt->copy()->addMinutes($i * 30 + rand(5, 25));
            $txn = $this->createTransaction($session, $scenario, $txTime, $i + 1, $today);

            if ($txn && $scenario['payment'] === 'CASH') {
                $runningBalance += $txn->grand_total;
                CashDrawerLog::create([
                    'tenant_id' => $this->tenant->id,
                    'outlet_id' => $this->outlet->id,
                    'pos_session_id' => $session->id,
                    'user_id' => $this->cashier->id,
                    'transaction_id' => $txn->id,
                    'type' => CashDrawerLog::TYPE_SALE,
                    'amount' => $txn->grand_total,
                    'balance_before' => $runningBalance - $txn->grand_total,
                    'balance_after' => $runningBalance,
                    'reference' => $txn->transaction_number,
                    'reason' => 'Cash sale',
                ]);
            }
        }

        // Cash in event (petty cash from manager)
        CashDrawerLog::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->manager->id,
            'type' => CashDrawerLog::TYPE_CASH_IN,
            'amount' => 200000,
            'balance_before' => $runningBalance,
            'balance_after' => $runningBalance + 200000,
            'reference' => 'CASHIN-001',
            'reason' => 'Tambahan uang kecil dari manager',
        ]);

        $this->command->info("Today's session: {$session->session_number} (OPEN) with ".count($todayScenarios).' transactions.');
    }

    private function seedActiveTableSessions(): void
    {
        if ($this->tables->isEmpty()) {
            $this->command->warn('No tables found. Skipping table sessions.');

            return;
        }

        // Open 4 tables with active sessions
        $tablesToOpen = $this->tables->where('status', 'available')->take(4);
        $waiters = User::where('tenant_id', $this->tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'waiter'))
            ->get();

        $waiterUser = $waiters->isNotEmpty() ? $waiters->first() : $this->waiter;

        $tableScenarios = [
            ['guest_count' => 2, 'notes' => 'Couple reguler'],
            ['guest_count' => 4, 'notes' => 'Keluarga dengan anak'],
            ['guest_count' => 6, 'notes' => 'Group meeting kantor'],
            ['guest_count' => 1, 'notes' => 'Tamu VIP'],
        ];

        $count = 0;
        foreach ($tablesToOpen as $table) {
            if ($count >= count($tableScenarios)) {
                break;
            }

            $scenario = $tableScenarios[$count];

            $tableSession = TableSession::create([
                'tenant_id' => $this->tenant->id,
                'table_id' => $table->id,
                'opened_by' => $waiterUser->id,
                'opened_at' => now()->subMinutes(rand(15, 90)),
                'guest_count' => $scenario['guest_count'],
                'notes' => $scenario['notes'],
                'status' => TableSession::STATUS_ACTIVE,
            ]);

            $table->update(['status' => 'occupied']);

            $count++;
        }

        // Mark 2 tables as reserved
        $reservableTables = $this->tables->where('status', 'available')->take(2);
        foreach ($reservableTables as $table) {
            $table->update(['status' => 'reserved']);
        }

        // Mark 1 table as dirty
        $dirtyTable = $this->tables->where('status', 'available')->first();
        if ($dirtyTable) {
            $dirtyTable->update(['status' => 'dirty']);
        }

        $this->command->info("Table sessions: {$count} active, 2 reserved, 1 dirty.");
    }

    private function seedKitchenOrders(): void
    {
        if ($this->stations->isEmpty()) {
            $this->command->warn('No kitchen stations found. Skipping kitchen orders.');

            return;
        }

        $todaySession = PosSession::where('tenant_id', $this->tenant->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        $todayTransactions = Transaction::where('tenant_id', $this->tenant->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('completed_at', '>=', Carbon::today())
            ->with('items')
            ->get();

        if ($todayTransactions->isEmpty()) {
            $this->command->warn('No today transactions found for kitchen orders.');

            return;
        }

        $beverageStation = $this->stations->where('code', 'BEV')->first() ?? $this->stations->first();
        $mainStation = $this->stations->where('code', 'MAIN')->first() ?? $this->stations->first();
        $coldStation = $this->stations->where('code', 'COLD')->first() ?? $this->stations->first();
        $grillStation = $this->stations->where('code', 'GRILL')->first() ?? $this->stations->first();

        // Map product categories to stations
        $categoryStationMap = [
            'HOT-COF' => $beverageStation,
            'ICE-COF' => $beverageStation,
            'ESP' => $beverageStation,
            'TEA' => $beverageStation,
            'CHOCO' => $beverageStation,
            'SMOOTH' => $beverageStation,
            'PASTRY' => $mainStation,
            'CAKE' => $coldStation,
            'SAND' => $grillStation,
        ];

        $kitchenStatuses = [
            KitchenOrder::STATUS_PENDING,
            KitchenOrder::STATUS_PENDING,
            KitchenOrder::STATUS_PREPARING,
            KitchenOrder::STATUS_PREPARING,
            KitchenOrder::STATUS_READY,
            KitchenOrder::STATUS_READY,
            KitchenOrder::STATUS_SERVED,
        ];

        $count = 0;
        foreach ($todayTransactions as $tx) {
            if ($tx->items->isEmpty()) {
                continue;
            }

            $status = $kitchenStatuses[$count % count($kitchenStatuses)];

            // Determine station from first item
            $firstItem = $tx->items->first();
            $product = $firstItem ? Product::find($firstItem->product_id) : null;
            $categoryCode = $product?->category?->code ?? 'HOT-COF';
            $station = $categoryStationMap[$categoryCode] ?? $mainStation;

            $kitchenOrder = KitchenOrder::create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'transaction_id' => $tx->id,
                'station_id' => $station->id,
                'order_number' => $tx->transaction_number,
                'order_type' => $tx->order_type ?? 'dine_in',
                'table_name' => $tx->order_type === 'dine_in' ? 'Table '.rand(1, 12) : null,
                'customer_name' => $tx->customer_name ?? 'Guest',
                'status' => $status,
                'priority' => $count === 0 ? KitchenOrder::PRIORITY_VIP : ($count === 1 ? KitchenOrder::PRIORITY_RUSH : KitchenOrder::PRIORITY_NORMAL),
                'started_at' => in_array($status, [KitchenOrder::STATUS_PREPARING, KitchenOrder::STATUS_READY, KitchenOrder::STATUS_SERVED])
                    ? now()->subMinutes(rand(3, 15)) : null,
                'completed_at' => in_array($status, [KitchenOrder::STATUS_READY, KitchenOrder::STATUS_SERVED])
                    ? now()->subMinutes(rand(1, 5)) : null,
                'served_at' => $status === KitchenOrder::STATUS_SERVED ? now()->subMinutes(rand(0, 3)) : null,
            ]);

            $itemStatus = match ($status) {
                KitchenOrder::STATUS_READY, KitchenOrder::STATUS_SERVED => KitchenOrderItem::STATUS_READY,
                KitchenOrder::STATUS_PREPARING => KitchenOrderItem::STATUS_PREPARING,
                default => KitchenOrderItem::STATUS_PENDING,
            };

            foreach ($tx->items as $item) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kitchenOrder->id,
                    'transaction_item_id' => $item->id,
                    'station_id' => $station->id,
                    'item_name' => $item->item_name ?? 'Item',
                    'quantity' => $item->quantity,
                    'modifiers' => $item->modifiers ?? [],
                    'notes' => $item->notes,
                    'status' => $itemStatus,
                    'started_at' => $itemStatus !== KitchenOrderItem::STATUS_PENDING ? now()->subMinutes(rand(3, 10)) : null,
                    'completed_at' => $itemStatus === KitchenOrderItem::STATUS_READY ? now()->subMinutes(rand(0, 3)) : null,
                ]);
            }

            $count++;
        }

        // Create 3 additional standalone kitchen orders (from waiter app, no transaction yet)
        $waiterOrders = [
            [
                'customer_name' => 'Pak Joko',
                'table_name' => 'Table 5',
                'order_type' => 'dine_in',
                'status' => KitchenOrder::STATUS_PENDING,
                'priority' => KitchenOrder::PRIORITY_VIP,
                'items' => ['Americano', 'Cappuccino', 'Club Sandwich'],
            ],
            [
                'customer_name' => 'Bu Sari',
                'table_name' => 'Table 8',
                'order_type' => 'dine_in',
                'status' => KitchenOrder::STATUS_PREPARING,
                'priority' => KitchenOrder::PRIORITY_RUSH,
                'items' => ['Iced Latte', 'Cheesecake', 'Tiramisu'],
            ],
            [
                'customer_name' => 'GrabFood #GF123',
                'table_name' => null,
                'order_type' => 'takeaway',
                'status' => KitchenOrder::STATUS_PENDING,
                'priority' => KitchenOrder::PRIORITY_NORMAL,
                'items' => ['Cold Brew', 'Croissant', 'Chocolate Brownie'],
            ],
        ];

        foreach ($waiterOrders as $wo) {
            $orderNumber = 'KDS-'.now()->format('Ymd').'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            $kitchenOrder = KitchenOrder::create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'transaction_id' => null,
                'station_id' => $beverageStation->id,
                'order_number' => $orderNumber,
                'order_type' => $wo['order_type'],
                'table_name' => $wo['table_name'],
                'customer_name' => $wo['customer_name'],
                'status' => $wo['status'],
                'priority' => $wo['priority'],
                'started_at' => $wo['status'] === KitchenOrder::STATUS_PREPARING ? now()->subMinutes(5) : null,
            ]);

            foreach ($wo['items'] as $itemName) {
                $product = $this->products->firstWhere('name', $itemName);
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kitchenOrder->id,
                    'station_id' => $beverageStation->id,
                    'item_name' => $itemName,
                    'quantity' => rand(1, 2),
                    'modifiers' => [],
                    'notes' => null,
                    'status' => $wo['status'] === KitchenOrder::STATUS_PREPARING
                        ? KitchenOrderItem::STATUS_PREPARING
                        : KitchenOrderItem::STATUS_PENDING,
                    'started_at' => $wo['status'] === KitchenOrder::STATUS_PREPARING ? now()->subMinutes(5) : null,
                ]);
            }

            $count++;
        }

        $this->command->info("Kitchen orders: {$count} total (various statuses).");
    }

    private function seedHeldOrders(): void
    {
        $todaySession = PosSession::where('tenant_id', $this->tenant->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();

        if (! $todaySession) {
            return;
        }

        $heldOrders = [
            [
                'reference' => 'Meja Luar #3',
                'table_number' => '3',
                'items' => [
                    ['name' => 'Iced Americano', 'qty' => 2, 'price' => 30000],
                    ['name' => 'Cold Brew', 'qty' => 1, 'price' => 38000],
                    ['name' => 'Croissant', 'qty' => 2, 'price' => 22000],
                ],
                'notes' => 'Tunggu teman datang',
            ],
            [
                'reference' => 'Pak Ahmad',
                'table_number' => null,
                'items' => [
                    ['name' => 'Cappuccino', 'qty' => 1, 'price' => 32000],
                    ['name' => 'Club Sandwich', 'qty' => 1, 'price' => 55000],
                ],
                'notes' => 'Takeaway - ambil 15 menit lagi',
            ],
            [
                'reference' => 'Meeting Room VIP',
                'table_number' => '10',
                'items' => [
                    ['name' => 'Earl Grey', 'qty' => 3, 'price' => 25000],
                    ['name' => 'Green Tea Latte', 'qty' => 2, 'price' => 35000],
                    ['name' => 'Cheesecake', 'qty' => 2, 'price' => 45000],
                    ['name' => 'Tiramisu', 'qty' => 1, 'price' => 48000],
                ],
                'notes' => 'Meeting selesai jam 3 sore, billing nanti',
            ],
        ];

        foreach ($heldOrders as $i => $ho) {
            $items = collect($ho['items'])->map(function ($item) {
                $product = $this->products->firstWhere('name', $item['name']);

                return [
                    'product_id' => $product?->id,
                    'product_variant_id' => null,
                    'item_name' => $item['name'],
                    'item_sku' => $product?->sku ?? 'UNKNOWN',
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'base_price' => $item['price'],
                    'variant_price_adjustment' => 0,
                    'modifiers_total' => 0,
                    'modifiers' => [],
                    'subtotal' => $item['qty'] * $item['price'],
                    'notes' => null,
                ];
            })->toArray();

            $subtotal = collect($items)->sum('subtotal');
            $taxAmount = round($subtotal * 0.11);
            $serviceCharge = round($subtotal * 0.05);
            $grandTotal = $subtotal + $taxAmount + $serviceCharge;

            $holdNumber = HeldOrder::generateHoldNumber($this->outlet->id);

            HeldOrder::create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'pos_session_id' => $todaySession->id,
                'user_id' => $this->cashier->id,
                'customer_id' => $this->customers->random()?->id,
                'hold_number' => $holdNumber,
                'reference' => $ho['reference'],
                'table_number' => $ho['table_number'],
                'items' => $items,
                'discounts' => [],
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'service_charge_amount' => $serviceCharge,
                'grand_total' => $grandTotal,
                'notes' => $ho['notes'],
                'expires_at' => now()->addHours(4),
            ]);
        }

        $this->command->info('Held orders: '.count($heldOrders).' created.');
    }

    private function seedQrOrders(): void
    {
        $tablesWithQr = $this->tables->whereNotNull('qr_token')->take(3);

        if ($tablesWithQr->isEmpty()) {
            // Generate QR tokens for some tables
            $tablesWithQr = $this->tables->take(3);
            foreach ($tablesWithQr as $table) {
                $table->update([
                    'qr_token' => Str::random(32),
                    'qr_generated_at' => now(),
                ]);
            }
        }

        $qrScenarios = [
            [
                'customer_name' => 'Rina',
                'customer_phone' => '081234567001',
                'status' => QrOrder::STATUS_PENDING,
                'payment_method' => QrOrder::PAYMENT_PAY_AT_COUNTER,
                'items' => [
                    ['name' => 'Iced Latte', 'qty' => 2, 'price' => 35000],
                    ['name' => 'Cheesecake', 'qty' => 1, 'price' => 45000],
                ],
                'notes' => 'Less sugar please',
            ],
            [
                'customer_name' => 'Dedi',
                'customer_phone' => '081234567002',
                'status' => QrOrder::STATUS_PROCESSING,
                'payment_method' => QrOrder::PAYMENT_PAY_AT_COUNTER,
                'items' => [
                    ['name' => 'Cappuccino', 'qty' => 1, 'price' => 32000],
                    ['name' => 'Club Sandwich', 'qty' => 1, 'price' => 55000],
                    ['name' => 'Cinnamon Roll', 'qty' => 1, 'price' => 25000],
                ],
                'notes' => null,
            ],
            [
                'customer_name' => 'Mega',
                'customer_phone' => '081234567003',
                'status' => QrOrder::STATUS_COMPLETED,
                'payment_method' => QrOrder::PAYMENT_PAY_AT_COUNTER,
                'items' => [
                    ['name' => 'Green Tea Latte', 'qty' => 1, 'price' => 35000],
                    ['name' => 'Pain au Chocolat', 'qty' => 2, 'price' => 28000],
                ],
                'notes' => 'Extra hot',
            ],
        ];

        $count = 0;
        foreach ($tablesWithQr as $table) {
            if ($count >= count($qrScenarios)) {
                break;
            }

            $scenario = $qrScenarios[$count];
            $items = collect($scenario['items']);
            $subtotal = $items->sum(fn ($item) => $item['qty'] * $item['price']);
            $taxAmount = round($subtotal * 0.11);
            $serviceCharge = round($subtotal * 0.05);
            $grandTotal = $subtotal + $taxAmount + $serviceCharge;

            $orderNumber = QrOrder::generateOrderNumber($this->outlet->id);

            $qrOrder = QrOrder::create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'table_id' => $table->id,
                'order_number' => $orderNumber,
                'customer_name' => $scenario['customer_name'],
                'customer_phone' => $scenario['customer_phone'],
                'notes' => $scenario['notes'],
                'status' => $scenario['status'],
                'payment_method' => $scenario['payment_method'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'service_charge_amount' => $serviceCharge,
                'grand_total' => $grandTotal,
                'tax_mode' => 'exclusive',
                'tax_percentage' => 11,
                'service_charge_percentage' => 5,
            ]);

            foreach ($scenario['items'] as $item) {
                $product = $this->products->firstWhere('name', $item['name']);

                QrOrderItem::create([
                    'qr_order_id' => $qrOrder->id,
                    'product_id' => $product?->id,
                    'item_name' => $item['name'],
                    'item_sku' => $product?->sku ?? 'QR-ITEM',
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'modifiers_total' => 0,
                    'subtotal' => $item['qty'] * $item['price'],
                    'modifiers' => [],
                    'item_notes' => null,
                ]);
            }

            $count++;
        }

        $this->command->info("QR orders: {$count} created.");
    }

    private function createTransaction(
        PosSession $session,
        array $scenario,
        Carbon $txTime,
        int $sequence,
        Carbon $date
    ): ?Transaction {
        $outletCode = $this->outlet->code;
        $txNumber = "{$outletCode}-{$date->format('Ymd')}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);

        $items = collect($scenario['products'])->map(function ($p) {
            $product = $this->products->firstWhere('name', $p['name']);
            $variant = null;
            $variantAdjustment = 0;

            if ($product && $product->variants->isNotEmpty() && isset($p['variant'])) {
                $variant = $product->variants->firstWhere('name', $p['variant']);
                $variantAdjustment = $variant?->price_adjustment ?? 0;
            }

            $unitPrice = ($product?->base_price ?? $p['price']) + $variantAdjustment;

            return [
                'product_id' => $product?->id,
                'product_variant_id' => $variant?->id,
                'inventory_item_id' => $product?->inventory_item_id,
                'item_name' => $p['name'].($variant ? " ({$variant->name})" : ''),
                'item_sku' => $product?->sku ?? 'UNKNOWN',
                'quantity' => $p['qty'],
                'unit_name' => 'pcs',
                'unit_price' => $unitPrice,
                'base_price' => $product?->base_price ?? $p['price'],
                'variant_price_adjustment' => $variantAdjustment,
                'modifiers_total' => 0,
                'cost_price' => $product?->cost_price ?? round($unitPrice * 0.3),
                'discount_amount' => 0,
                'subtotal' => $p['qty'] * $unitPrice,
                'modifiers' => [],
                'notes' => $p['notes'] ?? null,
            ];
        });

        $subtotal = $items->sum('subtotal');

        // Apply discount if specified
        $discountAmount = 0;
        $discountRecord = null;
        if (isset($scenario['discount'])) {
            $discountRecord = $this->discounts->firstWhere('code', $scenario['discount']);
            if ($discountRecord) {
                if ($discountRecord->type === 'percentage') {
                    $discountAmount = round($subtotal * $discountRecord->value / 100);
                    if ($discountRecord->max_discount && $discountAmount > $discountRecord->max_discount) {
                        $discountAmount = $discountRecord->max_discount;
                    }
                } else {
                    $discountAmount = $discountRecord->value;
                }
            }
        }

        $afterDiscount = $subtotal - $discountAmount;
        $taxPercentage = $this->tenant->tax_percentage ?? 11;
        $serviceChargePercentage = $this->tenant->service_charge_percentage ?? 5;
        $taxAmount = round($afterDiscount * $taxPercentage / 100);
        $serviceCharge = round($afterDiscount * $serviceChargePercentage / 100);
        $grandTotal = $afterDiscount + $taxAmount + $serviceCharge;

        // Rounding
        $rounding = (ceil($grandTotal / 100) * 100) - $grandTotal;
        $grandTotal += $rounding;

        $customer = isset($scenario['customer'])
            ? $this->customers->firstWhere('code', $scenario['customer'])
            : null;

        $waiterId = ($scenario['order_type'] ?? 'dine_in') === 'dine_in' && $this->waiter
            ? $this->waiter->id
            : null;

        $transaction = Transaction::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'table_id' => null,
            'order_type' => $scenario['order_type'] ?? 'dine_in',
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?? ($scenario['customer_name'] ?? 'Walk-in'),
            'user_id' => $this->cashier->id,
            'waiter_id' => $waiterId,
            'transaction_number' => $txNumber,
            'type' => Transaction::TYPE_SALE,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceCharge,
            'rounding' => $rounding,
            'grand_total' => $grandTotal,
            'payment_amount' => $grandTotal,
            'change_amount' => 0,
            'tax_percentage' => $taxPercentage,
            'tax_mode' => 'exclusive',
            'service_charge_percentage' => $serviceChargePercentage,
            'points_earned' => floor($grandTotal / 10000),
            'points_redeemed' => 0,
            'notes' => $scenario['notes'] ?? null,
            'status' => Transaction::STATUS_COMPLETED,
            'completed_at' => $txTime,
            'created_at' => $txTime,
            'updated_at' => $txTime,
        ]);

        // Create transaction items
        foreach ($items as $item) {
            TransactionItem::create(array_merge($item, [
                'transaction_id' => $transaction->id,
            ]));
        }

        // Create transaction payment
        $paymentCode = $scenario['payment'] ?? 'CASH';
        $paymentMethod = $this->paymentMethods->firstWhere('code', $paymentCode) ?? $this->cashMethod;
        $chargeAmount = 0;
        if ($paymentMethod->charge_percentage > 0) {
            $chargeAmount = round($grandTotal * $paymentMethod->charge_percentage / 100);
        }

        $paymentAmount = $grandTotal;
        $changeAmount = 0;
        if ($paymentCode === 'CASH') {
            // Round up to nearest 10k for cash
            $paymentAmount = ceil($grandTotal / 10000) * 10000;
            $changeAmount = $paymentAmount - $grandTotal;
        }

        $transaction->update([
            'payment_amount' => $paymentAmount,
            'change_amount' => $changeAmount,
        ]);

        TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => $paymentAmount,
            'charge_amount' => $chargeAmount,
            'reference_number' => $paymentCode !== 'CASH' ? strtoupper(Str::random(12)) : null,
            'approval_code' => $paymentCode !== 'CASH' ? rand(100000, 999999) : null,
        ]);

        // Create transaction discount if applicable
        if ($discountRecord && $discountAmount > 0) {
            TransactionDiscount::create([
                'transaction_id' => $transaction->id,
                'discount_id' => $discountRecord->id,
                'discount_name' => $discountRecord->name,
                'type' => $discountRecord->type,
                'value' => $discountRecord->value,
                'amount' => $discountAmount,
            ]);
        }

        return $transaction;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getYesterdayScenarios(Carbon $date): array
    {
        return [
            // Morning rush - coffee lovers
            [
                'products' => [
                    ['name' => 'Americano', 'qty' => 2, 'price' => 28000],
                    ['name' => 'Croissant', 'qty' => 2, 'price' => 22000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer' => 'CUST001',
                'customer_name' => 'John Doe',
            ],
            [
                'products' => [
                    ['name' => 'Cappuccino', 'qty' => 1, 'price' => 32000],
                    ['name' => 'Pain au Chocolat', 'qty' => 1, 'price' => 28000],
                ],
                'payment' => 'GOPAY',
                'order_type' => 'takeaway',
                'customer_name' => 'Grab #G001',
            ],
            [
                'products' => [
                    ['name' => 'Cafe Latte', 'qty' => 3, 'price' => 32000],
                    ['name' => 'Cinnamon Roll', 'qty' => 3, 'price' => 25000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer_name' => 'Meeting Room A',
                'discount' => 'WELCOME10',
            ],

            // Late morning
            [
                'products' => [
                    ['name' => 'Iced Americano', 'qty' => 1, 'price' => 30000],
                    ['name' => 'Club Sandwich', 'qty' => 1, 'price' => 55000],
                ],
                'payment' => 'DEBIT',
                'order_type' => 'dine_in',
                'customer' => 'CUST002',
                'customer_name' => 'Jane Smith',
            ],
            [
                'products' => [
                    ['name' => 'Espresso', 'qty' => 2, 'price' => 18000],
                ],
                'payment' => 'CASH',
                'order_type' => 'takeaway',
                'customer_name' => 'Walk-in',
            ],

            // Lunch peak
            [
                'products' => [
                    ['name' => 'Club Sandwich', 'qty' => 2, 'price' => 55000],
                    ['name' => 'Grilled Cheese', 'qty' => 1, 'price' => 35000],
                    ['name' => 'Iced Latte', 'qty' => 3, 'price' => 35000],
                ],
                'payment' => 'QRIS',
                'order_type' => 'dine_in',
                'customer_name' => 'Group Lunch',
                'discount' => 'FLAT25K',
            ],
            [
                'products' => [
                    ['name' => 'Tuna Melt', 'qty' => 1, 'price' => 48000],
                    ['name' => 'Cold Brew', 'qty' => 1, 'price' => 38000],
                ],
                'payment' => 'OVO',
                'order_type' => 'takeaway',
                'customer_name' => 'GoFood #GF002',
            ],
            [
                'products' => [
                    ['name' => 'Macchiato', 'qty' => 1, 'price' => 25000],
                    ['name' => 'Cheesecake', 'qty' => 1, 'price' => 45000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer' => 'CUST003',
                'customer_name' => 'Ahmad Yusuf',
            ],

            // Afternoon
            [
                'products' => [
                    ['name' => 'Earl Grey', 'qty' => 2, 'price' => 25000],
                    ['name' => 'Green Tea Latte', 'qty' => 1, 'price' => 35000],
                    ['name' => 'Tiramisu', 'qty' => 2, 'price' => 48000],
                ],
                'payment' => 'CREDIT',
                'order_type' => 'dine_in',
                'customer' => 'CUST004',
                'customer_name' => 'Siti Rahayu',
                'discount' => 'VIP20',
            ],
            [
                'products' => [
                    ['name' => 'Berry Blast Smoothie', 'qty' => 2, 'price' => 38000],
                    ['name' => 'Mango Smoothie', 'qty' => 1, 'price' => 35000],
                ],
                'payment' => 'DANA',
                'order_type' => 'takeaway',
                'customer_name' => 'Walk-in',
            ],
            [
                'products' => [
                    ['name' => 'Hot Chocolate', 'qty' => 1, 'price' => 30000],
                    ['name' => 'Chocolate Brownie', 'qty' => 2, 'price' => 28000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer_name' => 'Walk-in',
            ],

            // Evening
            [
                'products' => [
                    ['name' => 'Iced Chocolate', 'qty' => 2, 'price' => 32000],
                    ['name' => 'Chai Latte', 'qty' => 1, 'price' => 32000],
                    ['name' => 'Club Sandwich', 'qty' => 2, 'price' => 55000],
                    ['name' => 'Cheesecake', 'qty' => 1, 'price' => 45000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer_name' => 'Family Dinner',
                'discount' => 'WEEKEND10',
            ],
            [
                'products' => [
                    ['name' => 'Cappuccino', 'qty' => 2, 'price' => 32000],
                    ['name' => 'Grilled Cheese', 'qty' => 2, 'price' => 35000],
                ],
                'payment' => 'SHOPEEPAY',
                'order_type' => 'dine_in',
                'customer' => 'CUST005',
                'customer_name' => 'Budi Santoso',
            ],
            [
                'products' => [
                    ['name' => 'Iced Americano', 'qty' => 1, 'price' => 30000],
                    ['name' => 'Tuna Melt', 'qty' => 1, 'price' => 48000],
                ],
                'payment' => 'TRANSFER',
                'order_type' => 'delivery',
                'customer_name' => 'ShopeeFood #SF001',
            ],
            [
                'products' => [
                    ['name' => 'Cafe Latte', 'qty' => 1, 'price' => 32000],
                ],
                'payment' => 'CASH',
                'order_type' => 'takeaway',
                'customer_name' => 'Walk-in',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTodayScenarios(Carbon $date): array
    {
        return [
            [
                'products' => [
                    ['name' => 'Americano', 'qty' => 1, 'price' => 28000],
                    ['name' => 'Croissant', 'qty' => 1, 'price' => 22000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer' => 'CUST001',
                'customer_name' => 'John Doe',
                'notes' => 'Regular pagi',
            ],
            [
                'products' => [
                    ['name' => 'Iced Latte', 'qty' => 2, 'price' => 35000],
                    ['name' => 'Pain au Chocolat', 'qty' => 1, 'price' => 28000],
                ],
                'payment' => 'QRIS',
                'order_type' => 'takeaway',
                'customer_name' => 'Walk-in',
            ],
            [
                'products' => [
                    ['name' => 'Cappuccino', 'qty' => 3, 'price' => 32000],
                    ['name' => 'Cinnamon Roll', 'qty' => 2, 'price' => 25000],
                    ['name' => 'Cheesecake', 'qty' => 1, 'price' => 45000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer_name' => 'Group Brunch',
                'discount' => 'WELCOME10',
            ],
            [
                'products' => [
                    ['name' => 'Cold Brew', 'qty' => 1, 'price' => 38000],
                    ['name' => 'Club Sandwich', 'qty' => 1, 'price' => 55000],
                ],
                'payment' => 'GOPAY',
                'order_type' => 'dine_in',
                'customer' => 'CUST002',
                'customer_name' => 'Jane Smith',
            ],
            [
                'products' => [
                    ['name' => 'Espresso', 'qty' => 2, 'price' => 18000],
                    ['name' => 'Macchiato', 'qty' => 1, 'price' => 25000],
                ],
                'payment' => 'CASH',
                'order_type' => 'takeaway',
                'customer_name' => 'Walk-in',
            ],
            [
                'products' => [
                    ['name' => 'Green Tea Latte', 'qty' => 1, 'price' => 35000],
                    ['name' => 'Tiramisu', 'qty' => 1, 'price' => 48000],
                ],
                'payment' => 'OVO',
                'order_type' => 'dine_in',
                'customer' => 'CUST004',
                'customer_name' => 'Siti Rahayu',
                'discount' => 'VIP20',
            ],
            [
                'products' => [
                    ['name' => 'Berry Blast Smoothie', 'qty' => 1, 'price' => 38000],
                    ['name' => 'Mango Smoothie', 'qty' => 1, 'price' => 35000],
                    ['name' => 'Chocolate Brownie', 'qty' => 2, 'price' => 28000],
                ],
                'payment' => 'CASH',
                'order_type' => 'dine_in',
                'customer_name' => 'Ibu dan Anak',
            ],
            [
                'products' => [
                    ['name' => 'Tuna Melt', 'qty' => 2, 'price' => 48000],
                    ['name' => 'Iced Americano', 'qty' => 2, 'price' => 30000],
                ],
                'payment' => 'DEBIT',
                'order_type' => 'dine_in',
                'customer_name' => 'Lunch Meeting',
            ],
        ];
    }
}
