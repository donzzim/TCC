<?php

namespace App\Models\Administrativo;

use App\Models\Cadastro\Tabelas;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Lists extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;
    protected $table = 'adm_lists';
    protected $fillable = [
        'setor_id',
        'tipodemanda_id',
        'descricao',
        'cor',
        'ordem',
        'proximo',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function cards(): HasMany
    {
        return $this->HasMany(Cards::class, 'list_id', 'id');
    }
    public function tipodemanda(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'tipodemanda_id', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::creating(function (Lists $row) {
            $row->ordem = Lists::max('ordem') + 1;
        });
    }
}
