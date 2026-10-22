<?php

namespace App\Models;

use App\Models\Escola\Cursos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session;

class Setores extends Model
{
    protected $connection = 'emes';

    protected $table = 'setores';

    protected $fillable = [
        'codigopai',
        'unidadeorganizacional',
        'setor',
        'codigodauo',
        'cd_orgao',
        'comarca',
        'vara',
        'ordem',
        'email',
        'cns',
        'presidencia',
        'centrocusto',
    ];
    public function cursos(): HasMany
    {
        return $this->hasMany(Cursos::class, 'curso_id', 'id');
    }
    public function setorPai(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'codigopai', 'id');
    }
    public function lotacao(): HasMany
    {
        return $this->hasMany(Lotacao::class, 'setor_id', 'id');
    }
    public function setores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_lotacao', 'setor_id', 'user_id')
            ->using(Lotacao::class)
            ->withPivot('principal', 'atualizado_por', 'created_at', 'updated_at')
            ->withTimestamps();
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'codigopai', 'id');
    }

    public static function getHierarquiaOpcoes(?int $setorRaizId = null, ?int $ignorarId = null): array
    {
        $todosSetores = self::query()
            ->orderBy('setor')
            ->get();

        if ($ignorarId) {
            $bloqueados = collect([$ignorarId]);
            $adicionarDescendentes = function (int $parentId) use (&$adicionarDescendentes, &$bloqueados, $todosSetores): void {
                foreach ($todosSetores->where('codigopai', $parentId) as $filho) {
                    $bloqueados->push($filho->id);
                    $adicionarDescendentes((int) $filho->id);
                }
            };
            $adicionarDescendentes($ignorarId);
            $todosSetores = $todosSetores->whereNotIn('id', $bloqueados->unique())->values();
        }

        $opcoes = [];
        $montarArvore = function ($parentId, int $nivel) use (&$montarArvore, $todosSetores, &$opcoes): void {
            foreach ($todosSetores->where('codigopai', $parentId) as $filho) {
                $opcoes[$filho->id] = str_repeat('-- ', $nivel) . $filho->setor;
                $montarArvore($filho->id, $nivel + 1);
            }
        };

        if ($setorRaizId) {
            $raiz = $todosSetores->firstWhere('id', $setorRaizId);

            if ($raiz) {
                $opcoes[$raiz->id] = $raiz->setor;
                $montarArvore($raiz->id, 1);
            }

            return $opcoes;
        }

        $montarArvore(null, 0);

        return $opcoes;
    }
}
