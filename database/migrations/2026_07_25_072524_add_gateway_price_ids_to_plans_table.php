<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which price/product a plan maps to at each payment gateway. Set
     * from the admin panel — a plan with no price id configured for a
     * given gateway simply can't checkout through it yet.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_price_id_monthly')->nullable()->after('storage_mb');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');
            $table->string('paddle_price_id_monthly')->nullable()->after('stripe_price_id_yearly');
            $table->string('paddle_price_id_yearly')->nullable()->after('paddle_price_id_monthly');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_price_id_monthly', 'stripe_price_id_yearly',
                'paddle_price_id_monthly', 'paddle_price_id_yearly',
            ]);
        });
    }
};
