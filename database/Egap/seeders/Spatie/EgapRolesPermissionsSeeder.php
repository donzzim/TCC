<?php

namespace Database\Seeders\Spatie;

use App\Models\egap\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EgapRolesPermissionsSeeder extends Seeder
{
    private const ROLE_PERMISSIONS = [
        'view_role',
        'view_any_role',
        'create_role',
        'update_role',
        'delete_role',
        'delete_any_role',
    ];

    private const ROLES_HAS_PERMISSIONS = [
        User::ROLE_USER => ['egap_access'],
        User::ROLE_EXTERNO => ['egap_externo_access'],
        'egap_almoxarifado' => ['egap_almoxarifado_access'],
        'egap_patrimonio' => ['egap_patrimonio_access'],
        'egap_processo' => ['egap_processo_access'],
        'egap_agendamento' => ['egap_agendamento_access'],
        'egap_cadastro' => ['egap_cadastro_access'],
        'egap_pedido' => ['egap_pedido_access'],
        // abre, edita e exclui inventários (unidades, comissão e equipes); conferir continua livre
        'egap_inventario' => ['abrir_inventario'],
        User::ROLE_SUPER_ADMIN => self::ROLE_PERMISSIONS,
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        $roles = [
            'egap_publico',
            'egap_registrado',
            'egap_acesso_cadastrar_usuario',
            'egap_acesso_editar_usuario',
            'egap_acesso_usuario_externo',
            'egap_csg_contas_agendamentos',
            'egap_agendamento',
            'egap_agendamento_transporte',
            'egap_agendamento_veiculos',
            'egap_almoxarifado',
            'egap_almoxarifado_balanco_anual',
            'egap_cadastro',
            'egap_chamados',
            'egap_compras',
            'egap_corregedoria',
            'egap_cscp_almoxarifado_patrimonio',
            'egap_csg_servicos',
            'egap_gestao_empenho',
            'egap_gestao_estrategico',
            'egap_gestao_orcamentario',
            'egap_gestao_reserva',
            'egap_gestor',
            'egap_patrimonio',
            'egap_patrimonio_imoveis',
            'egap_patrimonio_intangiveis',
            'egap_patrimonio_moveis_adm',
            'egap_patrimonio_moveis_baixar',
            'egap_patrimonio_moveis_cadastro',
            'egap_patrimonio_moveis_calcular_vida_util_zerada',
            'egap_patrimonio_moveis_comissao_levantamento',
            'egap_patrimonio_moveis_corrigir_termos',
            'egap_patrimonio_moveis_editar_bem',
            'egap_patrimonio_moveis_incorporar',
            'egap_patrimonio_moveis_inventario',
            'egap_patrimonio_moveis_levantamento',
            'egap_patrimonio_moveis_pedidos',
            'egap_patrimonio_moveis_reavaliacao',
            'egap_patrimonio_moveis_termos',
            'egap_patrimonio_moveis_transferencia',
            'egap_patrimonio_moveis_validar_entrega',
            'egap_patrimonio_moveis_validar_pedidos',
            'egap_patrimonio_moveis_validar_termo',
            'egap_plano_contratacao',
            'egap_plano_contratacao_gestao',
            'egap_ponto_eletronico',
            'egap_presidencia_designacao_magistrado',
            'egap_processos_administrativos',
            'egap_relatorios_almoxarifado_patrimonio',
            'egap_servicos_gerais_avaliacao',
            'egap_admin',
            'egap_super_admin',
        ];
        */

        $roles = [
            'egap_admin',
            'egap_super_admin',
            'egap_externo',
            'egap_user',
            'egap_almoxarifado',
            'egap_patrimonio',
            'egap_processo',
            'egap_agendamento',
            'egap_cadastro',
            'egap_pedido',
            'egap_inventario',
        ];

        $permissions = [
            ...self::ROLE_PERMISSIONS,
            'egap_externo_access',
            'egap_almoxarifado_access',
            'egap_patrimonio_access',
            'egap_processo_access',
            'egap_agendamento_access',
            'egap_cadastro_access',
            'egap_pedido_access',
            'egap_access',
            'egap_superadmin',
            'abrir_inventario',
        ];

        foreach ($roles as $role)
        {
           Role::findOrCreate($role, User::GUARD);
        }

        foreach ($permissions as $permission)
        {
            Permission::findOrCreate($permission, User::GUARD);
        }

        foreach (self::ROLES_HAS_PERMISSIONS as $role => $rolePermissions)
        {
            Role::findByName($role, User::GUARD)->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
