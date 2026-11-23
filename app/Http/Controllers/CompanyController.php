<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        $membership = $company->membershipFor($request->user());
        $isOwner = $company->isOwner($membership);
        $permissions = CompanyPermissionProfile::GROUPS;
        $access = $company->permissionsFor($membership);
        $canViewTeam = $access['team.view'] ?? false;
        $canManageProfiles = $access['profiles.manage'] ?? false;
        $canAssignProfiles = $access['team.assign_profiles'] ?? false;
        $canUpdateBranding = $access['company.update_branding'] ?? false;

        abort_unless($company->canAccessSettings($membership), 403);

        if ($access['units.view']) {
            $company->load([
                'units' => fn ($query) => $query->orderByDesc('is_active')->orderBy('name'),
            ]);
        }

        if ($access['suppliers.view']) {
            $company->load([
                'suppliers' => fn ($query) => $query->orderByDesc('is_active')->orderBy('name'),
            ]);
        }

        if ($canViewTeam) {
            $company->load('users');
        }

        if ($canManageProfiles || $canAssignProfiles) {
            $company->load([
                'permissionProfiles' => fn ($query) => $query
                    ->visibleTo($request->user())
                    ->withCount('members')
                    ->orderBy('name'),
            ]);
        }

        $hiddenProfileIds = $canViewTeam
            ? $company->permissionProfiles()
                ->whereNotIn('id', $company->permissionProfiles()->visibleTo($request->user())->select('id'))
                ->pluck('id')
                ->all()
            : [];
        $visibleProfiles = $company->relationLoaded('permissionProfiles') ? $company->permissionProfiles : collect();

        if ($isOwner) {
            $company->load([
                'invitations' => fn ($query) => $query
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('created_at'),
            ]);
        }

        $sections = array_keys(array_filter([
            'company' => true,
            'branding' => $canUpdateBranding,
            'profiles' => $canManageProfiles,
            'team' => $isOwner || $canViewTeam,
            'records' => $access['units.view'] || $access['suppliers.view'],
            'archive' => $isOwner,
        ]));

        return view('companies.show', [
            'company' => $company,
            'isOwner' => $isOwner,
            'canViewTeam' => $canViewTeam,
            'permissions' => $permissions,
            'access' => $access,
            'sections' => $sections,
            'activeSection' => $this->activeSection($request, $sections),
            'hiddenProfileIds' => $hiddenProfileIds,
            'editableProfileIds' => $canManageProfiles
                ? $visibleProfiles
                    ->filter(fn (CompanyPermissionProfile $profile): bool => ! $profile->is_default
                        && $company->canEditProfile($membership, $profile))
                    ->modelKeys()
                : [],
            'assignableProfiles' => $canAssignProfiles
                ? $visibleProfiles
                    ->filter(fn (CompanyPermissionProfile $profile): bool => $company->canDelegate(
                        $membership,
                        CompanyPermissionProfile::normalize($profile->permissions),
                    ))
                    ->values()
                : collect(),
            'assignableMemberIds' => $canAssignProfiles && $canViewTeam
                ? $company->users
                    ->filter(fn ($member): bool => ! in_array($member->pivot->permission_profile_id, $hiddenProfileIds, true)
                        && $company->canAssignProfileTo($membership, $member))
                    ->modelKeys()
                : [],
            'memberCount' => $canViewTeam ? $company->users->count() : null,
            'logoUrl' => $company->logo_path === null
                ? null
                : Storage::disk('public')->url($company->logo_path),
        ]);
    }

    public function updateDetails(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        abort_unless(
            $company->users()
                ->whereKey($request->user()->getKey())
                ->wherePivot('role', Company::ROLE_OWNER)
                ->exists(),
            403,
        );

        $cnpj = $request->input('cnpj');

        if (is_string($cnpj)) {
            $cnpj = preg_replace('/[\s.\/-]/', '', $cnpj);
            $request->merge(['cnpj' => $cnpj === '' ? null : $cnpj]);
        }

        $validated = $request->validateWithBag('companyDetails', [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'digits:14'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $company->update($validated);

        return redirect()
            ->route('company.show', ['section' => 'company'])
            ->with('status', 'company-details-updated');
    }

    /**
     * Resolve which settings section opens first, so validation errors and
     * success messages land on the section that produced them.
     *
     * @param  list<string>  $sections
     */
    private function activeSection(Request $request, array $sections): string
    {
        $errors = $request->session()->get('errors');
        $status = $request->session()->get('status');
        $oldModal = (string) $request->old('_modal', '');

        $candidates = [
            $request->query('section'),
            match (true) {
                $errors?->hasBag('companyDetails') ?? false => 'company',
                $errors?->hasAny(['primary_color', 'accent_color', 'watermark_opacity', 'logo', 'remove_logo']) ?? false => 'branding',
                $request->old('profile_id') !== null => 'profiles',
                str_contains($oldModal, 'unit') || str_contains($oldModal, 'supplier') => 'records',
                $errors?->has('password') ?? false => 'archive',
                $errors?->has('email') ?? false => 'team',
                default => null,
            },
            match (true) {
                ! is_string($status) => null,
                $status === 'company-details-updated' => 'company',
                $status === 'company-branding-updated' => 'branding',
                str_starts_with($status, 'permission-profile-') => 'profiles',
                str_starts_with($status, 'member-'), $status === 'invitation-sent' => 'team',
                str_starts_with($status, 'unit-'), str_starts_with($status, 'supplier-') => 'records',
                default => null,
            },
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $sections, true)) {
                return $candidate;
            }
        }

        return $sections[0];
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        abort_unless(
            $company->allows($company->membershipFor($request->user()), 'company.update_branding'),
            403,
        );

        $validated = $request->validate([
            'primary_color' => ['required', 'string', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'accent_color' => ['required', 'string', 'regex:/\A#[0-9A-Fa-f]{6}\z/'],
            'watermark_opacity' => ['required', 'integer', 'between:0,30'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $previousLogoPath = $company->logo_path;
        $logoPath = $previousLogoPath;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->storePublicly("companies/{$company->getKey()}/branding", 'public');

            if ($logoPath === false) {
                throw new \RuntimeException('Não foi possível salvar a logo da empresa.');
            }
        } elseif ((bool) ($validated['remove_logo'] ?? false)) {
            $logoPath = null;
        }

        $company->update([
            'primary_color' => $validated['primary_color'],
            'accent_color' => $validated['accent_color'],
            'watermark_opacity' => $validated['watermark_opacity'],
            'logo_path' => $logoPath,
        ]);

        if ($previousLogoPath !== null && $previousLogoPath !== $logoPath) {
            if (! Storage::disk('public')->delete($previousLogoPath)) {
                Log::warning('Unable to delete replaced company logo.', [
                    'company_id' => $company->getKey(),
                    'path' => $previousLogoPath,
                ]);
            }
        }

        return redirect()
            ->route('company.show')
            ->with('status', 'company-branding-updated');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'companyCreation')
                ->withInput()
                ->with('openCompanyCreation', true);
        }

        $validated = $validator->validated();

        $companyId = DB::transaction(function () use ($request, $validated): int {
            $company = Company::create(['name' => $validated['name']]);
            $profile = $company->createInitialPermissionProfiles();
            $company->users()->attach($request->user()->getKey(), [
                'role' => Company::ROLE_OWNER,
                'permission_profile_id' => $profile->getKey(),
            ]);

            return $company->getKey();
        });
        $request->session()->put('active_company_id', $companyId);

        return redirect()
            ->route('dashboard')
            ->with('status', 'company-created');
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $company = $request->user()->companies()
            ->whereKey($validated['company_id'])
            ->firstOrFail();

        if (! $company->is_active) {
            abort_unless($company->pivot->role === Company::ROLE_OWNER, 404);
            $company->update(['is_active' => true]);
        }

        $request->session()->put('active_company_id', $company->getKey());

        return redirect()->route('dashboard')->with(
            'status',
            $company->wasChanged('is_active') ? 'company-restored' : 'company-switched',
        );
    }

    public function archive(Request $request, int $companyId): RedirectResponse
    {
        $company = $request->user()->companies()->whereKey($companyId)->firstOrFail();

        abort_unless($company->pivot->role === Company::ROLE_OWNER, 403);
        abort_if(! $company->is_active, 404);

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $nextCompanyId = DB::transaction(function () use ($request, $company): ?int {
            $company->update(['is_active' => false]);

            $nextCompanyId = $request->user()->companies()
                ->where('companies.is_active', true)
                ->orderBy('companies.id')
                ->value('companies.id');

            return $nextCompanyId === null ? null : (int) $nextCompanyId;
        });

        if ($nextCompanyId === null) {
            $request->session()->forget('active_company_id');
        } else {
            $request->session()->put('active_company_id', $nextCompanyId);
        }

        return redirect()->route('dashboard')->with('status', 'company-archived');
    }
}
