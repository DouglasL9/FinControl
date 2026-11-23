<?php

namespace App\Notifications\Concerns;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

trait BuildsBrandedMail
{
    /**
     * Dados de marca usados pelo tema de e-mail (logo da empresa, senão nome).
     *
     * @return array{brandName: string, brandLogoUrl: ?string}
     */
    protected function brandViewData(?Company $company): array
    {
        $logoUrl = $company?->logo_path === null
            ? null
            : Storage::disk('public')->url($company->logo_path);

        return [
            'brandName' => $company?->name ?? (string) config('app.name'),
            'brandLogoUrl' => $logoUrl,
        ];
    }

    /**
     * Empresa a usar na marca do e-mail: a única empresa ativa do usuário, se houver.
     */
    protected function singleActiveCompanyOf(?User $user): ?Company
    {
        if ($user === null) {
            return null;
        }

        $companies = $user->companies()->where('companies.is_active', true)->limit(2)->get();

        return $companies->count() === 1 ? $companies->first() : null;
    }

    protected function firstNameOf(?string $name): ?string
    {
        $firstName = $name === null ? '' : trim(explode(' ', trim($name))[0]);

        return $firstName === '' ? null : $firstName;
    }
}
