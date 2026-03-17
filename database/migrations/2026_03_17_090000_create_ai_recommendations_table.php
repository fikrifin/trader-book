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
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->nullable()->constrained()->nullOnDelete();
            $table->string('timeframe', 20)->default('H1');
            $table->string('provider', 50)->default('ollama_cloud');
            $table->string('model', 100)->nullable();
            $table->json('prompt_context');
            $table->json('recommendation')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('risk_flags')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->json('token_usage')->nullable();
            $table->enum('status', ['success', 'blocked', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['instrument_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};
