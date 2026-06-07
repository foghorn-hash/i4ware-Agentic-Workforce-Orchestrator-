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
            $table->string('email')->nullable()->after('name');
            $table->string('phone_number')->nullable()->after('email');
            $table->string('business_id')->nullable()->after('phone_number');
            $table->string('vat_id')->nullable()->after('business_id');
            $table->string('address_line_1')->nullable()->after('vat_id');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('zip')->nullable()->after('address_line_2');
            $table->string('city')->nullable()->after('zip');
            $table->string('domain', 255)->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone_number', 'business_id', 'vat_id', 'address_line_1', 'address_line_2', 'zip', 'city']);
        });
    }
};
