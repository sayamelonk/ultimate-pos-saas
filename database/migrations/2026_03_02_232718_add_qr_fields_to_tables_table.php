<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (! Schema::hasColumn('tables', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->unique()->after('is_active');
            }
            if (! Schema::hasColumn('tables', 'qr_generated_at')) {
                $table->timestamp('qr_generated_at')->nullable()->after('qr_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_generated_at']);
        });
    }
};
