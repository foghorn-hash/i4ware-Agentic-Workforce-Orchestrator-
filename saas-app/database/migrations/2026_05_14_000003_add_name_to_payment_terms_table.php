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
        Schema::table('invoice_payment_terms', function (Blueprint $table) {
            // Add name column for payment term display
            if (!Schema::hasColumn('invoice_payment_terms', 'name')) {
                $table->string('name')->nullable()->after('days_to_pay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_payment_terms', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_payment_terms', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
