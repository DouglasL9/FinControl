<?php

use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createCompanyForBranding(User $owner): Company
{
    $company = Company::create(['name' => 'Empresa personalizada']);
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

test('a company owner can customize colors and logo and preview the workspace branding', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    $company = createCompanyForBranding($owner);
    $logo = UploadedFile::fake()->create('marca.png', 20, 'image/png');

    $this->actingAs($owner)
        ->patch(route('company.branding.update'), [
            'primary_color' => '#123456',
            'accent_color' => '#abcdef',
            'watermark_opacity' => 14,
            'logo' => $logo,
        ])
        ->assertRedirect(route('company.show'))
        ->assertSessionHas('status', 'company-branding-updated');

    $company->refresh();
    $logoPath = $company->logo_path;

    expect($company->primary_color)->toBe('#123456')
        ->and($company->accent_color)->toBe('#abcdef')
        ->and($company->watermark_opacity)->toBe(14)
        ->and($logoPath)->not->toBeNull();

    Storage::disk('public')->assertExists($logoPath);

    $this->get(route('company.show'))
        ->assertOk()
        ->assertSee('Identidade visual da empresa atualizada.')
        ->assertSee('Identidade visual')
        ->assertSee('style="--company-primary: #123456; --company-accent: #abcdef;"', false)
        ->assertSee('style="opacity: 0.14;"', false)
        ->assertSee(Storage::disk('public')->url($logoPath), false);

    $this->patch(route('company.branding.update'), [
        'primary_color' => '#123456',
        'accent_color' => '#abcdef',
        'watermark_opacity' => 0,
        'remove_logo' => '1',
    ])
        ->assertRedirect(route('company.show'));

    expect($company->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($logoPath);
});

test('a company member cannot change shared branding', function () {
    $owner = User::factory()->create();
    $company = createCompanyForBranding($owner);
    $member = User::factory()->create();
    $company->users()->attach($member->getKey(), [
        'role' => Company::ROLE_MEMBER,
        'permission_profile_id' => $company->permissionProfiles()->where('is_default', true)->value('id'),
    ]);

    $this->actingAs($member)
        ->patch(route('company.branding.update'), [
            'primary_color' => '#123456',
            'accent_color' => '#abcdef',
            'watermark_opacity' => 10,
        ])
        ->assertForbidden();

    expect($company->fresh()->primary_color)->toBe('#4f46e5')
        ->and($company->fresh()->accent_color)->toBe('#7c3aed');

    $this->get(route('company.show'))
        ->assertOk()
        ->assertDontSee('Identidade visual');
});

test('invalid brand colors watermark opacity and svg logos are rejected', function () {
    Storage::fake('public');
    $owner = User::factory()->create();
    createCompanyForBranding($owner);

    $this->actingAs($owner)
        ->from(route('company.show'))
        ->patch(route('company.branding.update'), [
            'primary_color' => 'indigo',
            'accent_color' => '#abcdef',
            'watermark_opacity' => 31,
            'logo' => UploadedFile::fake()->create('marca.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors(['primary_color', 'watermark_opacity', 'logo'])
        ->assertRedirect(route('company.show'));

    Storage::disk('public')->assertDirectoryEmpty('companies');
});
