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
        $isOwner = $membership?->pivot->role === Company::ROLE_OWNER;
        $canManagePermissions = $company->isPermissionManager($membership);
        $permissions = CompanyPermissionProfile::GROUPS;
        $access = $company->permissionsFor($membership);

        abort_unless(
            $isOwner || $canManagePermissions || $access['units.view'] || $access['suppliers.view'],
            403,
        );

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

        if ($canManagePermissions) {
            $company->load([
                'users',
                'permissionProfiles' => fn ($query) => $query->withCount('members')->orderBy('name'),
            ]);
        }

        if ($isOwner) {
            $company->load([
                'invitations' => fn ($query) => $query
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('created_at'),
            ]);
        }

        return view('companies.show', [
            'company' => $company,
            'isOwner' => $isOwner,
            'canManagePermissions' => $canManagePermissions,
            'permissions' => $permissions,
            'access' => $access,
            'memberCount' => $canManagePermissions ? $company->users->count() : null,
            'logoUrl' => $company->logo_path === null
                ? null
                : Storage::disk('public')->url($company->logo_path),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
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
            $profile = $company->permissionProfiles()->create([
                'name' => 'Acesso completo',
                'permissions' => CompanyPermissionProfile::allPermissions(),
                'is_default' => true,
            ]);
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
