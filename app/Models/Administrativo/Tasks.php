<?php

namespace App\Models\Administrativo;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Tasks extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'adm_tasks';
    protected $fillable = ['setor_id', 'card_id', 'descricao', 'cor', 'ordem', 'proximo'];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function card(): BelongsTo
    {
        return $this->belongsTo(Cards::class, 'card_id', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::creating(function (Tasks $row) {
            $row->ordem = Tasks::where('card_id', $row->list_id)->max('ordem') + 1;
        });
    }
}
