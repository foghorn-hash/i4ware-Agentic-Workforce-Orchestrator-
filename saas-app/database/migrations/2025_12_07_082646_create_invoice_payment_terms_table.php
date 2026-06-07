<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_payment_terms', function (Blueprint $table) {
            $table->id(); // big integer unsigned primary key (id)
            $table->string('domain', 255); // käyttäjän domain
            $table->integer('days_to_pay'); // maksuaika päivinä, esim. 30
            $table->timestamps(); // luonti- ja päivitysaika (poista jos et halua)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_payment_terms');
    }
};
