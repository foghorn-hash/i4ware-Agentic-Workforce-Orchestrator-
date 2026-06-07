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
        Schema::table('customers', function (Blueprint $table) {
            // Add number_of_users for SaaS pricing
            if (!Schema::hasColumn('customers', 'number_of_users')) {
                $table->integer('number_of_users')->default(1)->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'number_of_users')) {
                $table->dropColumn('number_of_users');
            }
        });
    }
};
