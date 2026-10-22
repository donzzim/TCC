<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guarda contra a volta de um bug que derrubava relatórios inteiros em silêncio.
 *
 * As tabelas legadas do EGAP viviam num banco próprio (`patrimonio`, conexão
 * `egap`) sem prefixo. Hoje elas moram no schema `emes` com o prefixo `egap_`,
 * mas sobraram queries que sobrescreviam a tabela do model com o nome antigo:
 *
 *     BemMovel::query()->from('mat_patrimonio as pat')   // emes.mat_patrimonio não existe
 *
 * O model aponta para `egap_mat_patrimonio`, o `from()` desfazia isso, e a
 * consulta morria com "Base table or view not found" só quando aquele relatório
 * era aberto.
 *
 * Este teste falha se alguém reintroduzir um nome sem prefixo em contexto de
 * query. Comentários e docblocks são ignorados de propósito — lá o nome antigo
 * é referência histórica legítima.
 */
class EgapPrefixoTabelasLegadasTest extends TestCase
{
    /**
     * Nomes curtos que também existem como tabela própria do EMES e portanto
     * NÃO devem ganhar prefixo (`users`, `roles`, `sessions`, …).
     */
    private const NAO_PREFIXAR = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations',
        'model_has_permissions', 'model_has_roles', 'password_reset_tokens', 'permissions',
        'personal_access_tokens', 'pla_iniciativas', 'pla_macrodesafios', 'pla_objetivos',
        'pla_perspectivas', 'pla_temas', 'role_has_permissions', 'roles', 'sessions', 'users',
    ];

    /**
     * Ocorrências conferidas à mão que são legítimas.
     *
     * @var array<string, list<int>>
     */
    private const EXCECOES = [
        // rótulo de aba na UI, não tabela
        'app/Filament/Egap/Interno/Groups/Agendamento/Resources/TransporteResource.php' => [221],
        // roda na conexão `egap` (banco `patrimonio`), onde o nome é sem prefixo mesmo
        'app/Filament/Egap/Externo/Groups/PlanoContratacao/Support/PlanoWriter.php' => [231],
    ];

    public function test_no_query_references_a_legacy_table_without_the_egap_prefix(): void
    {
        $tabelas = $this->tabelasLegadas();

        $this->assertNotEmpty($tabelas, 'não foi possível ler as tabelas legadas do schema');

        $alternativas = implode('|', array_map('preg_quote', $tabelas));
        $emQuery = '/(?:->|::)(?:from|join|leftJoin|rightJoin|crossJoin|joinSub|leftJoinSub|table)\s*\(\s*([\'"])(' . $alternativas . ')(?:\s+as\s+\w+)?\1/';
        $qualificada = '/(?<![\w_.])(' . $alternativas . ')\.\w+/';

        $infracoes = [];

        foreach ($this->arquivosPhp() as $caminho => $relativo) {
            foreach (file($caminho) as $i => $linha) {
                $numero = $i + 1;

                if (in_array($numero, self::EXCECOES[$relativo] ?? [], true)) {
                    continue;
                }

                if ($this->ehComentario($linha)) {
                    continue;
                }

                if (preg_match($emQuery, $linha, $m)) {
                    $infracoes[] = "{$relativo}:{$numero} — tabela '{$m[2]}' sem o prefixo egap_";

                    continue;
                }

                if (preg_match($qualificada, $linha, $m)) {
                    $infracoes[] = "{$relativo}:{$numero} — coluna qualificada '{$m[0]}' sem o prefixo egap_";
                }
            }
        }

        $this->assertSame(
            [],
            $infracoes,
            "Referências a tabelas legadas sem o prefixo `egap_`:\n  " . implode("\n  ", $infracoes)
        );
    }

    /**
     * Os nomes curtos das tabelas `egap_*`, menos os que colidem com tabelas do EMES.
     *
     * @return list<string>
     */
    private function tabelasLegadas(): array
    {
        $nomes = DB::connection('emes')
            ->table('information_schema.TABLES')
            ->selectRaw('TABLE_NAME as nome')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->whereRaw("TABLE_NAME LIKE 'egap!_%' ESCAPE '!'")
            ->pluck('nome')
            ->map(fn (string $n): string => substr($n, 5))
            ->reject(fn (string $n): bool => in_array($n, self::NAO_PREFIXAR, true))
            ->values()
            ->all();

        // as mais longas primeiro, senão `mat_setores` casa dentro de `mat_setores2`
        usort($nomes, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $nomes;
    }

    /**
     * @return iterable<string, string> caminho absoluto => caminho relativo
     */
    private function arquivosPhp(): iterable
    {
        foreach (['app', 'database', 'routes'] as $base) {
            $raiz = base_path($base);

            if (! is_dir($raiz)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz)) as $arquivo) {
                if (! $arquivo->isFile() || $arquivo->getExtension() !== 'php') {
                    continue;
                }

                $absoluto = $arquivo->getPathname();
                // normaliza as barras dos dois lados antes de recortar a raiz,
                // senão no Windows o prefixo nunca casa e o caminho sai absoluto
                $relativo = ltrim(
                    str_replace(str_replace('\\', '/', base_path()), '', str_replace('\\', '/', $absoluto)),
                    '/'
                );

                yield $absoluto => $relativo;
            }
        }
    }

    private function ehComentario(string $linha): bool
    {
        $t = ltrim($linha);

        return str_starts_with($t, '*')
            || str_starts_with($t, '//')
            || str_starts_with($t, '/*')
            || str_contains($t, '->comment(');
    }
}
