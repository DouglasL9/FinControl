<?php

use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use Illuminate\Support\Facades\Notification;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users must verify an email code before they are authenticated', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $code = null;
    Notification::assertSentOnDemand(
        LoginVerificationCode::class,
        function (LoginVerificationCode $notification, array $channels, object $notifiable) use ($user, &$code): bool {
            $code = $notification->code;

            return $notifiable->routes['mail'] === $user->email;
        },
    );

    $challenge = LoginChallenge::query()->findOrFail($user->id);

    $this->assertGuest();
    $response->assertRedirect(route('login.code'));
    expect($code)->toMatch('/^\d{6}$/');
    expect(hash_equals($challenge->code_hash, hash_hmac('sha256', $code, (string) config('app.key'))))->toBeTrue();

    $this->get(route('login.code'))
        ->assertOk()
        ->assertSee('Digite o código abaixo para concluir o acesso.');
});

test('users can authenticate after entering the code sent to their email', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->withSession(['url.intended' => '/profile'])->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
    ]);

    $code = null;
    Notification::assertSentOnDemand(
        LoginVerificationCode::class,
        function (LoginVerificationCode $notification, array $channels, object $notifiable) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    $response = $this->post(route('login.verify'), ['code' => $code]);

    $this->assertAuthenticated();
    $response
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'logged-in');
    $this->assertDatabaseMissing('login_challenges', ['user_id' => $user->id]);
});

test('users can not authenticate with invalid password', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    Notification::assertNothingSent();
});

test('invalid email codes do not authenticate users', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('login.verify'), ['code' => '000000']);

    $this->assertGuest();
    $response->assertSessionHasErrors('code');
    expect(LoginChallenge::query()->findOrFail($user->id)->attempts)->toBe(1);
});

test('email codes with an invalid format do not consume an attempt', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('login.verify'), ['code' => '12345']);

    $this->assertGuest();
    $response->assertSessionHasErrors('code');
    expect(LoginChallenge::query()->findOrFail($user->id)->attempts)->toBe(0);
});

test('email codes are invalidated after five incorrect attempts', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    foreach (range(1, 5) as $attempt) {
        $response = $this->post(route('login.verify'), ['code' => '000000']);
    }

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $this->assertDatabaseMissing('login_challenges', ['user_id' => $user->id]);
    $this->get(route('login.code'))->assertRedirect(route('login'));
});

test('expired email codes cannot authenticate users', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    LoginChallenge::query()->findOrFail($user->id)->update([
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->post(route('login.verify'), ['code' => '000000']);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $this->assertDatabaseMissing('login_challenges', ['user_id' => $user->id]);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response
        ->assertRedirect('/')
        ->assertSessionHas('status', 'logged-out');
});
