<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_permission_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->json('permissions');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->foreignId('permission_profile_id')
                ->nullable()
                ->constrained('company_permission_profiles')
                ->restrictOnDelete();
        });

        $permissions = [
            'dashboard.view' => true,
            'payables.view' => true,
            'payables.create_contract' => true,
            'payables.update_amount' => true,
            'payables.change_payment_status' => true,
            'payables.manage_contract' => true,
            'units.view' => true,
            'units.create' => true,
            'units.update' => true,
            'units.archive' => true,
            'suppliers.view' => true,
            'suppliers.create' => true,
            'suppliers.update' => true,
            'suppliers.archive' => true,
        ];

        foreach (DB::table('companies')->orderBy('id')->get(['id']) as $company) {
            $profileId = DB::table('company_permission_profiles')->insertGetId([
                'company_id' => $company->id,
                'name' => 'Acesso completo',
                'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('company_user')
                ->where('company_id', $company->id)
                ->update(['permission_profile_id' => $profileId]);
        }
    }

    public function down(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('permission_profile_id');
        });

        Schema::dropIfExists('company_permission_profiles');
    }
};
