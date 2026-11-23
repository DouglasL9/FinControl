<?php

use App\Models\User;

test('tmp debug', function () {
    $owner = User::factory()->create();
    createCompanyForSettingsTest($owner);
    $r = $this->actingAs($owner)->from(route('company.show'))->patch(route('company.details.update'), ['name' => '', 'cnpj' => '123']);
    fwrite(STDERR, json_encode(array_keys(session()->all())).' '.json_encode(session('_flash')).PHP_EOL);
    $res = $this->get(route('company.show'));
    fwrite(STDERR, (str_contains($res->getContent(), '14 digits') ? 'HAS' : 'NO').' '.(str_contains($res->getContent(), 'required') ? 'REQ' : 'noreq').PHP_EOL);
    fwrite(STDERR, json_encode(array_keys(session()->all())).' errors='.json_encode(session('errors')?->getBags() ? array_keys(session('errors')->getBags()) : null).PHP_EOL);
    expect(true)->toBeTrue();
});
