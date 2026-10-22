<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_formulario_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained('avc_formularios')->cascadeOnDelete();
            $table->foreignId('pergunta_id')->constrained('avc_perguntas')->restrictOnDelete();
            $table->unsignedTinyInteger('estrelas')->nullable();
            $table->unsignedSmallInteger('pontos')->nullable();
            $table->text('observacao')->nullable();
            $table->string('status', 40)->default('pendente')->comment('App\\Filament\\Egap\\Externo\\Groups\\ServicosGerais\\Enums\\StatusResposta');
            $table->timestamp('date_time')->nullable();

            $table->unique(['formulario_id', 'pergunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_formulario_respostas');
    }
};
