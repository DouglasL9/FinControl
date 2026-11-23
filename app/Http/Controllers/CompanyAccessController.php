<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyAccessController extends Controller
{
    public function storeProfile(Request $request): RedirectResponse
    {
        $company = $this->activeCompany($request);
        $actor = $this->authorizedMember($request, $company, 'profiles.manage');
        $validated = $this->profileData($request, $company, $actor);

        $company->permissionProfiles()->create([
            ...$validated,
            'is_default' => false,
            'is_invite_default' => false,
        ]);

        return back()->with('status', 'permission-profile-created');
    }

    public function updateProfile(Request $request, int $profileId): RedirectResponse
    {
        $company = $this->activeCompany($request);
        $actor = $this->authorizedMember($request, $company, 'profiles.manage');
        $profile = $company->permissionProfiles()->visibleTo($request->user())->findOrFail($profileId);

        abort_if($profile->is_default, 403);
        abort_unless($company->canEditProfile($actor, $profile), 403);

        $profile->update($this->profileData($request, $company, $actor, $profile));

        return back()->with('status', 'permission-profile-updated');
    }

    public function destroyProfile(Request $request, int $profileId): RedirectResponse
    {
        $company = $this->activeCompany($request);
        $actor = $this->authorizedMember($request, $company, 'profiles.manage');
        $profile = $company->permissionProfiles()->visibleTo($request->user())->findOrFail($profileId);

        abort_if($profile->is_default || $profile->is_invite_default, 403);
        abort_unless($company->canEditProfile($actor, $profile), 403);
        abort_if($profile->members()->exists(), 409, 'Remova este perfil de todos os usuários antes de excluí-lo.');

        $profile->delete();

        return back()->with('status', 'permission-profile-deleted');
    }

    public function assignProfile(Request $request, int $userId): RedirectResponse
    {
        $company = $this->activeCompany($request);
        $actor = $this->authorizedMember($request, $company, 'team.assign_profiles');
        $member = $company->users()->whereKey($userId)->firstOrFail();

        abort_unless($member->pivot->role !== Company::ROLE_OWNER, 403);
        $this->abortIfMemberHasHiddenProfile($request, $company, $member);
        abort_unless($company->canAssignProfileTo($actor, $member), 403);

        $validated = $request->validate([
            'permission_profile_id' => [
                'required',
                'integer',
                Rule::exists('company_permission_profiles', 'id')
                    ->where('company_id', $company->getKey()),
            ],
        ]);

        $profile = $company->permissionProfiles()
            ->visibleTo($request->user())
            ->whereKey($validated['permission_profile_id'])
            ->first();

        abort_unless(
            $profile !== null
                && $company->canDelegate($actor, CompanyPermissionProfile::normalize($profile->permissions)),
            403,
        );

        $member->pivot->permission_profile_id = $profile->getKey();
        $member->pivot->save();

        return back()->with('status', 'member-permissions-updated');
    }

    public function updateRole(Request $request, int $userId): RedirectResponse
    {
        $company = $this->activeCompany($request);
        $actor = $company->membershipFor($request->user());

        abort_unless($actor?->pivot->role === Company::ROLE_OWNER, 403);

        $member = $company->users()->whereKey($userId)->firstOrFail();
        abort_unless($member->pivot->role !== Company::ROLE_OWNER, 403);
        $this->abortIfMemberHasHiddenProfile($request, $company, $member);

        $validated = $request->validate([
            'role' => ['required', Rule::in([Company::ROLE_ADMIN, Company::ROLE_MEMBER])],
        ]);

        $member->pivot->role = $validated['role'];
        $member->pivot->save();

        return back()->with('status', 'member-role-updated');
    }

    private function abortIfMemberHasHiddenProfile(Request $request, Company $company, User $member): void
    {
        if ($member->pivot->permission_profile_id === null) {
            return;
        }

        $isVisible = $company->permissionProfiles()
            ->visibleTo($request->user())
            ->whereKey($member->pivot->permission_profile_id)
            ->exists();

        abort_unless($isVisible, 403);
    }

    private function activeCompany(Request $request): Company
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);

        return $company;
    }

    private function authorizedMember(Request $request, Company $company, string $permission): User
    {
        $membership = $company->membershipFor($request->user());

        abort_unless($company->allows($membership, $permission), 403);

        return $membership;
    }

    private function profileData(
        Request $request,
        Company $company,
        User $actor,
        ?CompanyPermissionProfile $profile = null,
    ): array {
        $nameRule = Rule::unique('company_permission_profiles', 'name')
            ->where('company_id', $company->getKey());

        if ($profile !== null) {
            $nameRule->ignore($profile->getKey());
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                $nameRule,
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (! $request->user()->isSystemAdmin() && CompanyPermissionProfile::isDeveloperName($value)) {
                        $fail('Este nome é reservado.');
                    }
                },
            ],
            'permissions' => ['sometimes', 'array'],
        ]);

        $permissions = $validated['permissions'] ?? [];
        $unknownKeys = array_diff(array_keys($permissions), CompanyPermissionProfile::permissionKeys());

        if ($unknownKeys !== []) {
            throw ValidationException::withMessages([
                'permissions' => 'O perfil contém uma permissão inválida.',
            ]);
        }

        $normalizedPermissions = array_fill_keys(CompanyPermissionProfile::permissionKeys(), false);

        foreach ($permissions as $key => $enabled) {
            if (! in_array($enabled, [true, false, 0, 1, '0', '1'], true)) {
                throw ValidationException::withMessages([
                    "permissions.{$key}" => 'Selecione uma opção válida.',
                ]);
            }

            $normalizedPermissions[$key] = (bool) $enabled;
        }

        foreach ([
            'payables' => ['create_contract', 'update_amount', 'change_payment_status', 'manage_contract'],
            'units' => ['create', 'update', 'archive'],
            'suppliers' => ['create', 'update', 'archive'],
        ] as $module => $actions) {
            foreach ($actions as $action) {
                if ($normalizedPermissions["{$module}.{$action}"]) {
                    $normalizedPermissions["{$module}.view"] = true;
                    break;
                }
            }
        }

        if ($normalizedPermissions['payables.create_contract']) {
            $normalizedPermissions['units.view'] = true;
            $normalizedPermissions['suppliers.view'] = true;
        }

        if ($normalizedPermissions['team.assign_profiles']) {
            $normalizedPermissions['team.view'] = true;
        }

        if (! $company->canDelegate($actor, $normalizedPermissions)) {
            throw ValidationException::withMessages([
                'permissions' => 'Você não pode conceder permissões que não possui.',
            ]);
        }

        return [
            'name' => $validated['name'],
            'permissions' => $normalizedPermissions,
        ];
    }
}
