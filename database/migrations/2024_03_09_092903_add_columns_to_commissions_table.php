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
        Schema::table('aeps_commissions', function (Blueprint $table) {
            $table->boolean('fixed_charge_flat')->after('is_flat')->default(0);
        });

        Schema::table('bbps_commissions', function (Blueprint $table) {
            $table->boolean('fixed_charge_flat')->after('is_flat')->default(0);
        });

        Schema::table('dmt_commissions', function (Blueprint $table) {
            $table->boolean('fixed_charge_flat')->after('is_flat')->default(0);
        });

        Schema::table('payout_commissions', function (Blueprint $table) {
            $table->boolean('fixed_charge_flat')->after('is_flat')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            //
        });
    }
};
