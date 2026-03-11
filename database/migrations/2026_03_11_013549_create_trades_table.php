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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trading_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('setup_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->enum('session', ['asia', 'london', 'new_york', 'overlap']);
            $table->string('pair');
            $table->enum('direction', ['buy', 'sell']);
            $table->decimal('entry_price', 10, 5);
            $table->decimal('stop_loss', 10, 5);
            $table->decimal('take_profit_1', 10, 5);
            $table->decimal('take_profit_2', 10, 5)->nullable();
            $table->decimal('take_profit_3', 10, 5)->nullable();
            $table->decimal('close_price', 10, 5)->nullable();
            $table->decimal('lot_size', 10, 2);
            $table->decimal('risk_amount', 15, 2)->nullable();
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('swap', 10, 2)->default(0);
            $table->enum('result', ['win', 'loss', 'breakeven', 'partial']);
            $table->decimal('profit_loss', 15, 2)->default(0);
            $table->decimal('profit_loss_gross', 15, 2)->default(0);
            $table->decimal('pips', 10, 1)->nullable();
            $table->decimal('rr_ratio', 5, 2)->nullable();
            $table->decimal('rr_planned', 5, 2)->nullable();
            $table->string('setup')->nullable();
            $table->string('timeframe')->nullable();
            $table->boolean('followed_plan')->default(true);
            $table->text('mistake')->nullable();
            $table->text('notes')->nullable();
            $table->string('screenshot_before')->nullable();
            $table->string('screenshot_after')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date'], 'idx_user_date');
            $table->index(['user_id', 'pair'], 'idx_user_pair');
            $table->index(['user_id', 'result'], 'idx_user_result');
            $table->index('trading_account_id', 'idx_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
