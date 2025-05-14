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
        Schema::create('aeps_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('role_id')->constrained()->cascadeOnUpdate();
            $table->decimal('from', 16, 2);
            $table->decimal('to', 16, 2);
            $table->string('service');
            $table->decimal('commission', 16, 2);
            $table->boolean('is_flat')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aeps_commissions');
    }
};
