<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyInvitationController extends Controller
{
    public function searchUsers(Request $request): JsonResponse
    {
        $company = $this->ownedCompany($request);
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $search = trim($validated['search']);

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $escapedSearch = str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $search,
        );
        $pattern = "%{$escapedSearch}%";

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->where('id', '<>', $request->user()->getKey())
            ->whereDoesntHave('companies', fn ($query) => $query->whereKey($company->getKey()))
            ->where(function ($query) use ($pattern): void {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("email LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        return response()->json($users);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->ownedCompany($request);
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        $email = Str::lower($validated['email']);

        if ($company->users()->where('email', $email)->exists()) {
            return back()->withErrors([
                'email' => 'Este usuário já faz parte desta empresa.',
            ]);
        }

        $token = Str::random(64);

        CompanyInvitation::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'email' => $email,
            ],
            [
                'invited_by_user_id' => $request->user()->getKey(),
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        Notification::route('mail', $email)->notify(
            new CompanyInvitationNotification(
                $company->name,
                $request->user()->name,
                route('company-invitations.show', ['token' => $token]),
            ),
        );

        return back()->with('status', 'invitation-sent');
    }

    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invitation = $this->findInvitation($token);
        $this->authorizeInvitee($request, $invitation);

        if ($invitation->company->users()->whereKey($request->user()->getKey())->exists()) {
            abort(403, 'You already belong to this company.');
        }

        return view('companies.invitations.show', [
            'invitation' => $invitation->load('invitedBy'),
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        DB::transaction(function () use ($request, $token): void {
            $invitation = $this->findInvitation($token, lock: true);
            $this->authorizeInvitee($request, $invitation);
            $company = Company::query()
                ->whereKey($invitation->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(! $company->is_active, 404);

            if ($invitation->company->users()->whereKey($request->user()->getKey())->exists()) {
                abort(403, 'You already belong to this company.');
            }

            $defaultProfile = $company->permissionProfiles()
                ->where('is_default', true)
                ->firstOrFail();

            $invitation->company->users()->attach($request->user()->getKey(), [
                'role' => Company::ROLE_MEMBER,
                'permission_profile_id' => $defaultProfile->getKey(),
            ]);
            $invitation->forceFill(['accepted_at' => now()])->save();
            $request->session()->put('active_company_id', $invitation->company->getKey());
        });

        return redirect()->route('dashboard')->with('status', 'company-joined');
    }

    private function ownedCompany(Request $request): Company
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless(
            $company->users()
                ->whereKey($request->user()->getKey())
                ->wherePivot('role', Company::ROLE_OWNER)
                ->exists(),
            403,
        );

        return $company;
    }

    private function findInvitation(string $token, bool $lock = false): CompanyInvitation
    {
        $query = CompanyInvitation::query()
            ->with('company')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->whereHas('company', fn ($companyQuery) => $companyQuery->where('is_active', true));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    private function authorizeInvitee(Request $request, CompanyInvitation $invitation): void
    {
        abort_unless(Str::lower($request->user()->email) === $invitation->email, 403);
    }
}
