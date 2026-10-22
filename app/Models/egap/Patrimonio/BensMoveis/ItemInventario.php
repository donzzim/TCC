<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\Marcas;
use App\Models\egap\Cadastro\Modelos;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemInventario extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_itensinventario';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public static $snakeAttributes = false;

    protected $fillable = [
        'date_time',
        'id_bem',
        'unidades',
        'num_patrimonio',
        'num_patrimonioantigo',
        'num_serie',
        'descricao_resumida',
        'marca',
        'modelo',
        'setor',
        'id_inventario',
        'estado_conservacao',
        'setor_localizado',
        'unidade_localizado',
        'complemento_localizado',
        'descricao_detalhada',
        'observacao',
        'situacao',
        'termo',
        'atualizado_por',
        'num_serie_egap',
        'descricao_detalhada_egap',
        'marca_egap',
        'modelo_egap',
        'id_complementosetor',
        'transferido_em',
        'conciliado_patrimonio',
        'imagem_enviada'
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'transferido_em' => 'datetime',
    ];

    public function idInventarioRef(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function idBemRef(): BelongsTo
    {
        return $this->belongsTo(BemMovel::class, 'id_bem', 'id');
    }

    public function termoRef(): BelongsTo
    {
        return $this->belongsTo(Termo::class, 'termo', 'id');
    }

    public function complementoSetorRef(): BelongsTo
    {
        return $this->belongsTo(ComplementoSetor::class, 'id_complementosetor', 'id');
    }

    public function unidadesRef(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'unidades', 'id');
    }

    public function scopeDoInventario(Builder $query, Inventario|int $inventario): Builder
    {
        $inventarioId = $inventario instanceof Inventario ? $inventario->getKey() : $inventario;

        return $query->where('id_inventario', $inventarioId);
    }

    public function scopeDoSetor(Builder $query, int $setor): Builder
    {
        return $query->where('setor', $setor);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
