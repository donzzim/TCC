<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DescricaoResumida extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_descricaoresumida';
    protected $fillable = [
        'date_time',
        'Descricao',
        'CodigodaClasse',
        'Usuario',
        'ContaContabil',
        'imagem',
        'id_tipo_material',
        'id_produto',
        'visibilidade',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }

    public function atualizado_por(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    public function conta_contabil(): BelongsTo
    {
        return $this->belongsTo(ContaContabil::class, 'ContaContabil', 'id');
    }

    public function produto_id(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'id_produto', 'id');
    }

    public function codigo_da_classe(): BelongsTo
    {
        return $this->belongsTo(ElementoDespesa::class, 'CodigodaClasse', 'id');
    }

    public function bensMoveis(): HasMany
    {
        return $this->hasMany(BemMovel::class, 'DescricaoResumidadoBem', 'id');
    }
}
