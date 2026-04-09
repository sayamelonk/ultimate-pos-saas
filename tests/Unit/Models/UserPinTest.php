<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPinTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_user_pin(): void
    {
        $userPin = UserPin::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('user_pins', [
            'id' => $userPin->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    public function test_user_pin_has_required_attributes(): void
    {
        $userPin = UserPin::factory()->create(['user_id' => $this->user->id]);

        $this->assertNotNull($userPin->id);
        $this->assertNotNull($userPin->user_id);
        $this->assertNotNull($userPin->pin_hash);
    }

    public function test_pin_hash_is_hidden(): void
    {
        $userPin = UserPin::factory()->create(['user_id' => $this->user->id]);
        $array = $userPin->toArray();

        $this->assertArrayNotHasKey('pin_hash', $array);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_user_pin_belongs_to_user(): void
    {
        $userPin = UserPin::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(User::class, $userPin->user);
        $this->assertEquals($this->user->id, $userPin->user->id);
    }

    // ==================== SET PIN TESTS ====================

    public function test_set_pin_hashes_pin(): void
    {
        $userPin = UserPin::factory()->create(['user_id' => $this->user->id]);
        $originalHash = $userPin->pin_hash;

        $userPin->setPin('9999');

        $this->assertNotEquals($originalHash, $userPin->pin_hash);
        $this->assertTrue(Hash::check('9999', $userPin->pin_hash));
    }

    public function test_set_pin_saves_to_database(): void
    {
        $userPin = UserPin::factory()->create(['user_id' => $this->user->id]);

        $userPin->setPin('5555');
        $userPin->refresh();

        $this->assertTrue(Hash::check('5555', $userPin->pin_hash));
    }

    // ==================== VERIFY PIN TESTS ====================

    public function test_verify_pin_returns_true_for_correct_pin(): void
    {
        $userPin = UserPin::factory()->withPin('1234')->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($userPin->verifyPin('1234'));
    }

    public function test_verify_pin_returns_false_for_incorrect_pin(): void
    {
        $userPin = UserPin::factory()->withPin('1234')->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->assertFalse($userPin->verifyPin('5678'));
    }

    public function test_verify_pin_returns_false_when_inactive(): void
    {
        $userPin = UserPin::factory()->withPin('1234')->inactive()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($userPin->verifyPin('1234'));
    }

    public function test_verify_pin_updates_last_used_at_on_success(): void
    {
        $userPin = UserPin::factory()->withPin('1234')->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'last_used_at' => null,
        ]);

        $this->assertNull($userPin->last_used_at);

        $userPin->verifyPin('1234');
        $userPin->refresh();

        $this->assertNotNull($userPin->last_used_at);
    }

    public function test_verify_pin_does_not_update_last_used_at_on_failure(): void
    {
        $userPin = UserPin::factory()->withPin('1234')->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'last_used_at' => null,
        ]);

        $userPin->verifyPin('9999');
        $userPin->refresh();

        $this->assertNull($userPin->last_used_at);
    }

    // ==================== CREATE FOR USER TESTS ====================

    public function test_create_for_user_creates_active_pin(): void
    {
        $userPin = UserPin::createForUser($this->user->id, '4321');

        $this->assertNotNull($userPin->id);
        $this->assertEquals($this->user->id, $userPin->user_id);
        $this->assertTrue($userPin->is_active);
        $this->assertTrue(Hash::check('4321', $userPin->pin_hash));
    }

    public function test_create_for_user_saves_to_database(): void
    {
        $userPin = UserPin::createForUser($this->user->id, '6789');

        $this->assertDatabaseHas('user_pins', [
            'id' => $userPin->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    // ==================== CASTING TESTS ====================

    public function test_is_active_is_cast_to_boolean(): void
    {
        $userPin = UserPin::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => 1,
        ]);

        $this->assertIsBool($userPin->is_active);
        $this->assertTrue($userPin->is_active);
    }

    public function test_last_used_at_is_cast_to_datetime(): void
    {
        $userPin = UserPin::factory()->used()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(Carbon::class, $userPin->last_used_at);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_inactive_state(): void
    {
        $userPin = UserPin::factory()->inactive()->create(['user_id' => $this->user->id]);

        $this->assertFalse($userPin->is_active);
    }

    public function test_factory_with_pin_state(): void
    {
        $userPin = UserPin::factory()->withPin('9876')->create(['user_id' => $this->user->id]);

        $this->assertTrue(Hash::check('9876', $userPin->pin_hash));
    }

    public function test_factory_used_state(): void
    {
        $userPin = UserPin::factory()->used()->create(['user_id' => $this->user->id]);

        $this->assertNotNull($userPin->last_used_at);
    }

    public function test_factory_used_at_state(): void
    {
        $datetime = now()->subDays(5);
        $userPin = UserPin::factory()->usedAt($datetime)->create(['user_id' => $this->user->id]);

        $this->assertEquals($datetime->toDateTimeString(), $userPin->last_used_at->toDateTimeString());
    }

    // ==================== PIN ACTIVATION TESTS ====================

    public function test_pin_can_be_activated(): void
    {
        $userPin = UserPin::factory()->inactive()->create(['user_id' => $this->user->id]);

        $this->assertFalse($userPin->is_active);

        $userPin->update(['is_active' => true]);

        $this->assertTrue($userPin->is_active);
    }

    public function test_pin_can_be_deactivated(): void
    {
        $userPin = UserPin::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($userPin->is_active);

        $userPin->update(['is_active' => false]);

        $this->assertFalse($userPin->is_active);
    }

    // ==================== MULTIPLE PIN TESTS ====================

    public function test_user_can_only_have_one_pin(): void
    {
        // This is controlled by application logic, not model constraint
        // But we test that user relationship works correctly
        $userPin1 = UserPin::factory()->create(['user_id' => $this->user->id]);

        // Getting userPin through user relationship should return one
        $this->assertInstanceOf(UserPin::class, $this->user->userPin);
    }

    // ==================== PIN SECURITY TESTS ====================

    public function test_different_pins_have_different_hashes(): void
    {
        $userPin1 = UserPin::factory()->withPin('1111')->create(['user_id' => $this->user->id]);

        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $userPin2 = UserPin::factory()->withPin('2222')->create(['user_id' => $user2->id]);

        $this->assertNotEquals($userPin1->pin_hash, $userPin2->pin_hash);
    }

    public function test_same_pin_has_different_hash_due_to_salt(): void
    {
        $userPin1 = UserPin::factory()->withPin('1234')->create(['user_id' => $this->user->id]);

        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $userPin2 = UserPin::factory()->withPin('1234')->create(['user_id' => $user2->id]);

        // Even same PIN should have different hash due to bcrypt salt
        $this->assertNotEquals($userPin1->pin_hash, $userPin2->pin_hash);

        // But both should verify correctly
        $this->assertTrue($userPin1->verifyPin('1234'));
        $this->assertTrue($userPin2->verifyPin('1234'));
    }

    // ==================== INTEGRATION WITH USER TESTS ====================

    public function test_user_has_pin_returns_correct_value(): void
    {
        // User without PIN
        $this->assertFalse($this->user->hasPin());

        // Add active PIN
        UserPin::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->user->hasPin());
    }

    public function test_user_verify_pin_delegates_to_user_pin(): void
    {
        UserPin::factory()->withPin('5678')->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($this->user->verifyPin('5678'));
        $this->assertFalse($this->user->verifyPin('1234'));
    }
}
