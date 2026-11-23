<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Company;
use App\Models\CompanyPermissionProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $currentCompany = $user->currentCompany();
        $profileNames = CompanyPermissionProfile::query()
            ->whereIn('id', $user->companies()->pluck('company_user.permission_profile_id')->filter()->all())
            ->pluck('name', 'id');

        $memberships = $user->companies()
            ->orderBy('companies.name')
            ->get()
            ->map(fn (Company $company): array => [
                'company' => $company,
                'roleLabel' => match ($company->pivot->role) {
                    Company::ROLE_OWNER => 'Proprietário',
                    Company::ROLE_ADMIN => 'Administrador',
                    default => 'Membro',
                },
                'profileName' => $profileNames->get($company->pivot->permission_profile_id),
                'isCurrent' => $currentCompany?->is($company) ?? false,
            ]);

        return view('profile.edit', [
            'user' => $user,
            'memberships' => $memberships,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $logoPathsToDelete = [];

        DB::transaction(function () use ($user, &$logoPathsToDelete): void {
            $ownedCompanies = $user->companies()
                ->wherePivot('role', Company::ROLE_OWNER)
                ->get();

            foreach ($ownedCompanies as $company) {
                $successor = $company->users()
                    ->where('users.id', '<>', $user->getKey())
                    ->orderBy('users.id')
                    ->first();

                if ($successor === null) {
                    if ($company->logo_path !== null) {
                        $logoPathsToDelete[] = $company->logo_path;
                    }

                    $company->payables()->delete();
                    $company->recurringPayables()->delete();
                    $company->units()->delete();
                    $company->delete();
                } else {
                    $company->users()->updateExistingPivot($successor->getKey(), [
                        'role' => Company::ROLE_OWNER,
                    ]);
                }
            }

            $user->delete();
        });

        foreach ($logoPathsToDelete as $logoPath) {
            if (! Storage::disk('public')->delete($logoPath)) {
                Log::warning('Unable to delete company logo after account deletion.', [
                    'path' => $logoPath,
                ]);
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('status', 'account-deleted');
    }
}
