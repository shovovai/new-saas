<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per verification method attempted for a website — all three
     * (dns_txt, html_file, meta_tag) are provisioned up front so switching
     * methods in the UI never requires regenerating tokens.
     */
    public function up(): void
    {
        Schema::create('website_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('method')->comment('dns_txt|html_file|meta_tag');
            $table->string('token');
            $table->string('status')->default('pending')->comment('pending|verified|failed');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['website_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_verifications');
    }
};
