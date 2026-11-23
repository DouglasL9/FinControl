<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('displays a success notification on the welcome page', function () {
    $response = $this
        ->withSession(['status' => 'account-deleted'])
        ->get('/');

    $response
        ->assertOk()
        ->assertSee('Conta excluída com sucesso.');
});
