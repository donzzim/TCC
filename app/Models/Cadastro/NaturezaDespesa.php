<?php

namespace App\Models\Cadastro;

use App\Models\Contratacao\Orcamento;
use App\Models\Escola\AcaoDespesas;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NaturezaDespesa extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
    ];
    public function orcamento()
    {
        return $this->hasMany(Orcamento::class, 'natureza_id', 'id');
    }
    public function despesas()
    {
        return $this->hasMany(AcaoDespesas::class, 'natureza_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('natureza', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'NATUREZA_DESPESA');
        });
        static::creating(function (NaturezaDespesa $row) {
            $row->grupo = 'NATUREZA_DESPESA';
        });
    }
}
