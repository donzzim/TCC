<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'monitoramento';

    public function up(): void
    {
        Schema::connection('monitoramento')->create('arquivos_processados', function (Blueprint $table) {
            $table->string('nome')->primary();
            $table->integer('processado_em')->index();
        });
    }

    public function down(): void
    {
        Schema::connection('monitoramento')->dropIfExists('arquivos_processados');
    }
};
