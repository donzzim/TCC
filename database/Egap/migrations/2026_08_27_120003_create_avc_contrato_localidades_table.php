<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_contrato_localidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('avc_contratos')->cascadeOnDelete();
            $table->unsignedInteger('setor_id')->comment('mat_setores.id, tabela legada sem FK');
            $table->boolean('ativo')->default(true);
            $table->timestamp('date_time')->nullable();

            $table->unique(['contrato_id', 'setor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_contrato_localidades');
    }
};
