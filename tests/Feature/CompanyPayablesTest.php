<?php

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanyPermissionProfile;
use App\Models\Payable;
use App\Models\PayableHistory;
use App\Models\RecurringPayable;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use App\Services\RecurringPayableGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function createCompanyWithMember(User $user, string $name, string $role = Company::ROLE_OWNER): Company
{
    $company = Company::create(['name' => $name]);
    $profile = createDefaultCompanyAccessProfile($company);
    $company->users()->attach($user->getKey(), [
        'role' => $role,
        'permission_profile_id' => $profile->getKey(),
    ]);

    return $company;
}

function createDefaultCompanyAccessProfile(Company $company): CompanyPermissionProfile
{
    return $company->createInitialPermissionProfiles();
}

function addCompanyMember(Company $company, User $user, string $role = Company::ROLE_MEMBER): void
{
    $profile = $company->permissionProfiles()->where('is_default', true)->firstOrFail();
    $company->users()->attach($user->getKey(), [
        'role' => $role,
        'permission_profile_id' => $profile->getKey(),
    ]);
}

function createUnitForCompany(Company $company, string $name = 'Unidade central'): Unit
{
    return $company->units()->create(['name' => $name]);
}

function createSupplierForCompany(Company $company, string $name = 'Fornecedor'): Supplier
{
    return $company->suppliers()->create(['name' => $name]);
}

test('a user can create another company and it becomes the active company', function () {
    $user = User::factory()->create();
    $firstCompany = createCompanyWithMember($user, 'Primeira empresa');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('value="create"', false)
        ->assertSee('Criar empresa');

    $response = $this->actingAs($user)
        ->post(route('companies.store'), ['name' => 'Segunda empresa']);

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'company-created');

    $secondCompany = Company::query()->where('name', 'Segunda empresa')->firstOrFail();

    expect($user->fresh()->currentCompany()->is($secondCompany))->toBeTrue()
        ->and($user->fresh()->companies()->whereKey($firstCompany->id)->exists())->toBeTrue();
});

test('company creation validation reopens the navigation modal', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa atual');

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('companies.store'), ['name' => str_repeat('a', 256)])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrorsIn('companyCreation', ['name']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('style="display: block;"', false);
});

test('a user can switch between companies but cannot select a company they do not belong to', function () {
    $user = User::factory()->create();
    $firstCompany = createCompanyWithMember($user, 'Primeira empresa');
    $secondCompany = createCompanyWithMember($user, 'Segunda empresa');
    $otherUser = User::factory()->create();
    $otherCompany = createCompanyWithMember($otherUser, 'Empresa externa');

    $this->actingAs($user)
        ->post(route('companies.switch'), ['company_id' => $secondCompany->id])
        ->assertRedirect(route('dashboard'));

    expect($user->currentCompany()->is($secondCompany))->toBeTrue();

    $this->actingAs($user)
        ->post(route('companies.switch'), ['company_id' => $otherCompany->id])
        ->assertNotFound();

    expect($user->currentCompany()->is($secondCompany))->toBeTrue()
        ->and($user->companies()->whereKey($firstCompany->id)->exists())->toBeTrue();
});

test('a company owner can archive a company without deleting its data or allowing access', function () {
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa arquivada');
    $member = User::factory()->create();
    addCompanyMember($company, $member);
    $otherCompany = createCompanyWithMember($owner, 'Empresa ativa');
    $outsider = User::factory()->create();
    $unit = createUnitForCompany($company);
    $supplier = createSupplierForCompany($company);
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'supplier_id' => $supplier->id,
        'created_by_user_id' => $owner->id,
        'description' => 'Internet',
        'provider' => $supplier->name,
        'amount' => 150,
        'due_day' => today()->day,
        'starts_on' => today()->toDateString(),
        'is_active' => true,
    ]);
    expect($company->fresh()->is_active)->toBeTrue()
        ->and($contract->is_active)->toBeTrue();
    expect(app(RecurringPayableGenerator::class)->generateFor($contract))->toBe(3);
    $token = str_repeat('c', 64);
    $invitation = CompanyInvitation::create([
        'company_id' => $company->id,
        'invited_by_user_id' => $owner->id,
        'email' => $outsider->email,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($owner)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('Arquivar empresa')
        ->assertSee('Confirme sua senha atual');

    $this->actingAs($owner)
        ->post(route('companies.archive', $company), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect($company->fresh()->is_active)->toBeTrue();

    $this->post(route('companies.archive', $company), ['password' => 'password'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'company-archived');

    expect($company->fresh()->is_active)->toBeFalse()
        ->and($owner->fresh()->currentCompany()->is($otherCompany))->toBeTrue()
        ->and($member->fresh()->currentCompany())->toBeNull()
        ->and($contract->fresh())->not->toBeNull()
        ->and($supplier->fresh())->not->toBeNull()
        ->and($unit->fresh())->not->toBeNull()
        ->and($contract->payables()->count())->toBe(3)
        ->and($invitation->fresh())->not->toBeNull()
        ->and(app(RecurringPayableGenerator::class)->generateFor($contract->fresh()))->toBe(0);

    $this->actingAs($outsider)
        ->get(route('company-invitations.show', $token))
        ->assertNotFound();

    $this->post(route('company-invitations.accept', $token))
        ->assertNotFound();

    expect($company->users()->whereKey($outsider->id)->exists())->toBeFalse();
});

test('only an owner can archive a company', function () {
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa');
    $member = User::factory()->create();
    addCompanyMember($company, $member);

    $this->actingAs($member)
        ->post(route('companies.archive', $company), ['password' => 'password'])
        ->assertForbidden();

    expect($company->fresh()->is_active)->toBeTrue();
});

test('an owner can restore an archived company from the company selector', function () {
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa arquivada');
    $member = User::factory()->create();
    addCompanyMember($company, $member);

    $this->actingAs($owner)
        ->post(route('companies.archive', $company), ['password' => 'password'])
        ->assertSessionHas('status', 'company-archived');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reativar: Empresa arquivada');

    $this->actingAs($member)
        ->post(route('companies.switch'), ['company_id' => $company->id])
        ->assertNotFound();

    expect($company->fresh()->is_active)->toBeFalse();

    $this->actingAs($owner)
        ->post(route('companies.switch'), ['company_id' => $company->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'company-restored');

    expect($company->fresh()->is_active)->toBeTrue()
        ->and($owner->fresh()->currentCompany()->is($company))->toBeTrue();
});

test('a company member can register and view a unit', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa', Company::ROLE_MEMBER);

    $this->actingAs($user)
        ->post(route('company.units.store'), ['unit_name' => 'Filial Campinas'])
        ->assertRedirect(route('company.show'))
        ->assertSessionHas('status', 'unit-created');

    expect($company->units()->where('name', 'Filial Campinas')->exists())->toBeTrue();

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('Filial Campinas')
        ->assertDontSee('Criar outra empresa')
        ->assertDontSee('Arquivar empresa');
});

test('unit and supplier create and edit forms are available in modals', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);
    $supplier = createSupplierForCompany($company);

    $response = $this->actingAs($user)
        ->get(route('company.show'))
        ->assertOk()
        ->assertSee('x-data="{}"', false)
        ->assertSee("open-modal', 'create-unit'", false)
        ->assertSee("open-modal', 'edit-unit-{$unit->id}'", false)
        ->assertSee("open-modal', 'create-supplier'", false)
        ->assertSee("open-modal', 'edit-supplier-{$supplier->id}'", false)
        ->assertSee('x-ref="postalCode"', false)
        ->assertSee('x-on:input.debounce.500ms="lookupCep"', false);

    expect(substr_count($response->getContent(), 'x-data="unitAddressLookup"'))->toBe(2);
});

test('unit validation reopens its modal and retains entered values', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa');

    $this->actingAs($user)
        ->from(route('company.show'))
        ->post(route('company.units.store'), [
            '_modal' => 'create-unit',
            'unit_name' => 'Filial Campinas',
            'postal_code' => '123',
        ])
        ->assertSessionHasErrors('postal_code');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('show: true', false)
        ->assertSee('value="Filial Campinas"', false)
        ->assertSee('value="123"', false);
});

test('supplier validation reopens its modal and retains entered values', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa');

    $this->actingAs($user)
        ->from(route('company.show'))
        ->post(route('company.suppliers.store'), [
            '_modal' => 'create-supplier',
            'name' => 'Fornecedor Alfa',
            'cnpj' => '123',
        ])
        ->assertSessionHasErrors('cnpj');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('show: true', false)
        ->assertSee('value="Fornecedor Alfa"', false)
        ->assertSee('value="123"', false);
});

test('a company member can edit and archive a unit without losing its contracts', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $user->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_day' => 10,
        'starts_on' => today()->toDateString(),
    ]);

    $this->actingAs($user)
        ->patch(route('company.units.update', $unit), [
            'unit_name' => 'Filial Campinas',
            'postal_code' => '13010-000',
            'street' => 'Rua das Flores',
            'number' => '123A',
            'complement' => 'Sala 4',
            'neighborhood' => 'Centro',
            'city' => 'Campinas',
            'state' => 'sp',
            'phone' => '(19) 3000-0000',
            'company_id' => 999999,
            'is_active' => false,
        ])
        ->assertRedirect(route('company.show'))
        ->assertSessionHas('status', 'unit-updated');

    expect($unit->fresh()->postal_code)->toBe('13010000')
        ->and($unit->fresh()->state)->toBe('SP')
        ->and($unit->fresh()->street)->toBe('Rua das Flores')
        ->and($unit->fresh()->is_active)->toBeTrue();

    $this->from(route('company.show'))
        ->patch(route('company.units.update', $unit), [
            'unit_name' => 'Filial Campinas',
            'postal_code' => 'CEP 13010000',
        ])
        ->assertSessionHasErrors('postal_code');

    expect($unit->fresh()->postal_code)->toBe('13010000');

    $this->patch(route('company.units.toggle', $unit))
        ->assertSessionHas('status', 'unit-archived');

    expect($unit->fresh()->is_active)->toBeFalse()
        ->and($contract->fresh())->not->toBeNull();

    $supplier = createSupplierForCompany($company);

    $this->from(route('payables.index'))
        ->post(route('payables.contracts.store'), [
            'description' => 'Nova internet',
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'amount' => 80,
            'due_day' => today()->day,
            'starts_on' => today()->toDateString(),
        ])
        ->assertSessionHasErrors('unit_id');

    $this->patch(route('company.units.toggle', $unit))
        ->assertSessionHas('status', 'unit-activated');

    expect($unit->fresh()->is_active)->toBeTrue();
});

test('a company member can manage suppliers and contracts keep their supplier history', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);

    $this->actingAs($user)
        ->post(route('company.suppliers.store'), [
            'name' => 'Provedor Alfa',
            'cnpj' => '12.345.678/0001-90',
            'contact_name' => 'Ana Silva',
            'email' => 'ana@provedor.test',
            'phone' => '(11) 3000-0000',
            'address' => 'Rua Central, 10',
            'notes' => 'Atendimento comercial',
        ])
        ->assertRedirect(route('company.show'))
        ->assertSessionHas('status', 'supplier-created');

    $supplier = $company->suppliers()->firstOrFail();
    $otherCompany = Company::create(['name' => 'Outra empresa']);

    expect($supplier->cnpj)->toBe('12345678000190')
        ->and($supplier->formattedCnpj())->toBe('12.345.678/0001-90');

    $this->from(route('company.show'))
        ->post(route('company.suppliers.store'), [
            'name' => 'Fornecedor inválido',
            'cnpj' => 'abc12.345.678/0001-90',
        ])
        ->assertSessionHasErrors('cnpj');

    $this->from(route('company.show'))
        ->post(route('company.suppliers.store'), [
            'name' => 'Outro provedor',
            'cnpj' => '12.345.678/0001-90',
        ])
        ->assertSessionHasErrors('cnpj');

    $this->post(route('payables.contracts.store'), [
        'description' => 'Internet',
        'supplier_id' => $supplier->id,
        'unit_id' => $unit->id,
        'amount' => 150,
        'due_day' => today()->day,
        'starts_on' => today()->toDateString(),
    ])->assertRedirect(route('payables.index'));

    $contract = $company->recurringPayables()->firstOrFail();

    expect($contract->supplier_id)->toBe($supplier->id)
        ->and($contract->provider)->toBe('Provedor Alfa')
        ->and($contract->payables()->count())->toBe(3)
        ->and($contract->payables()->orderBy('due_date')->first()->provider)->toBe('Provedor Alfa');

    $this->patch(route('company.suppliers.update', $supplier), [
        'name' => 'Provedor Alfa <script>alert(1)</script>',
        'cnpj' => '12.345.678/0001-90',
        'contact_name' => 'Ana Silva',
        'email' => 'ana@provedor.test',
        'phone' => '(11) 3000-0000',
        'address' => 'Rua Central, 10',
        'notes' => 'Contrato atualizado',
        'company_id' => $otherCompany->id,
        'is_active' => false,
    ])->assertSessionHas('status', 'supplier-updated');

    expect($supplier->fresh()->company_id)->toBe($company->id)
        ->and($supplier->fresh()->is_active)->toBeTrue()
        ->and($contract->fresh()->provider)->toBe('Provedor Alfa <script>alert(1)</script>')
        ->and($contract->payables()->orderBy('due_date')->first()->provider)->toBe('Provedor Alfa');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);

    $this->get(route('payables.index', ['search' => 'Provedor Alfa']))
        ->assertOk()
        ->assertSee('Internet');

    expect(app(RecurringPayableGenerator::class)->generateFor(
        $contract->fresh(),
        Carbon::parse(today()->addMonths(3)->toDateString()),
    ))->toBe(3)
        ->and($contract->payables()->orderByDesc('due_date')->first()->provider)->toBe('Provedor Alfa <script>alert(1)</script>');

    $this->patch(route('company.suppliers.toggle', $supplier))
        ->assertSessionHas('status', 'supplier-archived');

    $this->from(route('payables.index'))
        ->post(route('payables.contracts.store'), [
            'description' => 'Telefonia',
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'amount' => 80,
            'due_day' => today()->day,
            'starts_on' => today()->toDateString(),
        ])
        ->assertSessionHasErrors('supplier_id');

    $this->assertDatabaseCount('recurring_payables', 1);
});

test('a company member cannot manage another company supplier', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa A');
    $otherUser = User::factory()->create();
    $otherCompany = createCompanyWithMember($otherUser, 'Empresa B');
    $supplier = createSupplierForCompany($otherCompany, 'Fornecedor externo');

    $this->actingAs($user)
        ->patch(route('company.suppliers.update', $supplier), [
            'name' => 'Alteração indevida',
        ])
        ->assertNotFound();

    expect($supplier->fresh()->name)->toBe('Fornecedor externo');
});

test('a contract cannot use a unit from another company', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa A');
    $supplier = createSupplierForCompany($company);
    $otherUser = User::factory()->create();
    $otherCompany = createCompanyWithMember($otherUser, 'Empresa B');
    $otherUnit = createUnitForCompany($otherCompany);

    $this->actingAs($user)
        ->post(route('payables.contracts.store'), [
            'description' => 'Internet',
            'supplier_id' => $supplier->id,
            'unit_id' => $otherUnit->id,
            'amount' => 150,
            'due_day' => 10,
            'starts_on' => today()->toDateString(),
        ])
        ->assertSessionHasErrors('unit_id');

    $this->assertDatabaseCount('recurring_payables', 0);
});

test('a company member can create a contract for a registered unit', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $supplier = createSupplierForCompany($company, 'Provedor');
    $logoPath = 'companies/logo.png';
    Storage::disk('public')->put($logoPath, 'logo');
    $company->update(['logo_path' => $logoPath]);
    $unit = createUnitForCompany($company, 'Filial Recife');

    $this->actingAs($user)
        ->post(route('payables.contracts.store'), [
            'description' => 'Internet',
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'amount' => 125,
            'due_day' => today()->day,
            'starts_on' => today()->toDateString(),
        ])
        ->assertRedirect(route('payables.index'))
        ->assertSessionHas('status', 'contract-created');

    $contract = $company->recurringPayables()->firstOrFail();

    expect($contract->unit_id)->toBe($unit->id)
        ->and($contract->payables()->count())->toBe(3)
        ->and($contract->payables()->where('unit_id', $unit->id)->count())->toBe(3);

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('Filial Recife')
        ->assertSee('Contratos e parcelas')
        ->assertSee('Ver parcelas')
        ->assertSee('1 contrato')
        ->assertSee(Storage::disk('public')->url($logoPath), false)
        ->assertSee('alt="Empresa"', false)
        ->assertSee('<dialog', false)
        ->assertSee('aria-modal="true"', false);

    $this->actingAs($user)
        ->post(route('payables.contracts.store'), [
            'description' => 'Telefonia',
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'amount' => 80,
            'due_day' => today()->day,
            'starts_on' => today()->toDateString(),
        ])
        ->assertRedirect(route('payables.index'))
        ->assertSessionHas('status', 'contract-created');

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('2 contratos');

    $this->actingAs($user)
        ->post(route('payables.contracts.store'), [
            'description' => 'Licença futura',
            'supplier_id' => $supplier->id,
            'unit_id' => $unit->id,
            'amount' => 80,
            'due_day' => today()->day,
            'starts_on' => today()->addMonths(6)->toDateString(),
        ])
        ->assertRedirect(route('payables.index'))
        ->assertSessionHas('status', 'contract-created');

    expect($company->recurringPayables()->where('description', 'Licença futura')->firstOrFail()->payables()->count())->toBe(0);

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('3 contratos');
});

test('payment filters show only matching contract groups and installments', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);

    foreach ([
        ['description' => 'Internet paga', 'paid_at' => now()],
        ['description' => 'Internet pendente', 'paid_at' => null],
    ] as $payment) {
        $contract = RecurringPayable::create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'created_by_user_id' => $user->id,
            'description' => $payment['description'],
            'provider' => 'Provedor',
            'amount' => 150,
            'due_day' => today()->day,
            'starts_on' => today()->toDateString(),
        ]);

        Payable::create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'recurring_payable_id' => $contract->id,
            'description' => $payment['description'],
            'provider' => 'Provedor',
            'amount' => 150,
            'due_date' => today()->toDateString(),
            'paid_at' => $payment['paid_at'],
        ]);
    }

    $this->actingAs($user)
        ->get(route('payables.index', ['status' => 'paid']))
        ->assertOk()
        ->assertSee('Internet paga')
        ->assertDontSee('Internet pendente')
        ->assertSee('Valor bloqueado após pagamento');
});

test('a company member can filter contracts by unit', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $northUnit = createUnitForCompany($company, 'Sede Norte');
    $southUnit = createUnitForCompany($company, 'Sede Sul');

    foreach ([
        ['unit' => $northUnit, 'description' => 'Internet Norte'],
        ['unit' => $southUnit, 'description' => 'Internet Sul'],
    ] as $contractData) {
        $contract = RecurringPayable::create([
            'company_id' => $company->id,
            'unit_id' => $contractData['unit']->id,
            'created_by_user_id' => $user->id,
            'description' => $contractData['description'],
            'provider' => 'Provedor',
            'amount' => 150,
            'due_day' => today()->day,
            'starts_on' => today()->toDateString(),
        ]);

        Payable::create([
            'company_id' => $company->id,
            'unit_id' => $contractData['unit']->id,
            'recurring_payable_id' => $contract->id,
            'description' => $contractData['description'],
            'provider' => 'Provedor',
            'amount' => 150,
            'due_date' => today()->toDateString(),
        ]);
    }

    $this->actingAs($user)
        ->get(route('payables.index', ['unit_id' => $northUnit->id]))
        ->assertOk()
        ->assertSee('Internet Norte')
        ->assertDontSee('Internet Sul')
        ->assertSee('Todas as sedes');
});

test('a company member cannot filter contracts by another company unit', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa');
    $otherUser = User::factory()->create();
    $otherCompany = createCompanyWithMember($otherUser, 'Outra empresa');
    $otherUnit = createUnitForCompany($otherCompany);

    $this->actingAs($user)
        ->get(route('payables.index', ['unit_id' => $otherUnit->id]))
        ->assertSessionHasErrors('unit_id');
});

test('a company owner can search for registered users outside the current company', function () {
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa');
    $member = User::factory()->create(['name' => 'Membro Atual']);
    addCompanyMember($company, $member);
    $candidate = User::factory()->create([
        'name' => 'Pessoa Cadastrada',
        'email' => 'pessoa.cadastrada@example.com',
    ]);
    createCompanyWithMember($candidate, 'Outra empresa');

    $this->actingAs($owner)
        ->getJson(route('company-invitations.users.search', ['search' => 'Pessoa Cadastrada']))
        ->assertOk()
        ->assertExactJson([
            [
                'id' => $candidate->id,
                'name' => 'Pessoa Cadastrada',
                'email' => 'pessoa.cadastrada@example.com',
            ],
        ]);

    $this->getJson(route('company-invitations.users.search', ['search' => '%%']))
        ->assertOk()
        ->assertExactJson([]);
});

test('a company owner can invite a user who already belongs to another company', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa convidante');
    $existingMember = User::factory()->create();
    createCompanyWithMember($existingMember, 'Outra empresa');

    $response = $this->actingAs($owner)
        ->from(route('company.show'))
        ->post(route('company-invitations.store'), ['email' => $existingMember->email]);

    $response
        ->assertRedirect(route('company.show'))
        ->assertSessionHas('status', 'invitation-sent');

    $this->assertDatabaseHas('company_invitations', [
        'company_id' => $company->id,
        'email' => $existingMember->email,
        'accepted_at' => null,
    ]);

    Notification::assertSentOnDemand(CompanyInvitationNotification::class);
});

test('a member cannot invite another user to the company', function () {
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa');
    $member = User::factory()->create();
    addCompanyMember($company, $member);

    $this->actingAs($member)
        ->getJson(route('company-invitations.users.search', ['search' => 'new-member']))
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('company-invitations.store'), ['email' => 'new-member@example.com'])
        ->assertForbidden();

    $this->assertDatabaseMissing('company_invitations', [
        'company_id' => $company->id,
        'email' => 'new-member@example.com',
    ]);
});

test('a verified user can accept an invitation while belonging to another company', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa atual');
    $invitedCompany = Company::create(['name' => 'Nova empresa']);
    createDefaultCompanyAccessProfile($invitedCompany);
    $token = str_repeat('a', 64);

    $invitation = CompanyInvitation::create([
        'company_id' => $invitedCompany->id,
        'invited_by_user_id' => null,
        'email' => $user->email,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('company-invitations.show', $token))
        ->assertOk();

    $response = $this->post(route('company-invitations.accept', $token));

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'company-joined');

    $membership = $user->fresh()->companies()->whereKey($invitedCompany->id)->first();

    expect($membership->pivot->role)
        ->toBe(Company::ROLE_MEMBER)
        ->and((int) $membership->pivot->permission_profile_id)
        ->toBe((int) $invitedCompany->permissionProfiles()->where('is_invite_default', true)->value('id'))
        ->and($invitedCompany->allows($membership, 'payables.view'))
        ->toBeTrue()
        ->and($user->fresh()->currentCompany()->is($invitedCompany))->toBeTrue();

    $this->assertNotNull($invitation->fresh()->accepted_at);
});

test('an invitation cannot be accepted by a user with a different email address', function () {
    $invitedCompany = Company::create(['name' => 'Empresa convidante']);
    createDefaultCompanyAccessProfile($invitedCompany);
    $invitedUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = str_repeat('b', 64);

    $invitation = CompanyInvitation::create([
        'company_id' => $invitedCompany->id,
        'invited_by_user_id' => null,
        'email' => $invitedUser->email,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($otherUser)
        ->post(route('company-invitations.accept', $token))
        ->assertForbidden();

    expect($invitation->fresh()->accepted_at)->toBeNull()
        ->and($invitedCompany->users()->whereKey($otherUser->id)->exists())->toBeFalse();
});

test('a user cannot update a payable belonging to another company', function () {
    $user = User::factory()->create();
    createCompanyWithMember($user, 'Empresa A');
    $otherUser = User::factory()->create();
    $otherCompany = createCompanyWithMember($otherUser, 'Empresa B');
    $unit = createUnitForCompany($otherCompany);
    $contract = RecurringPayable::create([
        'company_id' => $otherCompany->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $otherUser->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_day' => 1,
        'starts_on' => today()->toDateString(),
        'is_active' => true,
    ]);
    $payable = Payable::create([
        'company_id' => $otherCompany->id,
        'unit_id' => $unit->id,
        'recurring_payable_id' => $contract->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_date' => today()->addWeek(),
    ]);

    $this->actingAs($user)
        ->patch(route('payables.update', $payable), ['amount' => 200])
        ->assertNotFound();

    expect((float) $payable->fresh()->amount)->toBe(150.0)
        ->and($payable->histories()->exists())->toBeFalse();
});

test('a company member can view the actor and values for a payable amount change', function () {
    $user = User::factory()->create(['name' => 'Ana <script>alert(1)</script>']);
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $user->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_day' => 1,
        'starts_on' => today()->toDateString(),
        'is_active' => true,
    ]);
    $payable = Payable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'recurring_payable_id' => $contract->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_date' => today()->addWeek(),
    ]);

    $this->actingAs($user)
        ->patch(route('payables.update', $payable), ['amount' => 200])
        ->assertSessionHas('status', 'payable-updated');

    $history = $payable->histories()->firstOrFail();

    expect($history->action)->toBe(PayableHistory::ACTION_AMOUNT_UPDATED)
        ->and($history->old_amount)->toBe('150.00')
        ->and($history->new_amount)->toBe('200.00')
        ->and($history->user_id)->toBe($user->id)
        ->and($history->actor_name)->toBe('Ana <script>alert(1)</script>');

    $this->patch(route('payables.update', $payable), ['amount' => '200.00'])
        ->assertSessionHas('status', 'payable-updated');

    $this->assertDatabaseCount('payable_histories', 1);

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('Histórico de alterações (1)')
        ->assertSee('Valor alterado')
        ->assertSee('R$ 150,00')
        ->assertSee('R$ 200,00')
        ->assertSee('Ana &lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('a company member can mark a payable paid and reopen it', function () {
    $user = User::factory()->create(['name' => 'Bruno Operador']);
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $user->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_day' => 1,
        'starts_on' => today()->toDateString(),
        'is_active' => true,
    ]);
    $payable = Payable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'recurring_payable_id' => $contract->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_date' => today()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('payables.index'))
        ->assertOk()
        ->assertDontSee('confirm-reopen-title-'.$payable->id, false);

    $this->actingAs($user)
        ->patch(route('payables.payment.toggle', $payable))
        ->assertSessionHas('status', 'payable-paid');

    expect($payable->fresh()->paid_by_user_id)->toBe($user->id)
        ->and($payable->fresh()->paid_at)->not->toBeNull();

    $this->patch(route('payables.update', $payable), ['amount' => 250])
        ->assertStatus(409);

    expect((float) $payable->fresh()->amount)->toBe(150.0);
    $this->assertDatabaseCount('payable_histories', 1);

    $this->get(route('payables.index', ['status' => 'paid']))
        ->assertOk()
        ->assertSee('Valor bloqueado após pagamento')
        ->assertDontSee('id="amount-'.$payable->id.'"', false)
        ->assertSee('confirm-reopen-title-'.$payable->id, false)
        ->assertSee('Confirmar reversão do pagamento?')
        ->assertSee('Manter como paga')
        ->assertSee('Sim, desfazer pagamento');

    $this->patch(route('payables.payment.toggle', $payable))
        ->assertSessionHas('status', 'payable-reopened');

    expect($payable->fresh()->paid_by_user_id)->toBeNull()
        ->and($payable->fresh()->paid_at)->toBeNull();

    $this->assertDatabaseHas('payable_histories', [
        'payable_id' => $payable->id,
        'user_id' => $user->id,
        'actor_name' => 'Bruno Operador',
        'action' => PayableHistory::ACTION_PAYMENT_MARKED,
    ]);
    $this->assertDatabaseHas('payable_histories', [
        'payable_id' => $payable->id,
        'user_id' => $user->id,
        'actor_name' => 'Bruno Operador',
        'action' => PayableHistory::ACTION_PAYMENT_REOPENED,
    ]);

    $this->get(route('payables.index'))
        ->assertOk()
        ->assertSee('Histórico de alterações (2)')
        ->assertSee('Pagamento marcado como pago')
        ->assertSee('Pagamento desfeito')
        ->assertSee('Bruno Operador');
});

test('the dashboard displays financial totals grouped by company unit', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company, 'Filial Recife');
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $user->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 125,
        'due_day' => 1,
        'starts_on' => today()->toDateString(),
        'is_active' => true,
    ]);

    foreach ([
        ['amount' => 125, 'due_date' => today()->subDay(), 'paid_at' => null],
        ['amount' => 75, 'due_date' => today(), 'paid_at' => now()],
    ] as $payment) {
        Payable::create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'recurring_payable_id' => $contract->id,
            'description' => 'Internet',
            'provider' => 'Provedor',
            ...$payment,
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Totais por unidade')
        ->assertSee('Filial Recife')
        ->assertSee('1 contrato')
        ->assertSee('R$ 125,00')
        ->assertSee('R$ 75,00');
});

test('the dashboard guides a user without a company to create one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Comece pelo espaço da sua empresa')
        ->assertSee('name="name"', false)
        ->assertSee('Criar empresa');
});

test('recurring payables generate three monthly due dates safely for day 31 and do not duplicate', function () {
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, 'Empresa');
    $unit = createUnitForCompany($company);
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $user->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_day' => 31,
        'starts_on' => '2026-01-31',
        'is_active' => true,
    ]);
    $generator = app(RecurringPayableGenerator::class);
    $today = Carbon::parse('2026-01-01');

    expect($generator->generateFor($contract, $today))->toBe(3)
        ->and($generator->generateFor($contract, $today))->toBe(0);

    foreach (['2026-01-31', '2026-02-28', '2026-03-31'] as $dueDate) {
        expect($contract->payables()->whereDate('due_date', $dueDate)->exists())->toBeTrue();
    }
});

test('deleting an owner transfers ownership in every company with remaining members', function () {
    $owner = User::factory()->create();
    $successor = User::factory()->create();
    $firstCompany = createCompanyWithMember($owner, 'Empresa A');
    addCompanyMember($firstCompany, $successor);
    $secondCompany = createCompanyWithMember($owner, 'Empresa B');
    addCompanyMember($secondCompany, $successor);

    $this->actingAs($owner)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    foreach ([$firstCompany, $secondCompany] as $company) {
        expect($company->users()->whereKey($successor->id)->first()->pivot->role)
            ->toBe(Company::ROLE_OWNER);
    }

    $this->assertDatabaseMissing('users', ['id' => $owner->id]);
});

test('deleting the only company owner removes its unit-linked payable data', function () {
    $owner = User::factory()->create();
    $company = createCompanyWithMember($owner, 'Empresa');
    $unit = createUnitForCompany($company);
    $contract = RecurringPayable::create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'created_by_user_id' => $owner->id,
        'description' => 'Internet',
        'provider' => 'Provedor',
        'amount' => 150,
        'due_day' => 20,
        'starts_on' => today()->toDateString(),
    ]);

    app(RecurringPayableGenerator::class)->generateFor($contract);

    $this->actingAs($owner)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    $this->assertDatabaseMissing('recurring_payables', ['id' => $contract->id]);
    $this->assertDatabaseMissing('payables', ['recurring_payable_id' => $contract->id]);
});
