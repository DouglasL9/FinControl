<?php

use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use App\Models\User;

function createCompanyForManagementTest(User $owner): Company
{
    $company = Company::create(['name' => 'Empresa gerida']);
    $defaultProfile = $company->createInitialPermissionProfiles();
    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $defaultProfile->getKey(),
    ]);

    return $company;
}

/**
 * @param  list<string>  $enabledPermissions
 */
function createManagementTestProfile(Company $company, string $name, array $enabledPermissions): CompanyPermissionProfile
{
    return $company->permissionProfiles()->create([
        'name' => $name,
        'permissions' => [
            ...array_fill_keys(CompanyPermissionProfile::permissionKeys(), false),
            ...array_fill_keys($enabledPermissions, true),
        ],
    ]);
}

function addManagementTestMember(Company $company, CompanyPermissionProfile $profile, string $role = Company::ROLE_MEMBER): User
{
    $user = User::factory()->create();
    $company->users()->attach($user->getKey(), [
        'role' => $role,
        'permission_profile_id' => $profile->getKey(),
    ]);

    return $user;
}

function managementTestProfileIdOf(Company $company, User $user): int
{
    return (int) $company->users()->whereKey($user)->first()->pivot->permission_profile_id;
}

test('a member whose profile grants team permissions sees the team and assigns profiles', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $managerProfile = createManagementTestProfile($company, 'Gestão de equipe', ['team.view', 'team.assign_profiles', 'payables.view']);
    $readOnlyProfile = createManagementTestProfile($company, 'Consulta', ['payables.view']);
    $emptyProfile = createManagementTestProfile($company, 'Sem acesso', []);
    $manager = addManagementTestMember($company, $managerProfile);
    $target = addManagementTestMember($company, $emptyProfile);

    $this->actingAs($manager)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('settings-tab-team', false)
        ->assertSee('Membros da empresa')
        ->assertDontSee('settings-tab-profiles', false)
        ->assertDontSee('settings-tab-branding', false)
        ->assertDontSee('Enviar convite');

    $this->patch(route('company.members.permissions.update', $target), [
        'permission_profile_id' => $readOnlyProfile->getKey(),
    ])->assertSessionHas('status', 'member-permissions-updated');

    expect(managementTestProfileIdOf($company, $target))->toBe($readOnlyProfile->getKey());
});

test('members without management permissions are refused and do not see the management sections', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $recordsProfile = createManagementTestProfile($company, 'Cadastros', ['units.view']);
    $member = addManagementTestMember($company, $recordsProfile);
    $target = addManagementTestMember($company, $recordsProfile);

    $this->actingAs($member)
        ->get(route('company.show'))
        ->assertOk()
        ->assertDontSee('settings-tab-team', false)
        ->assertDontSee('settings-tab-profiles', false)
        ->assertDontSee('settings-tab-branding', false);

    $this->patch(route('company.members.permissions.update', $target), [
        'permission_profile_id' => $recordsProfile->getKey(),
    ])->assertForbidden();

    $this->post(route('company.permission-profiles.store'), ['name' => 'Novo', 'permissions' => []])
        ->assertForbidden();

    $this->patch(route('company.permission-profiles.update', $recordsProfile), ['name' => 'Renomeado'])
        ->assertForbidden();

    $this->delete(route('company.permission-profiles.destroy', $recordsProfile))
        ->assertForbidden();

    $this->patch(route('company.branding.update'), [
        'primary_color' => '#123456',
        'accent_color' => '#abcdef',
        'watermark_opacity' => 10,
    ])->assertForbidden();

    expect($company->fresh()->primary_color)->toBe('#4f46e5')
        ->and($company->permissionProfiles()->where('name', 'Novo')->exists())->toBeFalse();
});

test('a member whose profile grants branding updates the visual identity but nothing else', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $member = addManagementTestMember($company, createManagementTestProfile($company, 'Marca', ['company.update_branding']));

    $this->actingAs($member)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('settings-tab-branding', false)
        ->assertDontSee('settings-tab-team', false)
        ->assertDontSee('settings-tab-archive', false);

    $this->patch(route('company.branding.update'), [
        'primary_color' => '#123456',
        'accent_color' => '#abcdef',
        'watermark_opacity' => 10,
    ])->assertRedirect(route('company.show'));

    $this->patch(route('company.details.update'), ['name' => 'Tentativa'])->assertForbidden();

    expect($company->fresh()->primary_color)->toBe('#123456')
        ->and($company->fresh()->name)->toBe('Empresa gerida');
});

test('a profile manager only grants permissions they hold', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $manager = addManagementTestMember($company, createManagementTestProfile($company, 'Gestor de perfis', ['profiles.manage', 'payables.view']));

    $this->actingAs($manager)
        ->post(route('company.permission-profiles.store'), [
            'name' => 'Consulta financeira',
            'permissions' => ['payables.view' => '1'],
        ])
        ->assertSessionHas('status', 'permission-profile-created');

    $this->from(route('company.show'))
        ->post(route('company.permission-profiles.store'), [
            'name' => 'Escalada',
            'permissions' => ['payables.view' => '1', 'company.update_branding' => '1'],
        ])
        ->assertSessionHasErrors('permissions');

    $this->post(route('company.permission-profiles.store'), [
        'name' => 'Escalada implícita',
        'permissions' => ['payables.update_amount' => '1'],
    ])->assertSessionHasErrors('permissions');

    expect($company->permissionProfiles()->where('name', 'Consulta financeira')->exists())->toBeTrue()
        ->and($company->permissionProfiles()->where('name', 'like', 'Escalada%')->exists())->toBeFalse();

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('name="permissions[company.update_branding]" value="1"  disabled', false);
});

test('a profile manager cannot edit profiles stronger than their own or held by administrators', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $managerProfile = createManagementTestProfile($company, 'Gestor de perfis', ['profiles.manage', 'payables.view']);
    $strongerProfile = createManagementTestProfile($company, 'Financeiro completo', ['payables.view', 'payables.update_amount']);
    $adminProfile = createManagementTestProfile($company, 'Perfil do administrador', ['payables.view']);
    $manager = addManagementTestMember($company, $managerProfile);
    addManagementTestMember($company, $adminProfile, Company::ROLE_ADMIN);

    $this->actingAs($manager)
        ->patch(route('company.permission-profiles.update', $strongerProfile), [
            'name' => 'Financeiro reduzido',
            'permissions' => ['payables.view' => '1'],
        ])
        ->assertForbidden();

    $this->delete(route('company.permission-profiles.destroy', $strongerProfile))->assertForbidden();

    $this->patch(route('company.permission-profiles.update', $adminProfile), [
        'name' => 'Administrador rebaixado',
        'permissions' => [],
    ])->assertForbidden();

    $this->patch(route('company.permission-profiles.update', $managerProfile), [
        'name' => 'Gestor de perfis',
        'permissions' => ['profiles.manage' => '1', 'payables.view' => '1', 'payables.update_amount' => '1'],
    ])->assertSessionHasErrors('permissions');

    expect($strongerProfile->fresh()->name)->toBe('Financeiro completo')
        ->and($adminProfile->fresh()->name)->toBe('Perfil do administrador')
        ->and($managerProfile->fresh()->permissions['payables.update_amount'])->toBeFalse();
});

test('a profile assigner cannot escalate themselves or touch stronger members, administrators or the owner', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $assignerProfile = createManagementTestProfile($company, 'Atribuidor', ['team.view', 'team.assign_profiles', 'payables.view']);
    $strongerProfile = createManagementTestProfile($company, 'Financeiro completo', ['payables.view', 'payables.update_amount']);
    $weakProfile = createManagementTestProfile($company, 'Consulta', ['payables.view']);
    $assigner = addManagementTestMember($company, $assignerProfile);
    $strongerMember = addManagementTestMember($company, $strongerProfile);
    $weakMember = addManagementTestMember($company, $weakProfile);
    $administrator = addManagementTestMember($company, $weakProfile, Company::ROLE_ADMIN);

    $this->actingAs($assigner);

    $this->patch(route('company.members.permissions.update', $weakMember), [
        'permission_profile_id' => $strongerProfile->getKey(),
    ])->assertForbidden();

    $this->patch(route('company.members.permissions.update', $assigner), [
        'permission_profile_id' => $weakProfile->getKey(),
    ])->assertForbidden();

    $this->patch(route('company.members.permissions.update', $strongerMember), [
        'permission_profile_id' => $weakProfile->getKey(),
    ])->assertForbidden();

    $this->patch(route('company.members.permissions.update', $administrator), [
        'permission_profile_id' => $weakProfile->getKey(),
    ])->assertForbidden();

    $this->patch(route('company.members.permissions.update', $owner), [
        'permission_profile_id' => $weakProfile->getKey(),
    ])->assertForbidden();

    $this->patch(route('company.members.role.update', $weakMember), ['role' => Company::ROLE_ADMIN])
        ->assertForbidden();

    $this->post(route('company-invitations.store'), ['email' => 'convidado@example.com'])
        ->assertForbidden();

    $this->get(route('company.show'))
        ->assertOk()
        ->assertDontSee('manage-member-'.$strongerMember->id, false)
        ->assertDontSee('manage-member-'.$administrator->id, false)
        ->assertDontSee('manage-member-'.$owner->id, false)
        ->assertSee('manage-member-'.$weakMember->id, false)
        ->assertDontSee('<option value="'.$strongerProfile->id.'"', false);

    expect(managementTestProfileIdOf($company, $weakMember))->toBe($weakProfile->getKey())
        ->and(managementTestProfileIdOf($company, $strongerMember))->toBe($strongerProfile->getKey())
        ->and(managementTestProfileIdOf($company, $assigner))->toBe($assignerProfile->getKey());
});

test('the owner and administrators keep their access while the default profile gains no management access', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $defaultProfile = $company->permissionProfiles()->where('is_default', true)->firstOrFail();
    $weakProfile = createManagementTestProfile($company, 'Consulta', ['payables.view']);
    $administrator = addManagementTestMember($company, $defaultProfile, Company::ROLE_ADMIN);
    $defaultMember = addManagementTestMember($company, $defaultProfile);
    $member = addManagementTestMember($company, $weakProfile);

    $this->actingAs($owner)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('settings-tab-branding', false)
        ->assertSee('settings-tab-profiles', false)
        ->assertSee('settings-tab-team', false)
        ->assertSee('settings-tab-archive', false);

    $this->actingAs($administrator)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('settings-tab-profiles', false)
        ->assertSee('settings-tab-team', false)
        ->assertDontSee('settings-tab-branding', false);

    $this->post(route('company.permission-profiles.store'), [
        'name' => 'Criado pelo administrador',
        'permissions' => ['payables.view' => '1'],
    ])->assertSessionHas('status', 'permission-profile-created');

    $this->patch(route('company.members.permissions.update', $member), [
        'permission_profile_id' => $defaultProfile->getKey(),
    ])->assertSessionHas('status', 'member-permissions-updated');

    $this->patch(route('company.branding.update'), [
        'primary_color' => '#123456',
        'accent_color' => '#abcdef',
        'watermark_opacity' => 10,
    ])->assertForbidden();

    $this->actingAs($defaultMember)
        ->get(route('company.show'))
        ->assertOk()
        ->assertDontSee('settings-tab-team', false)
        ->assertDontSee('settings-tab-profiles', false);

    $this->post(route('company.permission-profiles.store'), ['name' => 'Tentativa', 'permissions' => []])
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('companies.store'), ['name' => 'Nova empresa'])
        ->assertRedirect(route('dashboard'));

    $newDefaultPermissions = Company::where('name', 'Nova empresa')->firstOrFail()
        ->permissionProfiles()->where('is_default', true)->firstOrFail()->permissions;

    expect($newDefaultPermissions['payables.view'])->toBeTrue()
        ->and(array_filter(array_intersect_key(
            $newDefaultPermissions,
            array_flip(CompanyPermissionProfile::managementPermissionKeys()),
        )))->toBe([]);
});

test('the company menu link follows the management permissions', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $teamViewer = addManagementTestMember($company, createManagementTestProfile($company, 'Equipe', ['team.view', 'dashboard.view']));
    $dashboardOnly = addManagementTestMember($company, createManagementTestProfile($company, 'Painel', ['dashboard.view']));

    $this->actingAs($teamViewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('company.show'), false);

    $this->actingAs($dashboardOnly)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('company.show'), false);
});

test('the data migration adds the management permissions as disabled without changing existing ones', function () {
    $owner = User::factory()->create();
    $company = createCompanyForManagementTest($owner);
    $legacyProfile = createManagementTestProfile($company, 'Legado', []);
    $legacyPermissions = array_diff_key(
        CompanyPermissionProfile::allPermissions(),
        array_flip(CompanyPermissionProfile::managementPermissionKeys()),
    );
    $legacyProfile->forceFill(['permissions' => $legacyPermissions])->save();

    (require database_path('migrations/2026_11_23_203510_add_management_permissions_to_company_permission_profiles.php'))->up();

    expect($legacyProfile->fresh()->permissions)->toEqual([
        ...$legacyPermissions,
        ...array_fill_keys(CompanyPermissionProfile::managementPermissionKeys(), false),
    ]);
});
