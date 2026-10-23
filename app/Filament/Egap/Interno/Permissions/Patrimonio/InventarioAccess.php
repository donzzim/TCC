<?php

namespace App\Filament\Egap\Interno\Permissions\Patrimonio;

use App\Models\egap\User;

/**
 * Abrir inventário (criar, editar, excluir — com unidades, comissão e
 * equipes) é exclusivo da permission `abrir_inventario` (role
 * `egap_inventario`). Conferir os bens continua livre para quem é do setor.
 */
final class InventarioAccess
{
    public const ROLE = 'egap_inventario';

    public const PERMISSAO_ABRIR = 'abrir_inventario';

    public static function podeAbrir(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->can(self::PERMISSAO_ABRIR));
    }

    /** `egap_patrimonio` consulta; quem abre inventário também vê. */
    public static function podeVer(): bool
    {
        return PatrimonioAccess::allowed() || self::podeAbrir();
    }
}
