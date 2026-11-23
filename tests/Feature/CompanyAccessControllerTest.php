<?php

use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use App\Models\User;

function createCompanyForAccessTest(User $owner, string $name = 'Empresa de acesso'): Company
{
    $company = Company::create(['name' => $name]);
    $defaultProfile = $company->permissionProfiles()->create([
        'name' => 'Acesso completo',
        'permissions' => CompanyPermissionProfile::allPermissions(),
        'is_default' => true,
    ]);

    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $defaultProfile->getKey(),
    ]);

    return $company;
}

function addMemberForAccessTest(Company $company, User $user): void
{
    $defaultProfileId = $company->permissionProfiles()->where('is_default', true)->value('id');

    $company->users()->attach($user->getKey(), [
        'role' => Company::ROLE_MEMBER,
        'permission_profile_id' => $defaultProfileId,
    ]);
}

test('permission profiles allow selected views and deny unassigned operations', function () {
    $owner = User::factory()->create();
    $company = createCompanyForAccessTest($owner);
    $unit = $company->units()->create(['name' => 'Unidade restrita']);
    $supplier = $company->suppliers()->create(['name' => 'Fornecedor restrito']);
    $member = User::factory()->create();
    addMemberForAccessTest($company, $member);

    $this->actingAs($owner)
        ->post(route('company.permission-profiles.store'), [
            'name' => 'Somente consulta',
            'permissions' => [
                'dashboard.view' => '1',
                'payables.view' => '1',
                'units.view' => '1',
                'suppliers.view' => '1',
            ],
        ])
        ->assertRedirect(route('company.show'))
        ->assertSessionHas('status', 'permission-profile-created');

    $profile = $company->permissionProfiles()->where('name', 'Somente consulta')->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('company.members.permissions.update', $member), [
            'permission_profile_id' => $profile->getKey(),
        ])
        ->assertSessionHas('status', 'member-permissions-updated');

    expect($profile->permissions['payables.view'])->toBeTrue()
        ->and($profile->permissions['payables.update_amount'])->toBeFalse()
        ->and($profile->permissions['units.create'])->toBeFalse()
        ->and((int) $member->fresh()->companies()->whereKey($company)->first()->pivot->permission_profile_id)
        ->toBe((int) $profile->getKey());

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Olá, '.$member->name)
        ->assertDontSee('Acesso ao painel não habilitado');

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertDontSee('Novo contrato mensal')
        ->assertDontSee('Pausar contrato');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('Unidade restrita')
        ->assertSee('Fornecedor restrito')
        ->assertDontSee('Adicionar unidade')
        ->assertDontSee('Cadastrar fornecedor')
        ->assertDontSee('Membros da empresa');

    $this->post(route('company.units.store'), ['unit_name' => 'Unidade proibida'])
        ->assertForbidden();

    $this->patch(route('company.units.toggle', $unit))
        ->assertForbidden();

    $this->post(route('company.suppliers.store'), ['name' => 'Fornecedor proibido'])
        ->assertForbidden();

    $this->patch(route('company.suppliers.toggle', $supplier))
        ->assertForbidden();

    $this->post(route('payables.contracts.store'), [])
        ->assertForbidden();

    $this->patch(route('payables.contracts.toggle', 1))
        ->assertForbidden();

    $this->patch(route('payables.update', 1), ['amount' => 999])
        ->assertForbidden();

    $this->patch(route('payables.payment.toggle', 1))
        ->assertForbidden();
});

test('administrators manage member profiles but only owners can appoint administrators or invite users', function () {
    $owner = User::factory()->create();
    $company = createCompanyForAccessTest($owner);
    $administrator = User::factory()->create();
    $member = User::factory()->create();
    $secondAdministrator = User::factory()->create();
    addMemberForAccessTest($company, $administrator);
    addMemberForAccessTest($company, $member);
    addMemberForAccessTest($company, $secondAdministrator);

    $this->actingAs($owner)
        ->patch(route('company.members.role.update', $administrator), ['role' => Company::ROLE_ADMIN])
        ->assertSessionHas('status', 'member-role-updated');

    $this->patch(route('company.members.role.update', $secondAdministrator), ['role' => Company::ROLE_ADMIN])
        ->assertSessionHas('status', 'member-role-updated');

    $this->actingAs($administrator)
        ->post(route('company.permission-profiles.store'), [
            'name' => 'Consulta administrativa',
            'permissions' => ['payables.view' => '1'],
        ])
        ->assertSessionHas('status', 'permission-profile-created');

    $profile = $company->permissionProfiles()->where('name', 'Consulta administrativa')->firstOrFail();

    $this->patch(route('company.members.permissions.update', $member), [
        'permission_profile_id' => $profile->getKey(),
    ])->assertSessionHas('status', 'member-permissions-updated');

    $this->patch(route('company.members.permissions.update', $secondAdministrator), [
        'permission_profile_id' => $profile->getKey(),
    ])->assertForbidden();

    $this->patch(route('company.members.role.update', $member), ['role' => Company::ROLE_ADMIN])
        ->assertForbidden();

    $this->post(route('company-invitations.store'), ['email' => 'new-user@example.com'])
        ->assertForbidden();

    $this->patch(route('company.members.role.update', $owner), ['role' => Company::ROLE_MEMBER])
        ->assertForbidden();

    expect((int) $member->fresh()->companies()->whereKey($company)->first()->pivot->permission_profile_id)
        ->toBe((int) $profile->getKey())
        ->and($company->users()->whereKey($administrator)->first()->pivot->role)
        ->toBe(Company::ROLE_ADMIN);
});

test('permission profiles cannot be assigned or edited across companies', function () {
    $firstOwner = User::factory()->create();
    $firstCompany = createCompanyForAccessTest($firstOwner, 'Empresa A');
    $member = User::factory()->create();
    addMemberForAccessTest($firstCompany, $member);

    $secondOwner = User::factory()->create();
    $secondCompany = createCompanyForAccessTest($secondOwner, 'Empresa B');
    $foreignProfile = $secondCompany->permissionProfiles()->create([
        'name' => 'Perfil externo',
        'permissions' => CompanyPermissionProfile::allPermissions(),
    ]);

    $this->actingAs($firstOwner)
        ->from(route('company.show'))
        ->patch(route('company.members.permissions.update', $member), [
            'permission_profile_id' => $foreignProfile->getKey(),
        ])
        ->assertSessionHasErrors('permission_profile_id');

    $this->patch(route('company.permission-profiles.update', $foreignProfile), [
        'name' => 'Tentativa externa',
        'permissions' => [],
    ])->assertNotFound();

    $this->patch(route('company.members.permissions.update', $secondOwner), [
        'permission_profile_id' => $firstCompany->permissionProfiles()->where('is_default', true)->value('id'),
    ])->assertNotFound();

    expect((int) $member->fresh()->companies()->whereKey($firstCompany)->first()->pivot->permission_profile_id)
        ->toBe((int) $firstCompany->permissionProfiles()->where('is_default', true)->value('id'));
});

test('permission profiles reject unknown actions and protect the default profile', function () {
    $owner = User::factory()->create();
    $company = createCompanyForAccessTest($owner);
    $defaultProfile = $company->permissionProfiles()->where('is_default', true)->firstOrFail();

    $this->actingAs($owner)
        ->from(route('company.show'))
        ->post(route('company.permission-profiles.store'), [
            'name' => 'Elevação indevida',
            'permissions' => [
                'payables.view' => '1',
                'team.manage' => '1',
            ],
            'is_default' => true,
        ])
        ->assertSessionHasErrors('permissions');

    $this->patch(route('company.permission-profiles.update', $defaultProfile), [
        'name' => 'Acesso reduzido',
        'permissions' => [],
    ])->assertForbidden();

    $this->delete(route('company.permission-profiles.destroy', $defaultProfile))
        ->assertForbidden();

    $this->assertDatabaseMissing('company_permission_profiles', [
        'company_id' => $company->getKey(),
        'name' => 'Elevação indevida',
    ]);

    expect($defaultProfile->fresh()->is_default)->toBeTrue()
        ->and($defaultProfile->fresh()->permissions['payables.view'])->toBeTrue();
});
