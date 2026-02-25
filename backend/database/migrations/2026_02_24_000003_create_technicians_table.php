<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable(); // vinculado a uma conta de usuário (role mecanico)
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('crea', 30)->nullable();   // registro profissional
            $table->string('region')->nullable();     // área geográfica de atendimento
            $table->string('status')->default('available'); // available|on_call|unavailable
            $table->jsonb('metadata')->default('{}');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'active']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // RLS
        DB::statement('ALTER TABLE technicians ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE technicians FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY tenant_isolation ON technicians
            USING (tenant_id = current_tenant_id())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON technicians');
        Schema::dropIfExists('technicians');
    }
};
