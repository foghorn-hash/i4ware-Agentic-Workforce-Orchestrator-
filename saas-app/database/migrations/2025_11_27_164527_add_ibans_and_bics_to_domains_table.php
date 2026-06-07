<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('iban1')->nullable();
            $table->string('bic1')->nullable();
            $table->string('iban2')->nullable();
            $table->string('bic2')->nullable();
            $table->string('iban3')->nullable();
            $table->string('bic3')->nullable();
        });
    }

    public function down()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'iban1', 'bic1',
                'iban2', 'bic2',
                'iban3', 'bic3'
            ]);
        });
    }
};
