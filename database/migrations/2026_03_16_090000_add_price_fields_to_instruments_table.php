<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->decimal('last_price', 20, 6)->nullable()->after('pip_value');
            $table->string('price_source', 40)->nullable()->after('last_price');
            $table->timestamp('price_updated_at')->nullable()->after('price_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropColumn(['last_price', 'price_source', 'price_updated_at']);
        });
    }
};
