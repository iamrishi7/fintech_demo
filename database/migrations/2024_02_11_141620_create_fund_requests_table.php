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
        Schema::create('fund_requests', function (Blueprint $table) {
            $table->id('uuid');
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('transaction_id')->unique();
            $table->date('transaction_date')->useCurrent();
            $table->decimal('amount', 16, 2);
            $table->decimal('opening_balance', 16, 2);
            $table->decimal('closing_balance', 16, 2);
            $table->string('user_remarks')->nullable();
            $table->string('admin_remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_requests');
    }
};
