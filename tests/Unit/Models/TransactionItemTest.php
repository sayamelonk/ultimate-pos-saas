<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionDiscount;
use App\Models\TransactionItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionItemTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected User $user;

    protected PosSession $posSession;

    protected Transaction $transaction;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->posSession = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $this->transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_transaction_item(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'item_name' => 'Test Product',
            'item_sku' => 'SKU-TEST-001',
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'id' => $item->id,
            'item_name' => 'Test Product',
            'item_sku' => 'SKU-TEST-001',
        ]);
    }

    public function test_transaction_item_belongs_to_transaction(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $item->transaction);
        $this->assertEquals($this->transaction->id, $item->transaction->id);
    }

    public function test_transaction_item_belongs_to_product(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertInstanceOf(Product::class, $item->product);
        $this->assertEquals($this->product->id, $item->product->id);
    }

    public function test_transaction_item_can_belong_to_product_variant(): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->assertInstanceOf(ProductVariant::class, $item->productVariant);
        $this->assertEquals($variant->id, $item->productVariant->id);
    }

    public function test_transaction_item_can_belong_to_inventory_item(): void
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $inventoryItem = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $unit->id,
        ]);

        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'inventory_item_id' => $inventoryItem->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $item->inventoryItem);
        $this->assertEquals($inventoryItem->id, $item->inventoryItem->id);
    }

    // ============================================================
    // DISCOUNTS RELATIONSHIP TESTS
    // ============================================================

    public function test_transaction_item_has_many_discounts(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
        ]);

        TransactionDiscount::factory()->itemLevel()->count(2)->create([
            'transaction_id' => $this->transaction->id,
            'transaction_item_id' => $item->id,
        ]);

        $this->assertCount(2, $item->discounts);
    }

    // ============================================================
    // GET GROSS AMOUNT TESTS
    // ============================================================

    public function test_get_gross_amount(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 25000,
        ]);

        // gross_amount = quantity * unit_price = 3 * 25000 = 75000
        $this->assertEquals(75000, $item->getGrossAmount());
    }

    public function test_get_gross_amount_with_decimal_quantity(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 1.5,
            'unit_price' => 20000,
        ]);

        // gross_amount = 1.5 * 20000 = 30000
        $this->assertEquals(30000, $item->getGrossAmount());
    }

    // ============================================================
    // GET PROFIT TESTS
    // ============================================================

    public function test_get_profit(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'cost_price' => 30000,
            'subtotal' => 100000,
        ]);

        // profit = subtotal - (cost_price * quantity) = 100000 - (30000 * 2) = 40000
        $this->assertEquals(40000, $item->getProfit());
    }

    public function test_get_profit_with_discount(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'cost_price' => 30000,
            'discount_amount' => 10000,
            'subtotal' => 90000, // (2 * 50000) - 10000
        ]);

        // profit = subtotal - (cost_price * quantity) = 90000 - (30000 * 2) = 30000
        $this->assertEquals(30000, $item->getProfit());
    }

    public function test_get_profit_with_modifiers(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 40000,
            'cost_price' => 25000,
            'modifiers_total' => 5000,
            'subtotal' => 45000, // 40000 + 5000
        ]);

        // profit = subtotal - (cost_price * quantity) = 45000 - (25000 * 1) = 20000
        $this->assertEquals(20000, $item->getProfit());
    }

    // ============================================================
    // GET MODIFIERS DISPLAY TESTS
    // ============================================================

    public function test_get_modifiers_display_attribute(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'modifiers' => [
                ['name' => 'Extra Cheese', 'price' => 5000],
                ['name' => 'Large Size', 'price' => 10000],
            ],
        ]);

        $this->assertEquals('Extra Cheese, Large Size', $item->modifiers_display);
    }

    public function test_get_modifiers_display_returns_empty_when_no_modifiers(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'modifiers' => [],
        ]);

        $this->assertEquals('', $item->modifiers_display);
    }

    public function test_get_modifiers_display_returns_empty_when_modifiers_null(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'modifiers' => null,
        ]);

        $this->assertEquals('', $item->modifiers_display);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 2.5000,
            'unit_price' => 50000.50,
            'cost_price' => 30000.25,
            'subtotal' => 125001.25,
        ]);

        $this->assertIsString($item->quantity);
        $this->assertIsString($item->unit_price);
        $this->assertIsString($item->cost_price);
        $this->assertIsString($item->subtotal);
    }

    public function test_modifiers_are_cast_to_array(): void
    {
        $modifiers = [
            ['name' => 'Extra Sauce', 'price' => 3000],
        ];

        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'modifiers' => $modifiers,
        ]);

        $this->assertIsArray($item->modifiers);
        $this->assertEquals($modifiers, $item->modifiers);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_with_variant_factory_state(): void
    {
        $item = TransactionItem::factory()->withVariant()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertNotNull($item->product_variant_id);
        $this->assertInstanceOf(ProductVariant::class, $item->productVariant);
    }

    public function test_with_modifiers_factory_state(): void
    {
        $modifiers = [
            ['name' => 'Extra Cheese', 'price' => 5000],
            ['name' => 'Bacon', 'price' => 8000],
        ];

        $item = TransactionItem::factory()->withModifiers($modifiers)->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertEquals($modifiers, $item->modifiers);
        $this->assertEquals(13000, $item->modifiers_total);
    }

    public function test_with_quantity_factory_state(): void
    {
        $item = TransactionItem::factory()->withQuantity(5)->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertEquals(5, $item->quantity);
    }

    // ============================================================
    // PRICE CALCULATION TESTS
    // ============================================================

    public function test_subtotal_calculation_with_modifiers(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 30000,
            'modifiers_total' => 5000,
            'discount_amount' => 0,
            'subtotal' => 65000, // (2 * 30000) + 5000
        ]);

        $expectedSubtotal = ($item->quantity * $item->unit_price) + $item->modifiers_total - $item->discount_amount;
        $this->assertEquals($expectedSubtotal, $item->subtotal);
    }

    public function test_subtotal_calculation_with_discount(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 30000,
            'modifiers_total' => 0,
            'discount_amount' => 10000,
            'subtotal' => 50000, // (2 * 30000) - 10000
        ]);

        $expectedSubtotal = ($item->quantity * $item->unit_price) + $item->modifiers_total - $item->discount_amount;
        $this->assertEquals($expectedSubtotal, $item->subtotal);
    }

    // ============================================================
    // VARIANT PRICE ADJUSTMENT TESTS
    // ============================================================

    public function test_variant_price_adjustment(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'base_price' => 25000,
            'variant_price_adjustment' => 5000,
            'unit_price' => 30000, // base_price + adjustment
        ]);

        $this->assertEquals(25000, $item->base_price);
        $this->assertEquals(5000, $item->variant_price_adjustment);
        $this->assertEquals(30000, $item->unit_price);
    }

    // ============================================================
    // NOTES TESTS
    // ============================================================

    public function test_item_can_have_notes(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'notes' => 'No onions please',
            'item_notes' => 'Extra spicy',
        ]);

        $this->assertEquals('No onions please', $item->notes);
        $this->assertEquals('Extra spicy', $item->item_notes);
    }

    // ============================================================
    // UNIT NAME TESTS
    // ============================================================

    public function test_item_has_unit_name(): void
    {
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $this->product->id,
            'unit_name' => 'pcs',
        ]);

        $this->assertEquals('pcs', $item->unit_name);
    }
}
