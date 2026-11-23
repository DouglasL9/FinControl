<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $profiles = DB::table('company_permission_profiles')
            ->where('name', 'Acesso completo')
            ->get(['id', 'company_id']);

        foreach ($profiles as $profile) {
            DB::table('company_permission_profiles')
                ->where('company_id', $profile->company_id)
                ->where('name', 'Administrador')
                ->update(['name' => 'Administrador (personalizado)']);

            DB::table('company_permission_profiles')
                ->where('id', $profile->id)
                ->update(['name' => 'Administrador', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('company_permission_profiles')
            ->where('name', 'Administrador')
            ->where('is_default', true)
            ->update(['name' => 'Acesso completo', 'updated_at' => now()]);
    }
};
