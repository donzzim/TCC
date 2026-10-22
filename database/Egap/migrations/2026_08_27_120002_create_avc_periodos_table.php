<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('avc_contratos')->cascadeOnDelete();
            $table->string('nome');
            $table->date('inicio');
            $table->date('fim');
            $table->unsignedTinyInteger('nota_1')->nullable();
            $table->unsignedTinyInteger('nota_2')->nullable();
            $table->unsignedTinyInteger('nota_3')->nullable();
            $table->timestamp('date_time')->nullable();
            $table->unsignedInteger('usuario_id')->nullable()->comment('jos_users.id, tabela legada sem FK');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_periodos');
    }
};
