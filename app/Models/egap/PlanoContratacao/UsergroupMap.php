<?php

namespace App\Models\egap\PlanoContratacao;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;

class UsergroupMap extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_jos_user_usergroup_map';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'user_id' => 'integer',
        'group_id' => 'integer',
    ];

    public static function usuarioPertence(?int $userId, array $groupIds): bool
    {
        if (blank($userId) || $groupIds === []) {
            return false;
        }

        return static::query()
            ->where('user_id', $userId)
            ->whereIn('group_id', $groupIds)
            ->exists();
    }

    public static function usuarioAtualPertence(array $groupIds): bool
    {
        return static::usuarioPertence(UserEgap::currentAuthenticated()?->id, $groupIds);
    }
}
