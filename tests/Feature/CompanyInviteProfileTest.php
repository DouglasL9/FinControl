<?php

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanyPermissionProfile;
use App\Models\User;

function createInvitationForInviteProfileTest(Company $company, User $invitee, string $token): CompanyInvitation
{
    return CompanyInvitation::create([
        'company_id' => $company->getKey(),
        'invited_by_user_id' => null,
        'email' => $invitee->email,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
    ]);
}

function inviteProfileTestMembershipOf(Company $company, User $user): User
{
    return $company->users()->whereKey($user)->firstOrFail();
}

test('creating a company creates the protected administrator profile and the read only invite profile', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('companies.store'), ['name' => 'Empresa nova'])
        ->assertRedirect(route('dashboard'));

    $company = Company::where('name', 'Empresa nova')->firstOrFail();
    $administratorProfile = $company->permissionProfiles()->where('is_default', true)->sole();
    $inviteProfile = $company->permissionProfiles()->where('is_invite_default', true)->sole();

    expect($administratorProfile->name)->toBe(Company::ADMINISTRATOR_PROFILE_NAME)
        ->and($administratorProfile->is_invite_default)->toBeFalse()
        ->and(inviteProfileTestMembershipOf($company, $owner)->pivot->permission_profile_id)->toBe($administratorProfile->getKey())
        ->and($inviteProfile->name)->toBe(Company::INVITE_PROFILE_NAME)
        ->and($inviteProfile->is_default)->toBeFalse()
        ->and(array_keys(array_filter($inviteProfile->permissions)))
        ->toEqualCanonicalizing(['dashboard.view', 'payables.view', 'units.view', 'suppliers.view']);
});

test('accepting an invitation assigns the read only invite profile instead of the administrator profile', function () {
    $owner = User::factory()->create();
    $company = Company::create(['name' => 'Empresa convidante']);
    $administratorProfile = $company->createInitialPermissionProfiles();
    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $administratorProfile->getKey(),
    ]);
    $invitee = User::factory()->create();
    createInvitationForInviteProfileTest($company, $invitee, str_repeat('c', 64));

    $this->actingAs($invitee)
        ->post(route('company-invitations.accept', str_repeat('c', 64)))
        ->assertRedirect(route('dashboard'));

    $membership = inviteProfileTestMembershipOf($company, $invitee);

    expect($membership->pivot->role)->toBe(Company::ROLE_MEMBER)
        ->and($membership->pivot->permission_profile_id)
        ->toBe($company->permissionProfiles()->where('is_invite_default', true)->value('id'))
        ->and($membership->pivot->permission_profile_id)->not->toBe($administratorProfile->getKey())
        ->and($company->allows($membership, 'payables.view'))->toBeTrue()
        ->and($company->allows($membership, 'payables.update_amount'))->toBeFalse()
        ->and($company->allows($membership, 'team.view'))->toBeFalse();

    $this->post(route('company.units.store'), ['unit_name' => 'Unidade proibida'])
        ->assertForbidden();
});

test('accepting an invitation of a company without an invite profile grants no access', function () {
    $owner = User::factory()->create();
    $company = Company::create(['name' => 'Empresa sem perfil de convite']);
    $administratorProfile = $company->createInitialPermissionProfiles();
    $company->permissionProfiles()->where('is_invite_default', true)->delete();
    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $administratorProfile->getKey(),
    ]);
    $invitee = User::factory()->create();
    createInvitationForInviteProfileTest($company, $invitee, str_repeat('d', 64));

    $this->actingAs($invitee)
        ->post(route('company-invitations.accept', str_repeat('d', 64)))
        ->assertRedirect(route('dashboard'));

    $membership = inviteProfileTestMembershipOf($company, $invitee);

    expect($membership->pivot->permission_profile_id)->toBeNull()
        ->and(array_filter($company->permissionsFor($membership)))->toBe([]);

    $this->get(route('payables.index'))->assertForbidden();
});

test('the administrator profile stays locked while the owner may edit but not delete the invite profile', function () {
    $owner = User::factory()->create();
    $company = Company::create(['name' => 'Empresa protegida']);
    $administratorProfile = $company->createInitialPermissionProfiles();
    $inviteProfile = $company->permissionProfiles()->where('is_invite_default', true)->sole();
    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $administratorProfile->getKey(),
    ]);
    $managerProfile = $company->permissionProfiles()->create([
        'name' => 'Gestor de perfis',
        'permissions' => [...CompanyPermissionProfile::defaultProfilePermissions(), 'profiles.manage' => true],
    ]);
    $manager = User::factory()->create();
    $company->users()->attach($manager->getKey(), [
        'role' => Company::ROLE_MEMBER,
        'permission_profile_id' => $managerProfile->getKey(),
    ]);

    $this->actingAs($owner)
        ->patch(route('company.permission-profiles.update', $administratorProfile), ['name' => 'Rebaixado', 'permissions' => []])
        ->assertForbidden();

    $this->delete(route('company.permission-profiles.destroy', $administratorProfile))->assertForbidden();

    $this->delete(route('company.permission-profiles.destroy', $inviteProfile))->assertForbidden();

    $this->get(route('company.show', ['section' => 'profiles']))
        ->assertOk()
        ->assertSee('Padrão para convites')
        ->assertDontSee('delete_permission_profile_'.$inviteProfile->id, false);

    $this->patch(route('company.permission-profiles.update', $inviteProfile), [
        'name' => 'Membro',
        'permissions' => ['payables.view' => '1'],
    ])->assertSessionHas('status', 'permission-profile-updated');

    $this->actingAs($manager)
        ->patch(route('company.permission-profiles.update', $inviteProfile), [
            'name' => 'Membro',
            'permissions' => ['payables.view' => '1', 'units.view' => '1'],
        ])
        ->assertForbidden();

    expect($administratorProfile->fresh()->name)->toBe(Company::ADMINISTRATOR_PROFILE_NAME)
        ->and($inviteProfile->fresh()->is_invite_default)->toBeTrue()
        ->and(array_keys(array_filter($inviteProfile->fresh()->permissions)))->toBe(['payables.view']);
});

test('the invite profile migration is idempotent and keeps existing profiles and members untouched', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $company = Company::create(['name' => 'Empresa legada']);
    $administratorProfile = $company->permissionProfiles()->create([
        'name' => Company::ADMINISTRATOR_PROFILE_NAME,
        'permissions' => CompanyPermissionProfile::defaultProfilePermissions(),
        'is_default' => true,
    ]);
    $customMemberProfile = $company->permissionProfiles()->create([
        'name' => 'Membro',
        'permissions' => CompanyPermissionProfile::allPermissions(),
    ]);
    $company->users()->attach($owner->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $administratorProfile->getKey(),
    ]);
    $company->users()->attach($member->getKey(), [
        'role' => Company::ROLE_MEMBER,
        'permission_profile_id' => $administratorProfile->getKey(),
    ]);
    $migration = require database_path('migrations/2026_11_23_204359_add_invite_default_member_profile_to_company_permission_profiles.php');

    $migration->up();
    $migration->up();

    $inviteProfile = $company->permissionProfiles()->where('is_invite_default', true)->sole();

    expect($inviteProfile->name)->toBe('Membro (convite)')
        ->and(array_keys(array_filter($inviteProfile->permissions)))
        ->toEqualCanonicalizing(['dashboard.view', 'payables.view', 'units.view', 'suppliers.view'])
        ->and($customMemberProfile->fresh()->is_invite_default)->toBeFalse()
        ->and($customMemberProfile->fresh()->permissions)->toEqual(CompanyPermissionProfile::allPermissions())
        ->and($administratorProfile->fresh()->is_default)->toBeTrue()
        ->and(inviteProfileTestMembershipOf($company, $member)->pivot->permission_profile_id)->toBe($administratorProfile->getKey())
        ->and(inviteProfileTestMembershipOf($company, $owner)->pivot->permission_profile_id)->toBe($administratorProfile->getKey());
});
