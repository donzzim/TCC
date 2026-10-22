<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_contratos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 2)->comment("li/co/te/tr/ag - App\\Filament\\Egap\\Externo\\Groups\\ServicosGerais\\Enums\\TipoContrato");
            $table->boolean('vigente')->default(true);
            $table->timestamp('date_time')->nullable();
            $table->unsignedInteger('usuario_id')->nullable()->comment('jos_users.id, tabela legada sem FK');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_contratos');
    }
};
