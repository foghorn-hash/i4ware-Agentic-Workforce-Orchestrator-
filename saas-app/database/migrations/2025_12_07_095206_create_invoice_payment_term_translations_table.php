<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('invoice_payment_term_translations')) {
            Schema::create('invoice_payment_term_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_payment_term_id');
                $table->string('locale', 2);
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('invoice_payment_term_id', 'ipt_payment_term_id_fk')
                    ->references('id')
                    ->on('invoice_payment_terms')
                    ->onDelete('cascade');

                $table->unique(['invoice_payment_term_id', 'locale'], 'ipt_term_locale_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('invoice_payment_term_translations');
    }
};
