<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionInvoiceTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_subscription_invoice(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $invoice = SubscriptionInvoice::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $plan->id,
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
        ]);

        $this->assertDatabaseHas('subscription_invoices', [
            'id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 299000,
        ]);
    }

    public function test_invoice_has_required_attributes(): void
    {
        $invoice = SubscriptionInvoice::factory()->create();

        $this->assertNotNull($invoice->id);
        $this->assertNotNull($invoice->tenant_id);
        $this->assertNotNull($invoice->subscription_id);
        $this->assertNotNull($invoice->subscription_plan_id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertNotNull($invoice->amount);
        $this->assertNotNull($invoice->status);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_invoice_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $invoice = SubscriptionInvoice::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $invoice->tenant);
        $this->assertEquals($tenant->id, $invoice->tenant->id);
    }

    public function test_invoice_belongs_to_subscription(): void
    {
        $subscription = Subscription::factory()->create();
        $invoice = SubscriptionInvoice::factory()->create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);

        $this->assertInstanceOf(Subscription::class, $invoice->subscription);
        $this->assertEquals($subscription->id, $invoice->subscription->id);
    }

    public function test_invoice_belongs_to_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $invoice = SubscriptionInvoice::factory()->create([
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertInstanceOf(SubscriptionPlan::class, $invoice->plan);
        $this->assertEquals($plan->id, $invoice->plan->id);
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_pending(): void
    {
        SubscriptionInvoice::factory()->pending()->count(2)->create();
        SubscriptionInvoice::factory()->paid()->create();
        SubscriptionInvoice::factory()->expired()->create();

        $pendingInvoices = SubscriptionInvoice::pending()->get();

        $this->assertCount(2, $pendingInvoices);
    }

    public function test_scope_paid(): void
    {
        SubscriptionInvoice::factory()->paid()->count(3)->create();
        SubscriptionInvoice::factory()->pending()->create();

        $paidInvoices = SubscriptionInvoice::paid()->get();

        $this->assertCount(3, $paidInvoices);
    }

    // ==================== STATUS CHECK TESTS ====================

    public function test_is_pending_returns_true_when_pending(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $this->assertTrue($invoice->isPending());
    }

    public function test_is_pending_returns_false_when_not_pending(): void
    {
        $invoice = SubscriptionInvoice::factory()->paid()->create();

        $this->assertFalse($invoice->isPending());
    }

    public function test_is_paid_returns_true_when_paid(): void
    {
        $invoice = SubscriptionInvoice::factory()->paid()->create();

        $this->assertTrue($invoice->isPaid());
    }

    public function test_is_paid_returns_false_when_not_paid(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $this->assertFalse($invoice->isPaid());
    }

    public function test_is_expired_returns_true_when_expired(): void
    {
        $invoice = SubscriptionInvoice::factory()->expired()->create();

        $this->assertTrue($invoice->isExpired());
    }

    public function test_is_expired_returns_false_when_not_expired(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $this->assertFalse($invoice->isExpired());
    }

    // ==================== MARK AS TESTS ====================

    public function test_mark_as_paid(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $invoice->markAsPaid([
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
            'external_id' => 'ext-123',
        ]);
        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals('BANK_TRANSFER', $invoice->payment_method);
        $this->assertEquals('BCA', $invoice->payment_channel);
        $this->assertEquals('ext-123', $invoice->xendit_response['external_id']);
    }

    public function test_mark_as_paid_without_payment_data(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $invoice->markAsPaid();
        $invoice->refresh();

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertNull($invoice->payment_method);
        $this->assertNull($invoice->payment_channel);
    }

    public function test_mark_as_expired(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $invoice->markAsExpired();
        $invoice->refresh();

        $this->assertEquals('expired', $invoice->status);
    }

    public function test_mark_as_failed(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $invoice->markAsFailed();
        $invoice->refresh();

        $this->assertEquals('failed', $invoice->status);
    }

    // ==================== FORMATTED AMOUNT TESTS ====================

    public function test_get_formatted_amount(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'total_amount' => 331890,
        ]);

        $this->assertEquals('Rp 331.890', $invoice->getFormattedAmount());
    }

    public function test_get_formatted_amount_with_zero(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'total_amount' => 0,
        ]);

        $this->assertEquals('Rp 0', $invoice->getFormattedAmount());
    }

    public function test_get_formatted_amount_with_large_number(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'total_amount' => 1664390,
        ]);

        $this->assertEquals('Rp 1.664.390', $invoice->getFormattedAmount());
    }

    // ==================== GENERATE INVOICE NUMBER TESTS ====================

    public function test_generate_invoice_number_format(): void
    {
        $invoiceNumber = SubscriptionInvoice::generateInvoiceNumber();

        $this->assertStringStartsWith('INV-', $invoiceNumber);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{4}$/', $invoiceNumber);
    }

    public function test_generate_invoice_number_contains_current_date(): void
    {
        $invoiceNumber = SubscriptionInvoice::generateInvoiceNumber();
        $currentDate = now()->format('Ymd');

        $this->assertStringContainsString($currentDate, $invoiceNumber);
    }

    public function test_generate_invoice_number_is_unique(): void
    {
        $invoiceNumber1 = SubscriptionInvoice::generateInvoiceNumber();
        $invoiceNumber2 = SubscriptionInvoice::generateInvoiceNumber();

        $this->assertNotEquals($invoiceNumber1, $invoiceNumber2);
    }

    // ==================== CASTING TESTS ====================

    public function test_amount_is_cast_to_decimal(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'amount' => 299000.50,
        ]);

        $this->assertEquals(299000.50, $invoice->amount);
    }

    public function test_tax_amount_is_cast_to_decimal(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'tax_amount' => 32890.05,
        ]);

        $this->assertEquals(32890.05, $invoice->tax_amount);
    }

    public function test_total_amount_is_cast_to_decimal(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'total_amount' => 331890.55,
        ]);

        $this->assertEquals(331890.55, $invoice->total_amount);
    }

    public function test_period_start_is_cast_to_datetime(): void
    {
        $invoice = SubscriptionInvoice::factory()->create();

        $this->assertInstanceOf(Carbon::class, $invoice->period_start);
    }

    public function test_period_end_is_cast_to_datetime(): void
    {
        $invoice = SubscriptionInvoice::factory()->create();

        $this->assertInstanceOf(Carbon::class, $invoice->period_end);
    }

    public function test_paid_at_is_cast_to_datetime(): void
    {
        $invoice = SubscriptionInvoice::factory()->paid()->create();

        $this->assertInstanceOf(Carbon::class, $invoice->paid_at);
    }

    public function test_expired_at_is_cast_to_datetime(): void
    {
        $invoice = SubscriptionInvoice::factory()->create();

        $this->assertInstanceOf(Carbon::class, $invoice->expired_at);
    }

    public function test_xendit_response_is_cast_to_array(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'xendit_response' => ['status' => 'paid', 'payment_id' => 'xyz123'],
        ]);

        $this->assertIsArray($invoice->xendit_response);
        $this->assertEquals('paid', $invoice->xendit_response['status']);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_pending_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $this->assertEquals('pending', $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_factory_paid_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->paid()->create();

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals('BANK_TRANSFER', $invoice->payment_method);
    }

    public function test_factory_expired_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->expired()->create();

        $this->assertEquals('expired', $invoice->status);
    }

    public function test_factory_failed_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->failed()->create();

        $this->assertEquals('failed', $invoice->status);
    }

    public function test_factory_yearly_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->yearly()->create();

        $this->assertEquals('yearly', $invoice->billing_cycle);
    }

    public function test_factory_monthly_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->monthly()->create();

        $this->assertEquals('monthly', $invoice->billing_cycle);
    }

    public function test_factory_with_amount_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->withAmount(599000)->create();

        $this->assertEquals(599000, $invoice->amount);
        $this->assertEquals(65890, $invoice->tax_amount); // 11% PPN
        $this->assertEquals(664890, $invoice->total_amount);
    }

    public function test_factory_with_xendit_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->withXendit('inv-123', 'https://xendit.co/inv/123')->create();

        $this->assertEquals('inv-123', $invoice->xendit_invoice_id);
        $this->assertEquals('https://xendit.co/inv/123', $invoice->xendit_invoice_url);
    }

    public function test_factory_for_tenant_state(): void
    {
        $tenant = Tenant::factory()->create();
        $invoice = SubscriptionInvoice::factory()->forTenant($tenant)->create();

        $this->assertEquals($tenant->id, $invoice->tenant_id);
    }

    public function test_factory_for_subscription_state(): void
    {
        $subscription = Subscription::factory()->create();
        $invoice = SubscriptionInvoice::factory()->forSubscription($subscription)->create();

        $this->assertEquals($subscription->id, $invoice->subscription_id);
        $this->assertEquals($subscription->tenant_id, $invoice->tenant_id);
    }

    public function test_factory_for_plan_state(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 299000,
        ]);
        $invoice = SubscriptionInvoice::factory()->forPlan($plan)->create();

        $this->assertEquals($plan->id, $invoice->subscription_plan_id);
        $this->assertEquals(299000, $invoice->amount);
        $this->assertEqualsWithDelta(299000 * 0.11, $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(299000 * 1.11, $invoice->total_amount, 0.01);
    }

    public function test_factory_with_notes_state(): void
    {
        $invoice = SubscriptionInvoice::factory()->withNotes('Upgrade from Starter to Growth')->create();

        $this->assertEquals('Upgrade from Starter to Growth', $invoice->notes);
    }

    // ==================== TAX CALCULATION TESTS ====================

    public function test_tax_calculation_11_percent(): void
    {
        $amount = 299000;
        $expectedTax = $amount * 0.11;
        $expectedTotal = $amount + $expectedTax;

        $invoice = SubscriptionInvoice::factory()->withAmount($amount)->create();

        $this->assertEquals($expectedTax, $invoice->tax_amount);
        $this->assertEquals($expectedTotal, $invoice->total_amount);
    }

    public function test_tax_calculation_for_all_plan_prices(): void
    {
        $prices = [99000, 299000, 599000, 1499000];

        foreach ($prices as $price) {
            $invoice = SubscriptionInvoice::factory()->withAmount($price)->create();

            $expectedTax = $price * 0.11;
            $expectedTotal = $price + $expectedTax;

            $this->assertEquals($expectedTax, $invoice->tax_amount);
            $this->assertEquals($expectedTotal, $invoice->total_amount);
        }
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_invoice_with_null_xendit_fields(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'xendit_invoice_id' => null,
            'xendit_invoice_url' => null,
            'xendit_response' => null,
        ]);

        $this->assertNull($invoice->xendit_invoice_id);
        $this->assertNull($invoice->xendit_invoice_url);
        $this->assertNull($invoice->xendit_response);
    }

    public function test_invoice_with_null_paid_at(): void
    {
        $invoice = SubscriptionInvoice::factory()->pending()->create();

        $this->assertNull($invoice->paid_at);
    }

    public function test_invoice_with_null_notes(): void
    {
        $invoice = SubscriptionInvoice::factory()->create([
            'notes' => null,
        ]);

        $this->assertNull($invoice->notes);
    }

    public function test_multiple_invoices_for_same_subscription(): void
    {
        $subscription = Subscription::factory()->create();

        $invoice1 = SubscriptionInvoice::factory()->create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
        $invoice2 = SubscriptionInvoice::factory()->create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);

        $this->assertCount(2, $subscription->invoices);
    }

    public function test_invoice_billing_period_monthly(): void
    {
        $invoice = SubscriptionInvoice::factory()->monthly()->create([
            'period_start' => now(),
            'period_end' => now()->addMonth(),
        ]);

        $this->assertTrue($invoice->period_start->diffInDays($invoice->period_end) >= 28);
    }

    public function test_invoice_billing_period_yearly(): void
    {
        $invoice = SubscriptionInvoice::factory()->yearly()->create([
            'period_start' => now(),
            'period_end' => now()->addYear(),
        ]);

        $this->assertTrue($invoice->period_start->diffInDays($invoice->period_end) >= 365);
    }

    public function test_invoice_currency_defaults_to_idr(): void
    {
        $invoice = SubscriptionInvoice::factory()->create();

        $this->assertEquals('IDR', $invoice->currency);
    }
}
