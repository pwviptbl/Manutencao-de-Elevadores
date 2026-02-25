<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('tenant_id');
            $table->uuid('elevator_id');
            $table->uuid('condominium_id');
            $table->uuid('assigned_technician_id')->nullable();
            $table->uuid('created_by_user_id')->nullable(); // null = criado pela IA

            // Classificação
            $table->string('priority')->default('P2');
            // P0=emergencia | P1=urgente | P2=normal | P3=baixa
            $table->string('status')->default('open');
            // open|assigned|in_progress|completed|closed|cancelled
            $table->string('type')->default('corrective');
            // corrective|preventive|emergency
            $table->string('origin')->default('panel');
            // panel|whatsapp|voice|import|ai

            // Conteúdo
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->jsonb('photos')->default('[]');
            $table->jsonb('checklist')->default('[]'); // itens do checklist do mecânico
            $table->string('signature_url')->nullable();

            // SLA
            $table->timestamp('sla_deadline')->nullable();   // prazo calculado na abertura
            $table->timestamp('sla_violated_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Dados extras
            $table->string('caller_name')->nullable();     // quem reportou
            $table->string('caller_phone', 20)->nullable();
            $table->jsonb('ai_metadata')->default('{}');   // dados de triagem IA
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            // Índices para as consultas mais comuns
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'status', 'priority']);
            $table->index(['tenant_id', 'assigned_technician_id']);
            $table->index(['tenant_id', 'elevator_id']);
            $table->index(['tenant_id', 'condominium_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('sla_deadline');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('elevator_id')->references('id')->on('elevators');
            $table->foreign('condominium_id')->references('id')->on('condominiums');
            $table->foreign('assigned_technician_id')->references('id')->on('technicians')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        // RLS
        DB::statement('ALTER TABLE service_orders ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE service_orders FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY tenant_isolation ON service_orders
            USING (tenant_id = current_tenant_id())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON service_orders');
        Schema::dropIfExists('service_orders');
    }
};
