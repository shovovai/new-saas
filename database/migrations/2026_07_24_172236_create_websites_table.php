<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->string('url');
            $table->string('domain')->index();
            $table->string('group')->nullable();
            $table->string('status')->default('pending_verification')->comment('pending_verification|verified|paused|failed');
            $table->string('verified_method')->nullable()->comment('dns_txt|html_file|meta_tag');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
