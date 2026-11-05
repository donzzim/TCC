<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'monitoramento';

    public function up(): void
    {
        $schema = Schema::connection('monitoramento');

        foreach (['agregados_minuto' => 'minuto', 'agregados_hora' => 'hora'] as $tabela => $tempo) {
            $schema->create($tabela, function (Blueprint $table) use ($tempo) {
                $table->id();
                $table->integer($tempo)->index();
                $table->string('contexto', 20);
                $table->string('rota', 255);
                $table->integer('qtd')->default(0);
                $table->integer('erros_4xx')->default(0);
                $table->integer('erros_5xx')->default(0);
                $table->float('soma_ms')->default(0);
                $table->float('max_ms')->default(0);
                $table->float('soma_sql_ms')->default(0);
                $table->integer('soma_sql_qtd')->default(0);
                $table->float('max_memoria_mb')->default(0);
                $table->integer('n_mais_um')->default(0);
                $table->text('faixas');
                $table->index(['contexto', $tempo]);
            });
        }

        $schema->create('requisicoes', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique();
            $table->float('instante')->index();
            $table->string('contexto', 20);
            $table->string('rota', 255);
            $table->string('metodo', 10);
            $table->integer('status');
            $table->float('duracao_ms');
            $table->float('sql_ms');
            $table->integer('sql_qtd');
            $table->float('memoria_mb');
            $table->text('livewire');
            $table->text('detalhe')->nullable();
        });

        $schema->create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->integer('instante')->index();
            $table->text('dados');
        });

        $schema->create('batimentos', function (Blueprint $table) {
            $table->string('tarefa')->primary();
            $table->integer('ultima_execucao');
            $table->boolean('sucesso');
            $table->text('mensagem')->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('monitoramento');

        foreach (['batimentos', 'snapshots', 'requisicoes', 'agregados_hora', 'agregados_minuto'] as $tabela) {
            $schema->dropIfExists($tabela);
        }
    }
};
