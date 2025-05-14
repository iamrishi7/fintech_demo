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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->after('id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('plan_id')->after('parent_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('eko_user_code')->after('phone_number')->nullable();
            $table->string('paysprint_merchant_id')->after('eko_user_code')->nullable();
        });

        Schema::table('aeps_commissions', function (Blueprint $table) {
            $table->decimal('fixed_charge', 16, 2)->default(0)->after('service');
        });
        Schema::table('bbps_commissions', function (Blueprint $table) {
            $table->decimal('from', 16, 2)->default(0)->after('operator_id');
            $table->decimal('to', 16, 2)->default(0)->after('from');
            $table->decimal('fixed_charge', 16, 2)->default(0)->after('service');
        });
        Schema::table('recharge_commissions', function (Blueprint $table) {
            $table->decimal('from', 16, 2)->default(0)->after('operator_id');
            $table->decimal('to', 16, 2)->default(0)->after('from');
            $table->decimal('fixed_charge', 16, 2)->default(0)->after('service');
        });
        Schema::table('dmt_commissions', function (Blueprint $table) {
            $table->decimal('fixed_charge', 16, 2)->default(0)->after('service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            //
        });
    }
};
