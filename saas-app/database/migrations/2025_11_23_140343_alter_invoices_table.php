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
            // EXAMPLES — adjust as needed:

            // Add a new column
            $table->string('reference_code')->nullable()->after('invoice_number');
            $table->date('invoice_date')->after('reference_code');
            $table->string('domain', 255)->after('invoice_date');

            // Modify an existing column
            // $table->string('invoice_number')->unique(false)->change();

            // Drop a column
            // $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Reverse added column
            $table->dropColumn('reference_code', 'invoice_date', 'domain');
            // Reverse modified or removed columns
            // $table->string('invoice_number')->unique()->change();
            // $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
        });
    }
};
