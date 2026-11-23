<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adiciona as permissões do grupo "Gestão da empresa" a todos os perfis
 * existentes como `false`, inclusive no perfil padrão "Administrador": esse
 * perfil é atribuído a todo convidado ao aceitar um convite, então ativá-las
 * daria gestão de equipe, perfis e identidade visual a membros comuns. O
 * proprietário e o papel `admin` mantêm o acesso atual pelo papel.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $managementPermissions = [
        'team.view',
        'team.assign_profiles',
        'profiles.manage',
        'company.update_branding',
    ];

    public function up(): void
    {
        DB::table('company_permission_profiles')
            ->chunkById(200, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $permissions = json_decode((string) $profile->permissions, true) ?: [];
                    $missing = array_diff_key(array_fill_keys($this->managementPermissions, false), $permissions);

                    if ($missing === []) {
                        continue;
                    }

                    DB::table('company_permission_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'permissions' => json_encode([...$permissions, ...$missing], JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('company_permission_profiles')
            ->chunkById(200, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $permissions = json_decode((string) $profile->permissions, true) ?: [];

                    DB::table('company_permission_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'permissions' => json_encode(
                                array_diff_key($permissions, array_flip($this->managementPermissions)),
                                JSON_THROW_ON_ERROR,
                            ),
                        ]);
                }
            });
    }
};
