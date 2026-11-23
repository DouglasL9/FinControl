<?php

use App\Models\User;

it('redirects guests from the root to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('redirects authenticated users from the root to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

it('keeps the success notification when redirecting to the login page', function () {
    $this
        ->withSession(['status' => 'account-deleted'])
        ->followingRedirects()
        ->get('/')
        ->assertOk()
        ->assertSee('Conta excluída com sucesso.');
});

it('offers a registration link on the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Registre-se')
        ->assertSee(route('register'), false);
});
