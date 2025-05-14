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
        Schema::create('bbps', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('amount', 8, 2);
            $table->string('status', 20);
            $table->string('utility_number');
            $table->string('phone_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('reference_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bbps');
    }
};
