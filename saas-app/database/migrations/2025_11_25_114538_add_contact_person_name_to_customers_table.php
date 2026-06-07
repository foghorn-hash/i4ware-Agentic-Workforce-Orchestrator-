<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lisää contact_person_name -sarakkeen customers-tauluun.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('contact_person_name')->nullable()->after('name');
        });
    }

    /**
     * Poistaa contact_person_name -sarakkeen.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('contact_person_name');
        });
    }
};
