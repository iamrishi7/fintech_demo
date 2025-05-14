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
        Schema::create('lic_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('role_id')->constrained()->cascadeOnUpdate();
            $table->decimal('from', 8, 2);
            $table->decimal('to', 8, 2);
            $table->string('service')->nullable();
            $table->decimal('commission', 8, 2);
            $table->boolean('is_flat')->default(0);
            $table->boolean('fixed_charge_flat')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lic_commissions');
    }
};
