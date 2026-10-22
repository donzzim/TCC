<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_formularios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('avc_periodos')->cascadeOnDelete();
            $table->unsignedInteger('setor_id')->comment('mat_setores.id, tabela legada sem FK');
            $table->unsignedInteger('fiscal_id')->nullable()->comment('jos_users.id, tabela legada sem FK');
            $table->unsignedInteger('gestor_id')->nullable()->comment('jos_users.id, tabela legada sem FK');
            $table->string('status', 40)->default('aberto')->comment('App\\Filament\\Egap\\Externo\\Groups\\ServicosGerais\\Enums\\StatusFormulario');
            $table->boolean('tem_falta_sem_substituicao')->default(false);
            $table->text('observacao_falta')->nullable();
            $table->unsignedTinyInteger('nota_gestor')->nullable();
            $table->timestamp('date_time')->nullable();

            $table->unique(['periodo_id', 'setor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_formularios');
    }
};
