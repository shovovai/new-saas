<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->comment('stripe|paddle|sslcommerz');
            $table->string('provider_payment_id')->nullable();
            $table->unsignedInteger('amount')->comment('minor units');
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending')->comment('succeeded|failed|pending|refunded');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
