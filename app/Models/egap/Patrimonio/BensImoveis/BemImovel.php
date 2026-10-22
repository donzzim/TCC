<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\ContaContabil;
use App\Models\egap\Cadastro\ElementoDespesa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class BemImovel extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_imovel';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $guarded = [];

    public function setoresRelacaoRef()
    {
        return $this->belongsTo(Setores::class, 'Id_setores', 'id');
    }

    public function responsavelRelacaoRef()
    {
        return $this->belongsTo(Responsavel::class, 'id_responsavel', 'id');
    }

    public function processoAdmRelacaoRef()
    {
        return $this->belongsTo(Processo::class, 'num_processo_adm', 'id');
    }

    public function tipoImovelRelacaoRef()
    {
        return $this->belongsTo(TipoImovel::class, 'Id_tipoimovel', 'Id');
    }

    public function denominacaoRelacaoRef()
    {
        return $this->belongsTo(Denominacao::class, 'id_denominacao', 'id');
    }

    public function tipoDeBemRelacaoRef()
    {
        return $this->belongsTo(TipoDeBem::class, 'Id_tipodebem', 'Id');
    }

    public function planoContasRelacaoRef()
    {
        // A coluna real em imo_imovel e `Id_planocontas` (maiuscula). Com FK em
        // minuscula o eager loading nao casava (atributo vinha null no SELECT *).
        return $this->belongsTo(ContaContabil::class, 'Id_planocontas', 'id');
    }

    public function elementoDespesaRelacaoRef()
    {
        return $this->belongsTo(ElementoDespesa::class, 'id_elementodespesa', 'id');
    }

    public function cidadeRelacaoRef()
    {
        return $this->belongsTo(Cidades::class, 'id_cidade', 'Id');
    }

    public function cidufRelacaoRef()
    {
        return $this->belongsTo(CidUf::class, 'id_ciduf', 'id');
    }

    public function situacaoRelacaoRef()
    {
        return $this->belongsTo(Situacao::class, 'Id_situacao', 'Id');
    }

    public function condicaoUsoRelacaoRef()
    {
        return $this->belongsTo(CondicaoUso::class, 'id_condicaouso', 'Id');
    }

    public function estadoConservacaoRelacaoRef()
    {
        return $this->belongsTo(EstadoConservacao::class, 'Id_estadoconservacao', 'Id');
    }

    public function entradaSaidaRelacaoRef()
    {
        return $this->belongsTo(EntradaSaida::class, 'id_entradasaida', 'Id');
    }

    public function tributosRelacaoRef()
    {
        return $this->hasMany(Tributo::class, 'Id_imovel', 'Id')
            ->with('tipoTributoRelacaoref')
            ->orderBy('vencimento')
            ->orderBy('Id');
    }

    public function depreciacoesRelacaoRef()
    {
        return $this->hasMany(Depreciacao::class, 'Id_imovel', 'Id')
            ->orderByDesc('data_calculo')
            ->orderByDesc('item');
    }

    public function cedidosRelacaoRef()
    {
        return $this->hasMany(Cedido::class, 'id_imovel', 'Id');
    }

    public function reavaliacoesRelacaoRef()
    {
        return $this->hasMany(Reavaliacao::class, 'Id_imovel', 'Id');
    }

    public function obrasRelacaoRef()
    {
        return $this->hasMany(Obra::class, 'id_imovel', 'Id');
    }
}
