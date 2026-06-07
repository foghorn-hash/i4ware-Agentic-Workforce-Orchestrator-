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
            $table->string('complaints_within')->nullable()->after('due_date');
            $table->string('sellers_reference')->nullable()->after('complaints_within');
            $table->string('buyers_reference')->nullable()->after('sellers_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['complaints_within', 'sellers_reference', 'buyers_reference']);
        });
    }
};
