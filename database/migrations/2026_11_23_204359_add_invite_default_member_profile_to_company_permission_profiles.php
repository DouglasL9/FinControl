<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa o perfil atribuído aos convidados do perfil "Administrador": cada
 * empresa ganha um perfil "Membro" somente de consulta, marcado como perfil de
 * convite. Perfis e atribuições existentes não são alterados.
 */
return new class extends Migration
{
    /**
     * @var array<string, bool>
     */
    private array $memberPermissions = [
        'dashboard.view' => true,
        'payables.view' => true,
        'payables.create_contract' => false,
        'payables.update_amount' => false,
        'payables.change_payment_status' => false,
        'payables.manage_contract' => false,
        'units.view' => true,
        'units.create' => false,
        'units.update' => false,
        'units.archive' => false,
        'suppliers.view' => true,
        'suppliers.create' => false,
        'suppliers.update' => false,
        'suppliers.archive' => false,
        'team.view' => false,
        'team.assign_profiles' => false,
        'profiles.manage' => false,
        'company.update_branding' => false,
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('company_permission_profiles', 'is_invite_default')) {
            Schema::table('company_permission_profiles', function (Blueprint $table) {
                $table->boolean('is_invite_default')->default(false)->after('is_default');
            });
        }

        DB::table('companies')->chunkById(200, function ($companies): void {
            foreach ($companies as $company) {
                $hasInviteProfile = DB::table('company_permission_profiles')
                    ->where('company_id', $company->id)
                    ->where('is_invite_default', true)
                    ->exists();

                if ($hasInviteProfile) {
                    continue;
                }

                DB::table('company_permission_profiles')->insert([
                    'company_id' => $company->id,
                    'name' => $this->availableName($company->id),
                    'permissions' => json_encode($this->memberPermissions, JSON_THROW_ON_ERROR),
                    'is_default' => false,
                    'is_invite_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('company_permission_profiles', 'is_invite_default')) {
            return;
        }

        $profilesInUse = DB::table('company_user')
            ->whereNotNull('permission_profile_id')
            ->select('permission_profile_id');

        DB::table('company_permission_profiles')
            ->where('is_invite_default', true)
            ->whereNotIn('id', $profilesInUse)
            ->delete();

        Schema::table('company_permission_profiles', function (Blueprint $table) {
            $table->dropColumn('is_invite_default');
        });
    }

    /**
     * Usa "Membro" ou, se a empresa já tiver um perfil com esse nome, a primeira
     * variação livre, sem renomear perfis existentes.
     */
    private function availableName(int $companyId): string
    {
        $candidates = ['Membro', 'Membro (convite)'];

        for ($suffix = 2; $suffix <= 50; $suffix++) {
            $candidates[] = "Membro (convite {$suffix})";
        }

        foreach ($candidates as $candidate) {
            $taken = DB::table('company_permission_profiles')
                ->where('company_id', $companyId)
                ->where('name', $candidate)
                ->exists();

            if (! $taken) {
                return $candidate;
            }
        }

        throw new RuntimeException("Não foi possível escolher um nome livre para o perfil de convite da empresa {$companyId}.");
    }
};
