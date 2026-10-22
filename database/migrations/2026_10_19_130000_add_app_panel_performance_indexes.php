<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COURSES_INDEX = 'esc_cursos_externo_ativo_curso_id_index';

    private const CERTIFICATES_INDEX = 'esc_cursocertificados_pessoa_certificable_index';

    public function up(): void
    {
        $schema = Schema::connection('emes');

        if (! $this->indexExists('esc_cursos', self::COURSES_INDEX)) {
            $schema->table('esc_cursos', function (Blueprint $table): void {
                $table->index(
                    ['externo', 'ativo', 'curso_id'],
                    self::COURSES_INDEX,
                );
            });
        }

        if (! $this->indexExists('esc_cursocertificados', self::CERTIFICATES_INDEX)) {
            $schema->table('esc_cursocertificados', function (Blueprint $table): void {
                $table->index(
                    ['pessoa_id', 'certificable_type', 'certificable_id', 'validado'],
                    self::CERTIFICATES_INDEX,
                );
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('emes');

        if ($this->indexExists('esc_cursos', self::COURSES_INDEX)) {
            $schema->table('esc_cursos', function (Blueprint $table): void {
                $table->dropIndex(self::COURSES_INDEX);
            });
        }

        if ($this->indexExists('esc_cursocertificados', self::CERTIFICATES_INDEX)) {
            $schema->table('esc_cursocertificados', function (Blueprint $table): void {
                $table->dropIndex(self::CERTIFICATES_INDEX);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::connection('emes')->selectOne(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$index],
        ) !== null;
    }
};
