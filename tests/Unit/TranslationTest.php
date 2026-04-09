<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    protected array $languages = ['en', 'id'];

    /**
     * Test that all translation files exist for both languages.
     */
    public function test_all_translation_files_exist_for_both_languages(): void
    {
        $langPath = lang_path();
        $enFiles = collect(File::files($langPath.'/en'))->map(fn ($file) => $file->getFilename())->toArray();
        $idFiles = collect(File::files($langPath.'/id'))->map(fn ($file) => $file->getFilename())->toArray();

        // Check that both directories have the same files
        sort($enFiles);
        sort($idFiles);

        $this->assertEquals($enFiles, $idFiles, 'Translation files should exist for both languages');
    }

    /**
     * Test that auth translation keys are complete.
     */
    public function test_auth_translation_keys_are_complete(): void
    {
        $enAuth = require lang_path('en/auth.php');
        $idAuth = require lang_path('id/auth.php');

        $enKeys = array_keys($enAuth);
        $idKeys = array_keys($idAuth);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Auth translation keys should match between en and id');

        // Required auth keys
        $requiredKeys = [
            'failed',
            'password',
            'throttle',
            'login',
            'login_title',
            'login_subtitle',
            'welcome_back',
            'email',
            'password_label',
            'remember_me',
            'forgot_password',
            'no_account',
            'register_now',
            'or',
            'login_button',
            'register',
            'register_title',
            'register_subtitle',
            'name',
            'business_name',
            'confirm_password',
            'have_account',
            'login_here',
            'register_button',
            'logout',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $enAuth, "English auth should have key: {$key}");
            $this->assertArrayHasKey($key, $idAuth, "Indonesian auth should have key: {$key}");
            $this->assertNotEmpty($enAuth[$key], "English auth key '{$key}' should not be empty");
            $this->assertNotEmpty($idAuth[$key], "Indonesian auth key '{$key}' should not be empty");
        }
    }

    /**
     * Test that no translation keys have placeholder text.
     */
    public function test_translations_have_no_placeholder_text(): void
    {
        foreach ($this->languages as $lang) {
            $authFile = require lang_path("{$lang}/auth.php");

            foreach ($authFile as $key => $value) {
                $this->assertStringNotContainsStringIgnoringCase(
                    'TODO',
                    $value,
                    "Translation {$lang}/auth.{$key} contains TODO placeholder"
                );
                $this->assertStringNotContainsStringIgnoringCase(
                    'FIXME',
                    $value,
                    "Translation {$lang}/auth.{$key} contains FIXME placeholder"
                );
                $this->assertStringNotContainsStringIgnoringCase(
                    'TRANSLATE',
                    $value,
                    "Translation {$lang}/auth.{$key} contains TRANSLATE placeholder"
                );
            }
        }
    }

    /**
     * Test that Indonesian translations are actually Indonesian (not just English copies).
     */
    public function test_indonesian_translations_differ_from_english(): void
    {
        $enAuth = require lang_path('en/auth.php');
        $idAuth = require lang_path('id/auth.php');

        // Keys that should definitely differ between en and id
        $keysThatShouldDiffer = [
            'failed',
            'password',
            'throttle',
            'login',
            'login_title',
            'login_subtitle',
            'welcome_back',
            'password_label',
            'remember_me',
            'forgot_password',
            'no_account',
            'register_now',
            'or',
            'login_button',
            'register_title',
            'register_subtitle',
            'name',
            'confirm_password',
            'have_account',
            'login_here',
            'register_button',
            'logout',
        ];

        $sameCount = 0;
        $differentCount = 0;

        foreach ($keysThatShouldDiffer as $key) {
            if (isset($enAuth[$key]) && isset($idAuth[$key])) {
                if ($enAuth[$key] === $idAuth[$key]) {
                    $sameCount++;
                } else {
                    $differentCount++;
                }
            }
        }

        // At least 50% should be different
        $this->assertGreaterThan(
            $sameCount,
            $differentCount,
            'Indonesian translations should differ from English for localized keys'
        );
    }

    /**
     * Test app translation keys are complete.
     */
    public function test_app_translation_keys_are_complete(): void
    {
        $enApp = require lang_path('en/app.php');
        $idApp = require lang_path('id/app.php');

        $enKeys = array_keys($enApp);
        $idKeys = array_keys($idApp);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'App translation keys should match between en and id');
    }

    /**
     * Test pricing translation keys are complete.
     */
    public function test_pricing_translation_keys_are_complete(): void
    {
        $enPricing = require lang_path('en/pricing.php');
        $idPricing = require lang_path('id/pricing.php');

        $enKeys = array_keys($enPricing);
        $idKeys = array_keys($idPricing);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Pricing translation keys should match between en and id');
    }

    /**
     * Test admin translation keys are complete.
     */
    public function test_admin_translation_keys_are_complete(): void
    {
        $enAdmin = require lang_path('en/admin.php');
        $idAdmin = require lang_path('id/admin.php');

        $enKeys = array_keys($enAdmin);
        $idKeys = array_keys($idAdmin);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Admin translation keys should match between en and id');
    }

    /**
     * Test inventory translation keys are complete.
     */
    public function test_inventory_translation_keys_are_complete(): void
    {
        $enInventory = require lang_path('en/inventory.php');
        $idInventory = require lang_path('id/inventory.php');

        $enKeys = array_keys($enInventory);
        $idKeys = array_keys($idInventory);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Inventory translation keys should match between en and id');
    }

    /**
     * Test menu translation keys are complete.
     */
    public function test_menu_translation_keys_are_complete(): void
    {
        $enMenu = require lang_path('en/menu.php');
        $idMenu = require lang_path('id/menu.php');

        $enKeys = array_keys($enMenu);
        $idKeys = array_keys($idMenu);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Menu translation keys should match between en and id');
    }

    /**
     * Test customers translation keys are complete.
     */
    public function test_customers_translation_keys_are_complete(): void
    {
        $enCustomers = require lang_path('en/customers.php');
        $idCustomers = require lang_path('id/customers.php');

        $enKeys = array_keys($enCustomers);
        $idKeys = array_keys($idCustomers);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Customers translation keys should match between en and id');
    }

    /**
     * Test pos translation keys are complete.
     */
    public function test_pos_translation_keys_are_complete(): void
    {
        $enPos = require lang_path('en/pos.php');
        $idPos = require lang_path('id/pos.php');

        $enKeys = array_keys($enPos);
        $idKeys = array_keys($idPos);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'POS translation keys should match between en and id');
    }

    /**
     * Test that translation keys used in views exist in language files.
     */
    public function test_auth_view_translation_keys_exist(): void
    {
        $enAuth = require lang_path('en/auth.php');

        // Keys used in login.blade.php
        $loginViewKeys = [
            'login',
            'login_title',
            'login_subtitle',
            'welcome_back',
            'welcome_back_subtitle',
            'email',
            'email_placeholder',
            'password_label',
            'password_placeholder',
            'remember_me',
            'forgot_password',
            'login_button',
            'or',
            'no_account',
            'register_now',
            'active_businesses',
            'transactions_per_day',
            'uptime',
            'join_other_owners',
            'demo_by_role',
            'test_by_plan',
            'feature_gating',
            'all_rights_reserved',
        ];

        foreach ($loginViewKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $enAuth,
                "Login view uses auth.{$key} but key doesn't exist in auth.php"
            );
        }

        // Keys used in register.blade.php
        $registerViewKeys = [
            'register',
            'register_title',
            'register_subtitle',
            'manage_business_easily',
            'manage_business_desc',
            'multi_outlet_support',
            'multi_outlet_desc',
            'realtime_inventory',
            'realtime_inventory_desc',
            'complete_reports',
            'complete_reports_desc',
            'step_business_info',
            'step_security',
            'business_name',
            'business_name_placeholder_example',
            'name',
            'name_placeholder',
            'phone',
            'phone_placeholder',
            'continue',
            'password_label',
            'password_min_chars',
            'password_hint',
            'confirm_password',
            'retype_password',
            'terms_agree',
            'terms_and_conditions',
            'and',
            'privacy_policy',
            'back',
            'register_button',
            'have_account',
            'login_here',
        ];

        foreach ($registerViewKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $enAuth,
                "Register view uses auth.{$key} but key doesn't exist in auth.php"
            );
        }
    }

    /**
     * Test subscription translation keys are complete.
     */
    public function test_subscription_translation_keys_are_complete(): void
    {
        $enSubscription = require lang_path('en/subscription.php');
        $idSubscription = require lang_path('id/subscription.php');

        $enKeys = array_keys($enSubscription);
        $idKeys = array_keys($idSubscription);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Subscription translation keys should match between en and id');

        // Required subscription keys used in views
        $requiredKeys = [
            'my_subscription',
            'manage_subscription',
            'subscription_status',
            'plan',
            'status',
            'period',
            'ends_at',
            'upgrade_plan',
            'renew_subscription',
            'cancel_subscription',
            'payment_history',
            'invoice_number',
            'amount',
            'monthly',
            'yearly',
            'most_popular',
            'current_plan',
            'payment_success',
            'payment_failed',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $enSubscription, "English subscription should have key: {$key}");
            $this->assertArrayHasKey($key, $idSubscription, "Indonesian subscription should have key: {$key}");
            $this->assertNotEmpty($enSubscription[$key], "English subscription key '{$key}' should not be empty");
            $this->assertNotEmpty($idSubscription[$key], "Indonesian subscription key '{$key}' should not be empty");
        }
    }

    /**
     * Test dashboard translation keys are complete.
     */
    public function test_dashboard_translation_keys_are_complete(): void
    {
        $enDashboard = require lang_path('en/dashboard.php');
        $idDashboard = require lang_path('id/dashboard.php');

        $enKeys = array_keys($enDashboard);
        $idKeys = array_keys($idDashboard);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Dashboard translation keys should match between en and id');

        // Required dashboard keys used in views
        $requiredKeys = [
            'title',
            'welcome_back',
            'today',
            'this_week',
            'this_month',
            'quick_actions',
            'open_pos',
            'add_product',
            'recent_activity',
            'account_frozen',
            'trial_period_days',
            'choose_plan',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $enDashboard, "English dashboard should have key: {$key}");
            $this->assertArrayHasKey($key, $idDashboard, "Indonesian dashboard should have key: {$key}");
            $this->assertNotEmpty($enDashboard[$key], "English dashboard key '{$key}' should not be empty");
            $this->assertNotEmpty($idDashboard[$key], "Indonesian dashboard key '{$key}' should not be empty");
        }
    }

    /**
     * Test products translation keys are complete.
     */
    public function test_products_translation_keys_are_complete(): void
    {
        $enProducts = require lang_path('en/products.php');
        $idProducts = require lang_path('id/products.php');

        $enKeys = array_keys($enProducts);
        $idKeys = array_keys($idProducts);

        sort($enKeys);
        sort($idKeys);

        $this->assertEquals($enKeys, $idKeys, 'Products translation keys should match between en and id');

        // Required products keys used in views
        $requiredKeys = [
            'products',
            'product',
            'manage_products',
            'add_product',
            'edit_product',
            'delete_product',
            'search_products',
            'all_categories',
            'all_status',
            'filter',
            'clear',
            'sku',
            'category',
            'type',
            'price',
            'cost',
            'status',
            'actions',
            'single',
            'variant',
            'combo',
            'active',
            'inactive',
            'view_details',
            'edit',
            'delete',
            'duplicate',
            'no_products',
            'no_products_desc',
            'confirm_delete',
            // Category keys
            'menu_categories',
            'manage_categories',
            'add_category',
            'search_categories',
            'root_only',
            'code',
            'parent',
            'pos',
            'no_categories',
            'no_categories_desc',
            // Combo keys
            'combo_meals',
            'manage_combos',
            'add_combo',
            'search_combos',
            'all_pricing',
            'fixed_price',
            'sum_of_items',
            'items',
            'pricing_type',
            'no_combos',
            'no_combos_desc',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $enProducts, "English products should have key: {$key}");
            $this->assertArrayHasKey($key, $idProducts, "Indonesian products should have key: {$key}");
            $this->assertNotEmpty($enProducts[$key], "English products key '{$key}' should not be empty");
            $this->assertNotEmpty($idProducts[$key], "Indonesian products key '{$key}' should not be empty");
        }
    }
}
