<?php

namespace App\Models\Contratacao;

use App\Models\Cadastro\NaturezaDespesa;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class OrcamentoEvento extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cnt_orcamentoeventos';
    protected $fillable = [
        'setor_id',
        'orcamento_id',
        'tipoevento_id',
        'atualizado_por',
        'data',
        'descricao',
        'processo',
        'valor_1instancia',
        'valor_2instancia',
        'valor_total',
        'observacao',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id', 'id');
    }
    public function natureza(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id', 'id');
    }
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEventoOrcamentario::class, 'tipoevento_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::created(function (OrcamentoEvento $row) {
            $orcamento = Orcamento::find($row->orcamento_id);
            if ($orcamento) {
                switch ($row->tipo->codigo) {
                    case 'AC':
                        $orcamento->saldo_1instancia += $row->valor_1instancia;
                        $orcamento->saldo_2instancia += $row->valor_2instancia;
                        $orcamento->saldo_total += $row->valor_total;
                        $orcamento->save();
                        break;

                    case 'DC':
                        $orcamento->saldo_1instancia -= $row->valor_1instancia;
                        $orcamento->saldo_2instancia -= $row->valor_2instancia;
                        $orcamento->saldo_total -= $row->valor_total;
                        $orcamento->save();
                        break;

                    case 'SU':
                        $orcamento->saldo_1instancia += $row->valor_1instancia;
                        $orcamento->saldo_2instancia += $row->valor_2instancia;
                        $orcamento->saldo_total += $row->valor_total;
                        $orcamento->save();
                        break;
                }
            }
        });

        static::updated(function (OrcamentoEvento $row) {
            $old = $row->getChanges();
            $orcamento = Orcamento::find($row->orcamento_id);
            if ($orcamento) {
                switch ($row->tipo->codigo) {
                    case 'AC':
                        if (isset($old['valor_1instancia']))
                            $orcamento->saldo_1instancia = ($orcamento->saldo_1instancia - (float) $old['valor_1instancia']) + $row->valor_1instancia;
                        if (isset($old['valor_2instancia']))
                            $orcamento->saldo_2instancia = ($orcamento->saldo_2instancia - (float) $old['valor_2instancia']) + $row->valor_2instancia;
                        if (isset($old['valor_total']))
                            $orcamento->saldo_total = ($orcamento->saldo_total - (float) $old['valor_total']) + $row->valor_total;
                        if (isset($old['valor_1instancia']) || isset($old['valor_2instancia']) || isset($old['valor_total']))
                            $orcamento->save();

                        break;

                    case 'DC':
                        if (isset($old['valor_1instancia']))
                            $orcamento->saldo_1instancia = ($orcamento->saldo_1instancia + (float) $old['valor_1instancia']) + $row->valor_1instancia;
                        if (isset($old['valor_2instancia']))
                            $orcamento->saldo_2instancia = ($orcamento->saldo_2instancia + (float) $old['valor_2instancia']) + $row->valor_2instancia;
                        if (isset($old['valor_total']))
                            $orcamento->saldo_total = ($orcamento->saldo_total + (float) $old['valor_total']) + $row->valor_total;
                        if (isset($old['valor_1instancia']) || isset($old['valor_2instancia']) || isset($old['valor_total']))
                            $orcamento->save();
                        break;

                    case 'SU':
                        if (isset($old['valor_1instancia']))
                            $orcamento->saldo_1instancia = ($orcamento->saldo_1instancia - (float) $old['valor_1instancia']) + $row->valor_1instancia;
                        if (isset($old['valor_2instancia']))
                            $orcamento->saldo_2instancia = ($orcamento->saldo_2instancia - (float) $old['valor_2instancia']) + $row->valor_2instancia;
                        if (isset($old['valor_total']))
                            $orcamento->saldo_total = ($orcamento->saldo_total - (float) $old['valor_total']) + $row->valor_total;
                        if (isset($old['valor_1instancia']) || isset($old['valor_2instancia']) || isset($old['valor_total']))
                            $orcamento->save();
                        break;
                }
            }
        });

        static::deleted(function (OrcamentoEvento $row) {
            $orcamento = Orcamento::find($row->orcamento_id);
            if ($orcamento) {
                switch ($row->tipo->codigo) {
                    case 'AC':
                        $orcamento->saldo_1instancia -= $row->valor_1instancia;
                        $orcamento->saldo_2instancia -= $row->valor_2instancia;
                        $orcamento->saldo_total -= $row->valor_total;
                        $orcamento->save();
                        break;

                    case 'DC':
                        $orcamento->saldo_1instancia += $row->valor_1instancia;
                        $orcamento->saldo_2instancia += $row->valor_2instancia;
                        $orcamento->saldo_total += $row->valor_total;
                        $orcamento->save();
                        break;

                    case 'SU':
                        $orcamento->saldo_1instancia -= $row->valor_1instancia;
                        $orcamento->saldo_2instancia -= $row->valor_2instancia;
                        $orcamento->saldo_total -= $row->valor_total;
                        $orcamento->save();
                        break;
                }
            }
        });
    }
}
