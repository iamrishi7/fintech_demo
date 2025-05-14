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
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('auth_image')->nullable();
            $table->string('comapny_name');
            $table->string('portal_name')->nullable();
            $table->string('logo_config');
            $table->boolean('receipt_footer')->default(1);
            $table->string('theme')->default('whatsapp');
            $table->string('receipt_layout');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customizations');
    }
};
