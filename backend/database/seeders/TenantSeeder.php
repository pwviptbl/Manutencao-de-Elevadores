<?php

namespace Database\Seeders;

use App\Enums\TechnicianStatus;
use App\Models\Condominium;
use App\Models\Elevator;
use App\Models\ServiceOrder;
use App\Models\Technician;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────
        // TENANT 1 — Empresa Demo (plano Business)
        // ─────────────────────────────────────────
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'elevadores-demo'],
            [
                'name'     => 'Elevadores Demo Ltda',
                'slug'     => 'elevadores-demo',
                'plan'     => 'business',
                'cnpj'     => '12.345.678/0001-99',
                'email'    => 'contato@elevadores-demo.com.br',
                'phone'    => '(11) 3000-0000',
                'active'   => true,
                'settings' => [
                    'sla_hours_default' => 4,
                    'logo_url'          => null,
                    'primary_color'     => '#1d4ed8',
                ],
            ]
        );

        // Injeta contexto RLS para o seeder
        DB::statement("SELECT set_config('app.tenant_id', ?, false)", [(string) $tenant->id]);

        // ─────────────────────────────────────────
        // USUÁRIOS
        // ─────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.com', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Administrador Demo',
                'password'  => Hash::make('password'),
                'active'    => true,
            ]
        );
        $admin->assignRole('admin');

        $atendente = User::firstOrCreate(
            ['email' => 'atendente@demo.com', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Atendente Demo',
                'password'  => Hash::make('password'),
                'active'    => true,
            ]
        );
        $atendente->assignRole('atendente');

        $mecanico = User::firstOrCreate(
            ['email' => 'mecanico@demo.com', 'tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Mecânico Demo',
                'password'  => Hash::make('password'),
                'active'    => true,
            ]
        );
        $mecanico->assignRole('mecanico');

        // ─────────────────────────────────────────
        // MECÂNICOS
        // ─────────────────────────────────────────
        $tec1 = Technician::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '(11) 99001-0001'],
            [
                'tenant_id' => $tenant->id,
                'user_id'   => $mecanico->id,
                'name'      => 'João da Silva',
                'phone'     => '(11) 99001-0001',
                'email'     => 'joao@demo.com',
                'crea'      => 'CREA-SP 12345',
                'region'    => 'Zona Sul SP',
                'status'    => TechnicianStatus::Available,
                'active'    => true,
            ]
        );

        $tec2 = Technician::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '(11) 99002-0002'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Carlos Oliveira',
                'phone'     => '(11) 99002-0002',
                'region'    => 'Zona Norte SP',
                'status'    => TechnicianStatus::Available,
                'active'    => true,
            ]
        );

        // ─────────────────────────────────────────
        // CONDOMÍNIOS
        // ─────────────────────────────────────────
        $cond1 = Condominium::firstOrCreate(
            ['tenant_id' => $tenant->id, 'cnpj' => '11.222.333/0001-44'],
            [
                'tenant_id'    => $tenant->id,
                'name'         => 'Condomínio Residencial Jardins',
                'cnpj'         => '11.222.333/0001-44',
                'address'      => 'Rua das Flores',
                'address_number' => '100',
                'neighborhood' => 'Jardins',
                'city'         => 'São Paulo',
                'state'        => 'SP',
                'zip_code'     => '01401-001',
                'phone'        => '(11) 3100-0001',
                'email'        => 'sindico@jardins.com.br',
                'contact_name' => 'Maria Santos',
                'sla_hours'    => 4,
                'active'       => true,
            ]
        );

        $cond2 = Condominium::firstOrCreate(
            ['tenant_id' => $tenant->id, 'cnpj' => '55.666.777/0001-88'],
            [
                'tenant_id'    => $tenant->id,
                'name'         => 'Edifício Comercial Centro',
                'cnpj'         => '55.666.777/0001-88',
                'address'      => 'Av. Paulista',
                'address_number' => '1500',
                'neighborhood' => 'Bela Vista',
                'city'         => 'São Paulo',
                'state'        => 'SP',
                'zip_code'     => '01310-100',
                'phone'        => '(11) 3200-0002',
                'email'        => 'manutencao@edcomercial.com.br',
                'contact_name' => 'Roberto Lima',
                'sla_hours'    => 2,
                'active'       => true,
            ]
        );

        // ─────────────────────────────────────────
        // ELEVADORES
        // ─────────────────────────────────────────
        $elev1 = Elevator::firstOrCreate(
            ['tenant_id' => $tenant->id, 'serial_number' => 'OTIS-2021-001'],
            [
                'tenant_id'          => $tenant->id,
                'condominium_id'     => $cond1->id,
                'serial_number'      => 'OTIS-2021-001',
                'manufacturer'       => 'Otis',
                'model'              => 'Gen2',
                'floor_count'        => 12,
                'capacity'           => '630kg / 8 pessoas',
                'type'               => 'traction',
                'installation_date'  => '2021-03-15',
                'last_revision_date' => '2025-09-01',
                'next_revision_date' => '2026-03-01',
                'active'             => true,
            ]
        );

        $elev2 = Elevator::firstOrCreate(
            ['tenant_id' => $tenant->id, 'serial_number' => 'SCHI-2019-007'],
            [
                'tenant_id'          => $tenant->id,
                'condominium_id'     => $cond2->id,
                'serial_number'      => 'SCHI-2019-007',
                'manufacturer'       => 'Schindler',
                'model'              => '3300',
                'floor_count'        => 22,
                'capacity'           => '1000kg / 13 pessoas',
                'type'               => 'traction',
                'installation_date'  => '2019-07-20',
                'last_revision_date' => '2025-08-15',
                'next_revision_date' => '2026-02-15',
                'active'             => true,
            ]
        );

        // ─────────────────────────────────────────
        // CHAMADOS DE EXEMPLO
        // ─────────────────────────────────────────
        ServiceOrder::firstOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Botão do 5º andar não responde'],
            [
                'tenant_id'          => $tenant->id,
                'elevator_id'        => $elev1->id,
                'condominium_id'     => $cond1->id,
                'created_by_user_id' => $atendente->id,
                'priority'           => 'P2',
                'status'             => 'open',
                'type'               => 'corrective',
                'origin'             => 'panel',
                'title'              => 'Botão do 5º andar não responde',
                'description'        => 'Síndica reportou que o botão do 5º andar não acende ao ser pressionado.',
                'caller_name'        => 'Maria Santos',
                'caller_phone'       => '(11) 3100-0001',
                'sla_deadline'       => now()->addHours(24),
            ]
        );

        ServiceOrder::firstOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Ruído anormal ao subir'],
            [
                'tenant_id'               => $tenant->id,
                'elevator_id'             => $elev2->id,
                'condominium_id'          => $cond2->id,
                'assigned_technician_id'  => $tec2->id,
                'created_by_user_id'      => $atendente->id,
                'priority'                => 'P1',
                'status'                  => 'assigned',
                'type'                    => 'corrective',
                'origin'                  => 'panel',
                'title'                   => 'Ruído anormal ao subir',
                'description'             => 'Ruído metálico ao passar pelo 10º andar em direção aos andares superiores.',
                'caller_name'             => 'Roberto Lima',
                'sla_deadline'            => now()->addHours(4),
                'assigned_at'             => now()->subMinutes(30),
            ]
        );

        $this->command->info("✓ Tenant '{$tenant->name}' populado com dados de demonstração.");
        $this->command->line('');
        $this->command->line('  Credenciais de acesso:');
        $this->command->line('  admin@demo.com     | password  (Admin)');
        $this->command->line('  atendente@demo.com | password  (Atendente)');
        $this->command->line('  mecanico@demo.com  | password  (Mecânico)');
    }
}
