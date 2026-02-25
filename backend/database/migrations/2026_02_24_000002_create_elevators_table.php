<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elevators', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('tenant_id');
            $table->uuid('condominium_id');
            $table->string('serial_number');
            $table->string('manufacturer');      // Otis, Schindler, Thyssenkrupp, etc.
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('floor_count')->nullable();   // quantos andares atende
            $table->string('capacity')->nullable(); // kg ou pessoas
            $table->string('type')->default('traction'); // traction|hydraulic|pneumatic
            $table->date('installation_date')->nullable();
            $table->date('last_revision_date')->nullable();
            $table->date('next_revision_date')->nullable();
            $table->jsonb('photos')->default('[]');     // URLs das fotos
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'serial_number']);
            $table->index('tenant_id');
            $table->index(['tenant_id', 'condominium_id']);
            $table->index(['tenant_id', 'active']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('condominium_id')->references('id')->on('condominiums')->cascadeOnDelete();
        });

        // RLS
        DB::statement('ALTER TABLE elevators ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE elevators FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY tenant_isolation ON elevators
            USING (tenant_id = current_tenant_id())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON elevators');
        Schema::dropIfExists('elevators');
    }
};
