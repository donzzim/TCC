<?php

namespace App\Models;

use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoPublico;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $pis_pasep_nit
 * @property string|null $categoria_profissional
 * @property string|null $cargo
 * @property array|null $contato
 * @property bool|null $estrangeiro
 */
class Perfil extends Model
{
    use HasFactory;

    protected $table = 'user_perfil';

    protected $fillable = [
        'user_id',
        'titulacao',
        'cep',
        'pis_pasep_nit',
        'categoria_profissional',
        'cargo',
        'cbo',
        'idioma',
        'contato',
        'setor_orgao',
        'area_atuacao',
        'endereco',
        'curriculo',
        'estrangeiro',
        'raca',
        'sexo',
        'genero',
        'tipopessoa_id',
        'pcd',
        'pcd_necessidade',
        'chefia',
    ];

    protected $casts = [
        'titulacao' => 'array',
        'cep' => 'array',
        'cbo' => 'array',
        'idioma' => 'array',
        'area_atuacao' => 'array',
        'endereco' => 'array',
        'contato' => 'array',
        'estrangeiro' => 'boolean',
        'pcd' => 'array',
        'pcd_necessidade' => 'array',
        'chefia' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tipoPessoa()
    {
        return $this->belongsTo(TipoPublico::class, 'tipopessoa_id', 'id');
    }

    public function tipoSexo(): BelongsTo
    {
        return $this->belongsTo(TipoSexo::class, 'sexo', 'id');
    }

    public function tipoRaca(): BelongsTo
    {
        return $this->belongsTo(TipoRaca::class, 'raca', 'id');
    }

    public function getSexoDescricaoAttribute(): string
    {
        return $this->tipoSexo?->descricao ?? 'N/D';
    }

    public function getRacaDescricaoAttribute(): string
    {
        return $this->tipoRaca?->descricao ?? 'N/D';
    }

    public function getPcdFormatadoAttribute(): string
    {
        $pcds = $this->pcd;

        if (is_string($pcds)) {
            $pcds = json_decode($pcds, true);
        }

        if (! is_array($pcds) || count($pcds) === 0) {
            return 'Sem deficiência';
        }

        $tiposDeficiencia = self::tiposDeficienciaLookup();

        $descricoes = collect($pcds)
            ->map(function ($pcd) use ($tiposDeficiencia) {
                if (! is_array($pcd)) {
                    return null;
                }

                $tipo = isset($pcd['tipo']) ? (string) $pcd['tipo'] : null;
                $tipoDescricao = $tipo ? ($tiposDeficiencia[$tipo] ?? $tipo) : 'Tipo não informado';
                $necessidade = $this->limparTexto($pcd['descricao_necessidade'] ?? null);

                return trim($tipoDescricao.($necessidade ? ': '.$necessidade : ''));
            })
            ->filter()
            ->values();

        return $descricoes->isNotEmpty()
            ? $descricoes->implode('; ')
            : 'Sem deficiência';
    }

    private static function tiposDeficienciaLookup(): array
    {
        static $tipos = null;

        if ($tipos !== null) {
            return $tipos;
        }

        $tipos = [];

        TipoDeficiencia::query()
            ->get(['id', 'codigo', 'descricao'])
            ->each(function (TipoDeficiencia $tipoDeficiencia) use (&$tipos) {
                $tipos[(string) $tipoDeficiencia->id] = $tipoDeficiencia->descricao;

                if ($tipoDeficiencia->codigo) {
                    $tipos[(string) $tipoDeficiencia->codigo] = $tipoDeficiencia->descricao;
                }
            });

        return $tipos;
    }

    private function limparTexto(?string $texto): ?string
    {
        if (! $texto) {
            return null;
        }

        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = trim(strip_tags($texto));

        return $texto !== '' ? $texto : null;
    }
}
