<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = User::query()->findOrFail((int) Auth::id());
        $remember = $request->boolean('remember');
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Auth::logout();
        $request->session()->regenerate();
        $request->session()->put([
            'auth.login.pending_user_id' => $user->id,
            'auth.login.remember' => $remember,
        ]);

        LoginChallenge::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => hash_hmac('sha256', $code, (string) config('app.key')),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ],
        );

        Notification::route('mail', $user->email)
            ->notify(new LoginVerificationCode($code, $user));

        return redirect()->route('login.code');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'logged-out');
    }
}
