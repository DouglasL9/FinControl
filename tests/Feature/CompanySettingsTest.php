<?php

use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use App\Models\User;

function createCompanyForSettingsTest(User $owner, string $name = 'Empresa configurada'): Company
{
    $company = Company::create(['name' => $name]);
    $profile = $company->permissionProfiles()->create([
        'name' => 'Acesso completo',
        'permissions' => CompanyPermissionProfile::allPermissions(),
        'is_default' => true,
    ]);
    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $profile->getKey(),
    ]);

    return $company;
}

function addSettingsTestMember(Company $company, User $user, string $role = Company::ROLE_MEMBER): void
{
    $company->users()->attach($user->getKey(), [
        'role' => $role,
        'permission_profile_id' => $company->permissionProfiles()->where('is_default', true)->value('id'),
    ]);
}

test('the owner updates the company registration details', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner);

    $this->actingAs($owner)
        ->patch(route('company.details.update'), [
            'name' => 'Nova Fantasia',
            'legal_name' => 'Nova Fantasia Comércio Ltda.',
            'cnpj' => '12.345.678/0001-90',
            'email' => 'financeiro@example.com',
            'phone' => '(11) 3333-4444',
            'address' => 'Av. Paulista, 1000 - São Paulo/SP',
        ])
        ->assertRedirect(route('company.show', ['section' => 'company']))
        ->assertSessionHas('status', 'company-details-updated');

    $company->refresh();

    expect($company->name)->toBe('Nova Fantasia')
        ->and($company->legal_name)->toBe('Nova Fantasia Comércio Ltda.')
        ->and($company->cnpj)->toBe('12345678000190')
        ->and($company->email)->toBe('financeiro@example.com')
        ->and($company->phone)->toBe('(11) 3333-4444')
        ->and($company->address)->toBe('Av. Paulista, 1000 - São Paulo/SP');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('Dados cadastrais da empresa atualizados.')
        ->assertSee('value="12.345.678/0001-90"', false);
});

test('invalid company details are rejected and reopen the company section', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner);

    $this->actingAs($owner)
        ->from(route('company.show'))
        ->patch(route('company.details.update'), [
            'name' => '',
            'cnpj' => '123',
            'email' => 'nao-e-email',
        ])
        ->assertRedirect(route('company.show'))
        ->assertSessionHasErrorsIn('companyDetails', ['name', 'cnpj', 'email']);

    expect($company->fresh()->name)->toBe('Empresa configurada')
        ->and($company->fresh()->cnpj)->toBeNull();

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('The cnpj field must be 14 digits.')
        ->assertSee('value="123"', false)
        ->assertSee("section: 'company'", false);
});

test('members cannot change the company details and only see them read only', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner);
    $company->update(['legal_name' => 'Razão Social Original']);
    $admin = User::factory()->create();
    addSettingsTestMember($company, $admin, Company::ROLE_ADMIN);

    $this->actingAs($admin)
        ->patch(route('company.details.update'), ['name' => 'Tentativa'])
        ->assertForbidden();

    expect($company->fresh()->name)->toBe('Empresa configurada');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('Razão Social Original')
        ->assertDontSee(route('company.details.update'), false)
        ->assertSee('Perfis de acesso')
        ->assertSee('Equipe')
        ->assertDontSee('Identidade visual')
        ->assertDontSee('Arquivar empresa');
});

test('guests are redirected to login when updating company details', function () {
    $this->patch(route('company.details.update'), ['name' => 'Empresa'])
        ->assertRedirect(route('login'));
});

test('settings sections follow the member permissions', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner);
    $member = User::factory()->create();
    addSettingsTestMember($company, $member);

    $this->actingAs($owner)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSeeInOrder(['Empresa', 'Identidade visual', 'Perfis de acesso', 'Equipe', 'Unidades e fornecedores', 'Arquivar empresa'])
        ->assertSee(route('company.details.update'), false);

    $this->actingAs($member)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('Unidades e fornecedores')
        ->assertDontSee('Perfis de acesso')
        ->assertDontSee('settings-tab-team', false)
        ->assertDontSee('Identidade visual')
        ->assertDontSee('Arquivar empresa');
});

test('the requested settings section opens first when the user may access it', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner);
    $member = User::factory()->create();
    addSettingsTestMember($company, $member);

    $this->actingAs($owner)
        ->get(route('company.show', ['section' => 'profiles']))
        ->assertOk()
        ->assertSee("section: 'profiles'", false);

    $this->actingAs($member)
        ->get(route('company.show', ['section' => 'archive']))
        ->assertOk()
        ->assertSee("section: 'company'", false);
});

test('archiving asks for confirmation in a dialog and reopens it after a wrong password', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner, 'Empresa arquivada');
    createCompanyForSettingsTest($owner, 'Empresa ativa');

    $this->actingAs($owner)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('<dialog x-ref="archiveCompany"', false)
        ->assertSee('Sim, arquivar empresa')
        ->assertSee('x-init="false &&', false);

    $this->from(route('company.show'))
        ->post(route('companies.archive', $company), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee("section: 'archive'", false)
        ->assertSee('x-init="true &&', false);

    expect($company->fresh()->is_active)->toBeTrue();
});

test('an archived company is no longer offered as an active company', function () {
    $owner = User::factory()->create();
    $company = createCompanyForSettingsTest($owner, 'Empresa arquivada');
    $activeCompany = createCompanyForSettingsTest($owner, 'Empresa ativa');

    $this->actingAs($owner)
        ->post(route('companies.archive', $company), ['password' => 'password'])
        ->assertRedirect(route('dashboard'));

    expect($owner->fresh()->currentCompany()->is($activeCompany))->toBeTrue();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reativar: Empresa arquivada')
        ->assertDontSee('>Empresa arquivada</option>', false);

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('Empresa ativa')
        ->assertDontSee('Arquivar Empresa arquivada?');
});
