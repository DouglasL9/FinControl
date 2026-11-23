<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'primary_color', 'accent_color', 'logo_path', 'watermark_opacity', 'is_active'])]
class Company extends Model
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

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
