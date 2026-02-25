<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Cria roles e permissões padrão do sistema.
     * Roles são globais (sem tenant_id) para servir como template.
     * Na criação de cada tenant, os roles são copiados com tenant_id.
     */
    public function run(): void
    {
        // Limpa o cache do Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────
        // PERMISSÕES
        // ─────────────────────────────────────────
        $permissions = [
            // Chamados
            'service-orders.view',
            'service-orders.create',
            'service-orders.update',
            'service-orders.delete',
            'service-orders.assign',
            'service-orders.close',

            // Condomínios
            'condominiums.view',
            'condominiums.create',
            'condominiums.update',
            'condominiums.delete',

            // Elevadores
            'elevators.view',
            'elevators.create',
            'elevators.update',
            'elevators.delete',

            // Mecânicos
            'technicians.view',
            'technicians.create',
            'technicians.update',
            'technicians.delete',

            // Usuários
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Relatórios
            'reports.view',

            // Importação
            'imports.manage',

            // Configurações do tenant
            'settings.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'sanctum',
            ]);
        }

        // ─────────────────────────────────────────
        // ROLES e suas permissões
        // ─────────────────────────────────────────

        $roleMap = [
            'admin' => $permissions, // tudo

            'atendente' => [
                'service-orders.view',
                'service-orders.create',
                'service-orders.update',
                'service-orders.assign',
                'service-orders.close',
                'condominiums.view',
                'elevators.view',
                'technicians.view',
                'reports.view',
            ],

            'mecanico' => [
                'service-orders.view',
                'service-orders.update', // aceitar, checklist, fechar
                'condominiums.view',
                'elevators.view',
            ],

            'visualizador' => [
                'service-orders.view',
                'condominiums.view',
                'elevators.view',
                'technicians.view',
                'reports.view',
            ],
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'sanctum',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('✓ Roles e permissões criados com sucesso.');
    }
}
