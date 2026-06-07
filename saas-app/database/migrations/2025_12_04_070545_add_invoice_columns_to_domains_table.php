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
        Schema::table('domains', function (Blueprint $table) {
            // Laskun alkunumero — käytetään unsignedBigInteger:ä, nullable jos haluat sallia tyhjän.
            $table->unsignedBigInteger('invoice_start_number')->nullable();

            // Polku laskupohjan tiedostoon/templaten sijaintiin, pitääksesi merkkijonon alle 255 merkkiä.
            $table->string('invoice_template_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['invoice_start_number', 'invoice_template_path']);
        });
    }
};
