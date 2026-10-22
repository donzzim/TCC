<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ItemBaixa extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_itembaixa';
    public $timestamps = false;

    protected $fillable = [
        'id_baixa',
        'id_bem',
        'id_situacao',
    ];

    protected static function booted()
    {
        static::created(function ($itemBaixa) {
            BemMovel::where('id', $itemBaixa->id_bem)
                ->update([
                    'SituacaoBem'   => $itemBaixa->id_situacao,
                    'DataBaixa'     => now(),
                    'ProcessoBaixa' => $itemBaixa->baixa->NumeroProcesso ?? null,
                    'Usuario'       => Auth::id(),
                    'date_time'     => now(),
                ]);
        });
    }

    public function bem(): BelongsTo
    {
        return $this->belongsTo(BemMovel::class, 'id_bem', 'id');
    }

    public function baixa(): BelongsTo
    {
        return $this->belongsTo(Baixa::class, 'id_baixa', 'id');
    }

    public function situacaoDestino(): BelongsTo
    {
        return $this->belongsTo(SituacaoBemMovel::class, 'id_situacao', 'id');
    }
}
