<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response
        ->assertOk()
        ->assertSee(route('help.index'), false)
        ->assertSee('Ajuda e documentação');
});

test('authenticated users can read the product documentation from their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('help.index'))
        ->assertOk()
        ->assertSee('Como usar o FinControl')
        ->assertSee('Como cadastrar uma conta fixa')
        ->assertSee('confirme a ação')
        ->assertSee('Proprietário');
});

test('guests cannot access the product documentation', function () {
    $this->get(route('help.index'))
        ->assertRedirect(route('login'));
});

test('unverified users see the email verification action on their profile', function () {
    $user = User::factory()->unverified()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response
        ->assertOk()
        ->assertSee('E-mail não verificado')
        ->assertSee('Enviar um novo e-mail de verificação');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'profile-updated')
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'account-deleted')
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('deleting the last company owner removes its stored logo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Empresa com logo']);
    $profile = $company->createInitialPermissionProfiles();
    $company->users()->attach($user->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $profile->getKey(),
    ]);
    $logoPath = "companies/{$company->getKey()}/branding/logo.png";
    Storage::disk('public')->put($logoPath, 'logo');
    $company->update(['logo_path' => $logoPath]);

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    Storage::disk('public')->assertMissing($logoPath);
});

test('profile update notifications are displayed on the profile page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession(['status' => 'profile-updated'])
        ->get('/profile');

    $response
        ->assertOk()
        ->assertSee('Perfil atualizado com sucesso.');
});

test('account deletion notifications are displayed on the login page', function () {
    $response = $this
        ->withSession(['status' => 'account-deleted'])
        ->followingRedirects()
        ->get('/');

    $response
        ->assertOk()
        ->assertSee('Conta excluída com sucesso.');
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('profile page lists the companies and access level of the user', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Empresa Listada']);
    $profile = $company->createInitialPermissionProfiles();
    $company->users()->attach($user->getKey(), [
        'role' => Company::ROLE_OWNER,
        'permission_profile_id' => $profile->getKey(),
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Minhas empresas')
        ->assertSee('Empresa Listada')
        ->assertSee('Proprietário')
        ->assertSee('Verificação em duas etapas');
});

test('help center is organized by tasks and hides technical docs from regular users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('help.index'))
        ->assertOk()
        ->assertSee('Primeiros passos')
        ->assertSee('Contas a pagar')
        ->assertSee('Quem pode fazer o quê')
        ->assertSee('Seu acesso e segurança')
        ->assertSee('Dúvidas frequentes')
        ->assertDontSee('Para desenvolvedores');
});

test('technical documentation link is shown only to system administrators', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_system_admin' => true])->save();

    $this->actingAs($admin)
        ->get(route('help.index'))
        ->assertOk()
        ->assertSee('Para desenvolvedores')
        ->assertSee(route('documentation.show'), false);
});

test('technical documentation page loads for system administrators and is forbidden for others', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_system_admin' => true])->save();
    $company = Company::create(['name' => 'Empresa docs']);
    $company->users()->attach($admin->getKey(), ['role' => Company::ROLE_OWNER]);

    $this->actingAs($admin)
        ->get(route('documentation.show'))
        ->assertOk()
        ->assertSee('Documentação do sistema')
        ->assertSee(route('help.index'), false);

    $member = User::factory()->create();
    $company->users()->attach($member->getKey(), ['role' => Company::ROLE_MEMBER]);

    $this->actingAs($member)
        ->get(route('documentation.show'))
        ->assertForbidden();
});
