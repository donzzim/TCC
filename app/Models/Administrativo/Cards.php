<?php

namespace App\Models\Administrativo;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property string|null $cor */
class Cards extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'adm_cards';

    protected $fillable = ['setor_id', 'list_id', 'descricao', 'cor', 'ordem', 'proximo'];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(Lists::class, 'list_id', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->HasMany(Tasks::class, 'card_id', 'id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::creating(function (Cards $row) {
            $row->ordem = Cards::where('list_id', $row->list_id)->max('ordem') + 1;
        });
    }
}
