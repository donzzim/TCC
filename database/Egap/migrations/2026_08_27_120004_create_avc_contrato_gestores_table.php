<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avc_contrato_gestores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('avc_contratos')->cascadeOnDelete();
            $table->unsignedInteger('user_id')->comment('jos_users.id, tabela legada sem FK');
            $table->boolean('principal')->default(false);

            $table->unique(['contrato_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avc_contrato_gestores');
    }
};
