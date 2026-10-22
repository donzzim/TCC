<?php

namespace App\Models\egap\Views;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class MaterialDepositoView extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_ped_materiaisdeposito';

    protected $primaryKey = 'patrimonio_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
