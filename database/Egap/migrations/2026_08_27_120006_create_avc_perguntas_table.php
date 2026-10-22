<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_perguntas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_contrato', 2)->comment("li/co/te/tr/ag - App\\Filament\\Egap\\Externo\\Groups\\ServicosGerais\\Enums\\TipoContrato");
            $table->unsignedSmallInteger('ordem');
            $table->string('texto', 500);
            $table->unsignedSmallInteger('peso_maximo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_perguntas');
    }
};
