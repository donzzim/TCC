<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('emes')->create('egap_mobile_sessoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->integer('egap_id')->nullable();
            $table->integer('setor_id')->nullable();
            $table->integer('codigodauo')->nullable();
            $table->json('info');
            $table->string('dispositivo')->nullable();
            $table->timestamps();

            $table->foreign('token_id')
                ->references('id')->on('personal_access_tokens')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('emes')->dropIfExists('egap_mobile_sessoes');
    }
};
