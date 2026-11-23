<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'legal_name', 'cnpj', 'email', 'phone', 'address', 'primary_color', 'accent_color', 'logo_path', 'watermark_opacity', 'is_active'])]
class Company extends Model
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    public const DEVELOPER_PROFILE_NAME = 'dev';

    public const ADMINISTRATOR_PROFILE_NAME = 'Administrador';

    public const INVITE_PROFILE_NAME = 'Membro';

    protected function casts(): array
    {
        return [
            'watermark_opacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'permission_profile_id'])
            ->withTimestamps();
    }

    public function permissionProfiles(): HasMany
    {
        return $this->hasMany(CompanyPermissionProfile::class);
    }

    /**
     * Cria os perfis iniciais da empresa: "Administrador" (protegido, usado pelo
     * criador) e "Membro" (somente consulta, atribuído a quem aceita convites).
     */
    public function createInitialPermissionProfiles(): CompanyPermissionProfile
    {
        $this->permissionProfiles()->create([
            'name' => self::INVITE_PROFILE_NAME,
            'permissions' => CompanyPermissionProfile::inviteDefaultPermissions(),
            'is_default' => false,
            'is_invite_default' => true,
        ]);

        return $this->permissionProfiles()->create([
            'name' => self::ADMINISTRATOR_PROFILE_NAME,
            'permissions' => CompanyPermissionProfile::defaultProfilePermissions(),
            'is_default' => true,
            'is_invite_default' => false,
        ]);
    }

    public function membershipFor(User $user): ?User
    {
        return $this->users()->whereKey($user->getKey())->first();
    }

    /**
     * Permissões de gestão que o papel `admin` já tinha antes de existirem no
     * catálogo de perfis; continuam garantidas pelo papel.
     */
    public const ADMIN_ROLE_PERMISSIONS = ['team.view', 'team.assign_profiles', 'profiles.manage'];

    public function isOwner(?User $membership): bool
    {
        return $membership !== null
            && (int) $membership->pivot->company_id === (int) $this->getKey()
            && $membership->pivot->role === self::ROLE_OWNER;
    }

    /**
     * Informa se o membro pode abrir a página de configurações da empresa.
     */
    public function canAccessSettings(?User $membership): bool
    {
        $permissions = $this->permissionsFor($membership);

        foreach (['team.view', 'profiles.manage', 'company.update_branding', 'units.view', 'suppliers.view'] as $permission) {
            if ($permissions[$permission] ?? false) {
                return true;
            }
        }

        return $this->isOwner($membership);
    }

    /**
     * Informa se o ator pode conceder o conjunto de permissões informado:
     * quem não é proprietário só concede o que ele próprio possui.
     *
     * @param  array<string, mixed>  $permissions
     */
    public function canDelegate(?User $actor, array $permissions): bool
    {
        if ($this->isOwner($actor)) {
            return true;
        }

        return ! CompanyPermissionProfile::exceeds($permissions, $this->permissionsFor($actor));
    }

    /**
     * Regras de atribuição de perfil (a visibilidade do perfil atual do alvo é
     * verificada à parte): nunca o proprietário; quem não é proprietário não altera
     * o próprio perfil, só altera membros comuns e não mexe em quem tem mais
     * permissões que ele.
     */
    public function canAssignProfileTo(?User $actor, User $target): bool
    {
        if (
            ! $this->allows($actor, 'team.assign_profiles')
            || (int) $target->pivot->company_id !== (int) $this->getKey()
            || $target->pivot->role === self::ROLE_OWNER
        ) {
            return false;
        }

        if ($this->isOwner($actor)) {
            return true;
        }

        return (int) $target->getKey() !== (int) $actor->getKey()
            && $target->pivot->role === self::ROLE_MEMBER
            && $this->canDelegate($actor, $this->permissionsFor($target));
    }

    /**
     * Regras de edição/exclusão de perfil (perfil padrão e visibilidade são
     * verificados à parte): quem não é proprietário só altera perfis contidos nas
     * próprias permissões e que não estejam em uso pelo proprietário ou por outro
     * administrador.
     */
    public function canEditProfile(?User $actor, CompanyPermissionProfile $profile): bool
    {
        if (
            ! $this->allows($actor, 'profiles.manage')
            || (int) $profile->company_id !== (int) $this->getKey()
        ) {
            return false;
        }

        if ($this->isOwner($actor)) {
            return true;
        }

        if ($profile->is_invite_default) {
            return false;
        }

        return $this->canDelegate($actor, CompanyPermissionProfile::normalize($profile->permissions))
            && ! $this->users()
                ->wherePivot('permission_profile_id', $profile->getKey())
                ->wherePivotIn('role', [self::ROLE_OWNER, self::ROLE_ADMIN])
                ->whereKeyNot($actor->getKey())
                ->exists();
    }

    /**
     * Informa se o membro usa o perfil de acesso "Dev" nesta empresa.
     */
    public function hasDeveloperProfile(?User $membership): bool
    {
        if (
            $membership === null
            || (int) $membership->pivot->company_id !== (int) $this->getKey()
            || $membership->pivot->permission_profile_id === null
        ) {
            return false;
        }

        $profileName = $this->permissionProfiles()
            ->whereKey($membership->pivot->permission_profile_id)
            ->value('name');

        return is_string($profileName) && CompanyPermissionProfile::isDeveloperName($profileName);
    }

    public function allows(?User $membership, string $permission): bool
    {
        return (bool) ($this->permissionsFor($membership)[$permission] ?? false);
    }

    public function permissionsFor(?User $membership): array
    {
        if (
            $membership === null
            || (int) $membership->pivot->company_id !== (int) $this->getKey()
        ) {
            return [];
        }

        if ($membership->pivot->role === self::ROLE_OWNER) {
            return CompanyPermissionProfile::allPermissions();
        }

        $profile = $membership->pivot->permission_profile_id === null
            ? null
            : $this->permissionProfiles()->find($membership->pivot->permission_profile_id);
        $permissions = CompanyPermissionProfile::normalize($profile?->permissions);

        if ($membership->pivot->role === self::ROLE_ADMIN) {
            foreach (self::ADMIN_ROLE_PERMISSIONS as $permission) {
                $permissions[$permission] = true;
            }
        }

        return $permissions;
    }

    public function formattedCnpj(): ?string
    {
        if ($this->cnpj === null) {
            return null;
        }

        return preg_replace('/\A(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})\z/', '$1.$2.$3/$4-$5', $this->cnpj)
            ?? $this->cnpj;
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function recurringPayables(): HasMany
    {
        return $this->hasMany(RecurringPayable::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }
}
