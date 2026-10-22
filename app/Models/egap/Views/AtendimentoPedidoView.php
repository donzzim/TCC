<?php

namespace App\Models\egap\Views;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class AtendimentoPedidoView extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_ped_atendimentopedidos';

    protected $primaryKey = 'item_id';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
