<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginChallenge;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginVerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('auth.login.pending_user_id')) {
            return redirect()->route('login');
        }

        $challenge = $this->pendingChallenge($request);

        if ($challenge === null) {
            return $this->redirectToLogin($request);
        }

        return view('auth.login-code');
    }

    public function store(Request $request): RedirectResponse
    {
        $challenge = $this->pendingChallenge($request);

        if ($challenge === null) {
            return $this->redirectToLogin($request);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $attemptRegistered = LoginChallenge::query()
            ->whereKey($challenge->getKey())
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->increment('attempts');

        if ($attemptRegistered === 0) {
            $challenge->delete();

            return $this->redirectToLogin($request);
        }

        $challenge->refresh();

        $codeHash = hash_hmac('sha256', $validated['code'], (string) config('app.key'));

        if (! hash_equals($challenge->code_hash, $codeHash)) {
            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                $challenge->delete();

                return $this->redirectToLogin($request);
            }

            return back()->withErrors([
                'code' => __('The verification code is incorrect.'),
            ]);
        }

        $user = User::query()->findOrFail($request->session()->get('auth.login.pending_user_id'));
        $remember = (bool) $request->session()->get('auth.login.remember', false);

        $challenge->delete();
        $request->session()->forget([
            'auth.login.pending_user_id',
            'auth.login.remember',
        ]);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('dashboard', absolute: false))
            ->with('status', 'logged-in');
    }

    private function pendingChallenge(Request $request): ?LoginChallenge
    {
        $userId = $request->session()->get('auth.login.pending_user_id');

        if (! is_int($userId) && ! (is_string($userId) && ctype_digit($userId))) {
            return null;
        }

        $challenge = LoginChallenge::query()
            ->where('user_id', (int) $userId)
            ->first();

        if ($challenge === null || $challenge->expires_at->isPast()) {
            $challenge?->delete();

            return null;
        }

        return $challenge;
    }

    private function redirectToLogin(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'auth.login.pending_user_id',
            'auth.login.remember',
        ]);

        return redirect()->route('login')->withErrors([
            'email' => __('The verification code expired. Please sign in again.'),
        ]);
    }
}
