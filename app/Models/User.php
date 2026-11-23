<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role', 'permission_profile_id'])
            ->withTimestamps();
    }

    public function currentCompany(): ?Company
    {
        $activeCompanyId = request()->hasSession()
            ? request()->session()->get('active_company_id')
            : null;

        if ($activeCompanyId !== null) {
            $activeCompany = $this->companies()
                ->where('companies.is_active', true)
                ->whereKey($activeCompanyId)
                ->first();

            if ($activeCompany !== null) {
                return $activeCompany;
            }
        }

        return $this->companies()
            ->where('companies.is_active', true)
            ->orderBy('companies.id')
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
