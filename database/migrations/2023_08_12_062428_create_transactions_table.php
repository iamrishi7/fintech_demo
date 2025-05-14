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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnUpdate();
            $table->foreignUuid('updated_by')->constrained('users')->cascadeOnUpdate();
            $table->foreignUuid('triggered_by')->constrained('users')->cascadeOnUpdate();
            $table->string('reference_id');
            $table->string('service');
            $table->text('description');
            $table->decimal('credit_amount', 16, 2);
            $table->decimal('debit_amount', 16, 2);
            $table->decimal('opening_balance', 16, 2);
            $table->decimal('closing_balance', 16, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
