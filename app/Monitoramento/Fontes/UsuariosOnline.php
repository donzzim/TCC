<?php

namespace App\Monitoramento\Fontes;

use App\Models\egap\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

final class UsuariosOnline extends Fonte
{
    public static function contar(int $agora, ?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(function () use ($agora, $conexao): array {
            $db = self::emes($conexao);

            $idsWeb = $db->table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $agora - (int) config('monitoramento.online_web_min', 5) * 60)
                ->distinct()
                ->pluck('user_id')
                ->all();

            $externos = $idsWeb === []
                ? 0
                : User::query()->whereIn('id', $idsWeb)->role(User::ROLE_EXTERNO)->count();

            $app = $db->table('personal_access_tokens')
                ->where('last_used_at', '>=', Carbon::createFromTimestamp($agora, config('app.timezone'))->subMinutes((int) config('monitoramento.online_app_min', 15)))
                ->distinct()
                ->count('tokenable_id');

            return [
                'web_interno' => count($idsWeb) - $externos,
                'web_externo' => $externos,
                'app' => $app,
            ];
        });
    }
}
