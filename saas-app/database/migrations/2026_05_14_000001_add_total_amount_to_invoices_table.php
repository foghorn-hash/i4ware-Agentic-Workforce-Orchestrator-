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
        Schema::table('invoices', function (Blueprint $table) {
            // Add sellers_reference if it doesn't exist
            if (!Schema::hasColumn('invoices', 'sellers_reference')) {
                $table->string('sellers_reference')->nullable()->after('reference_code');
            }

            // Add buyers_reference if it doesn't exist
            if (!Schema::hasColumn('invoices', 'buyers_reference')) {
                $table->string('buyers_reference')->nullable()->after('sellers_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'sellers_reference')) {
                $table->dropColumn('sellers_reference');
            }
            if (Schema::hasColumn('invoices', 'buyers_reference')) {
                $table->dropColumn('buyers_reference');
            }
        });
    }
};
