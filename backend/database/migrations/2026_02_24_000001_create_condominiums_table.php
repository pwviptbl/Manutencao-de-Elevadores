<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominiums', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('cnpj', 18)->nullable();
            $table->string('address');
            $table->string('address_number', 20)->nullable();
            $table->string('address_complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 9);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable(); // responsável
            $table->unsignedSmallInteger('sla_hours')->default(4); // SLA contratado em horas
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'cnpj']);
            $table->index(['tenant_id', 'active']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // RLS
        DB::statement('ALTER TABLE condominiums ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE condominiums FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY tenant_isolation ON condominiums
            USING (tenant_id = current_tenant_id())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON condominiums');
        Schema::dropIfExists('condominiums');
    }
};
