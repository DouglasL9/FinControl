<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['company_id', 'name', 'permissions', 'is_default', 'is_invite_default'])]
class CompanyPermissionProfile extends Model
{
    public const GROUPS = [
        'Painel' => [
            'dashboard.view' => 'Visualizar o painel financeiro',
        ],
        'Contas a pagar' => [
            'payables.view' => 'Visualizar contratos, parcelas e valores',
            'payables.create_contract' => 'Cadastrar contratos recorrentes',
            'payables.update_amount' => 'Alterar o valor das parcelas',
            'payables.change_payment_status' => 'Marcar pagamentos e reabrir parcelas',
            'payables.manage_contract' => 'Editar, pausar e reativar contratos',
        ],
        'Relatórios' => [
            'reports.view' => 'Visualizar e exportar relatórios',
        ],
        'Unidades' => [
            'units.view' => 'Visualizar unidades',
            'units.create' => 'Cadastrar unidades',
            'units.update' => 'Editar unidades',
            'units.archive' => 'Arquivar e reativar unidades',
        ],
        'Fornecedores' => [
            'suppliers.view' => 'Visualizar fornecedores',
            'suppliers.create' => 'Cadastrar fornecedores',
            'suppliers.update' => 'Editar fornecedores',
            'suppliers.archive' => 'Arquivar e reativar fornecedores',
        ],
        'Gestão da empresa' => [
            'team.view' => 'Visualizar os membros da equipe',
            'team.assign_profiles' => 'Alterar o perfil de acesso dos membros',
            'profiles.manage' => 'Criar, editar e excluir perfis de acesso',
            'company.update_branding' => 'Alterar a identidade visual',
        ],
    ];

    /**
     * Permissões de gestão: nunca são concedidas automaticamente ao perfil
     * padrão, que é atribuído a todo convidado ao aceitar um convite.
     */
    public const MANAGEMENT_GROUP = 'Gestão da empresa';

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_default' => 'boolean',
            'is_invite_default' => 'boolean',
        ];
    }

    public static function permissionKeys(): array
    {
        $keys = [];

        foreach (self::GROUPS as $permissions) {
            $keys = [...$keys, ...array_keys($permissions)];
        }

        return $keys;
    }

    public static function allPermissions(): array
    {
        return array_fill_keys(self::permissionKeys(), true);
    }

    /**
     * @return list<string>
     */
    public static function managementPermissionKeys(): array
    {
        return array_keys(self::GROUPS[self::MANAGEMENT_GROUP]);
    }

    /**
     * Permissões do perfil padrão criado com a empresa: todas as operacionais,
     * nenhuma de gestão.
     *
     * @return array<string, bool>
     */
    public static function defaultProfilePermissions(): array
    {
        return [
            ...self::allPermissions(),
            ...array_fill_keys(self::managementPermissionKeys(), false),
        ];
    }

    /**
     * Permissões do perfil "Membro", atribuído a quem aceita um convite:
     * somente consulta, sem ações de escrita nem de gestão.
     *
     * @return array<string, bool>
     */
    public static function inviteDefaultPermissions(): array
    {
        return [
            ...array_fill_keys(self::permissionKeys(), false),
            ...array_fill_keys(['dashboard.view', 'payables.view', 'units.view', 'suppliers.view'], true),
        ];
    }

    /**
     * Completa o catálogo com `false` para chaves ausentes e descarta chaves desconhecidas.
     *
     * @param  array<string, mixed>|null  $permissions
     * @return array<string, bool>
     */
    public static function normalize(?array $permissions): array
    {
        $normalized = array_fill_keys(self::permissionKeys(), false);

        foreach (array_intersect_key($permissions ?? [], $normalized) as $key => $enabled) {
            $normalized[$key] = (bool) $enabled;
        }

        return $normalized;
    }

    /**
     * Informa se `$permissions` concede alguma permissão que `$limit` não concede.
     *
     * @param  array<string, mixed>  $permissions
     * @param  array<string, mixed>  $limit
     */
    public static function exceeds(array $permissions, array $limit): bool
    {
        foreach ($permissions as $key => $enabled) {
            if ($enabled && ! ($limit[$key] ?? false)) {
                return true;
            }
        }

        return false;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function isDeveloperName(?string $name): bool
    {
        return mb_strtolower(trim((string) $name)) === Company::DEVELOPER_PROFILE_NAME;
    }

    public function isDeveloper(): bool
    {
        return self::isDeveloperName($this->name);
    }

    public static function isAdministratorName(?string $name): bool
    {
        return mb_strtolower(trim((string) $name)) === mb_strtolower(Company::ADMINISTRATOR_PROFILE_NAME);
    }

    /**
     * O perfil Dev só é visível para administradores do sistema; o perfil
     * Administrador só é visível para quem já o possui (e administradores do sistema).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSystemAdmin()) {
            return $query;
        }

        $ownProfileIds = DB::table('company_user')
            ->where('user_id', $user->getKey())
            ->whereNotNull('permission_profile_id')
            ->select('permission_profile_id');

        return $query
            ->whereRaw('lower(trim(name)) != ?', [Company::DEVELOPER_PROFILE_NAME])
            ->where(function (Builder $query) use ($ownProfileIds): void {
                $query->whereRaw('lower(trim(name)) != ?', [mb_strtolower(Company::ADMINISTRATOR_PROFILE_NAME)])
                    ->orWhereIn('id', $ownProfileIds);
            });
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user', 'permission_profile_id', 'user_id')
            ->withPivot('company_id');
    }
}
