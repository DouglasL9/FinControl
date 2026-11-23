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

    public function membershipFor(User $user): ?User
    {
        return $this->users()->whereKey($user->getKey())->first();
    }

    public function isPermissionManager(?User $membership): bool
    {
        return $membership !== null
            && (int) $membership->pivot->company_id === (int) $this->getKey()
            && in_array($membership->pivot->role, [self::ROLE_OWNER, self::ROLE_ADMIN], true);
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

        $profile = $this->permissionProfiles()
            ->find($membership->pivot->permission_profile_id);

        return $profile?->permissions ?? [];
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
