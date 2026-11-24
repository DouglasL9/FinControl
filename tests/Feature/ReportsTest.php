<?php

use App\Models\Company;
use App\Models\Payable;
use App\Models\User;

function reportFixture(string $companyName = 'Empresa'): array
{
    $user = User::factory()->create();
    $company = createCompanyWithMember($user, $companyName);
    $contract = createContractWithInstallments($user, $company);

    return [$user, $company, $contract];
}

test('a member without reports permission cannot see or export reports', function () {
    [, $company] = reportFixture();
    $viewer = User::factory()->create();
    $profile = createManagementTestProfile($company, 'Sem relatorios', ['payables.view']);
    $company->users()->attach($viewer->getKey(), ['role' => Company::ROLE_MEMBER, 'permission_profile_id' => $profile->getKey()]);

    $this->actingAs($viewer);
    $this->get(route('reports.show', 'payables'))->assertForbidden();
    $this->get(route('reports.export', 'payables'))->assertForbidden();
    $this->get(route('reports.print', 'payables'))->assertForbidden();
    $this->get(route('payables.index'))->assertOk()->assertDontSee('Relatórios');
});

test('an authorized member sees every report type with totals and navigation link', function () {
    [$user] = reportFixture();

    $this->actingAs($user)->get(route('reports.index'))->assertRedirect(route('reports.show', 'payables'));

    foreach (['payables', 'contracts', 'suppliers', 'units'] as $type) {
        $this->get(route('reports.show', $type))->assertOk()->assertSee('Provedor');
    }

    $this->get(route('reports.show', 'payables'))->assertSee('Relatórios')->assertSee('Total');
    $this->get(route('reports.show', 'unknown'))->assertNotFound();
});

test('reports never include data from other companies', function () {
    [$user] = reportFixture();
    [, , $foreignContract] = reportFixture('Outra');
    $foreignContract->update(['description' => 'Segredo alheio', 'provider' => 'Fornecedor alheio']);
    Payable::query()->where('company_id', $foreignContract->company_id)->update(['description' => 'Segredo alheio', 'provider' => 'Fornecedor alheio']);

    $this->actingAs($user);

    foreach (['payables', 'contracts', 'suppliers', 'units'] as $type) {
        $this->get(route('reports.show', $type))->assertDontSee('alheio');
        $this->get(route('reports.export', $type))->assertDownload();
    }

    $this->get(route('reports.show', ['report' => 'payables', 'unit_id' => $foreignContract->unit_id]))
        ->assertSessionHasErrors('unit_id');
});

test('payables report applies status and period filters and computes totals', function () {
    [$user, , $contract] = reportFixture();
    $payables = $contract->payables()->orderBy('due_date')->get();
    $first = $payables->first();
    $first->update(['paid_at' => now(), 'paid_by_user_id' => $user->id, 'amount' => 100]);
    $payables->skip(1)->each->update(['amount' => 10]);
    $others = $payables->skip(1)->count();

    $this->actingAs($user)
        ->get(route('reports.show', ['report' => 'payables', 'status' => 'paid']))
        ->assertOk()
        ->assertSee('R$ 100,00')
        ->assertSee($first->due_date->format('d/m/Y'));

    $response = $this->get(route('reports.show', ['report' => 'payables', 'status' => 'paid']));
    $response->assertDontSee($payables->last()->due_date->format('d/m/Y'));

    $this->get(route('reports.show', ['report' => 'payables', 'start' => $first->due_date->toDateString(), 'end' => $first->due_date->toDateString()]))
        ->assertOk()
        ->assertSee('1 linha');

    $this->get(route('reports.show', ['report' => 'payables']))
        ->assertSee('R$ '.number_format(100 + 10 * $others, 2, ',', '.'));

    $this->get(route('reports.show', ['report' => 'payables', 'start' => '2026-12-31', 'end' => '2026-01-01']))
        ->assertSessionHasErrors('end');
    $this->get(route('reports.show', ['report' => 'contracts', 'status' => 'paid']))
        ->assertSessionHasErrors('status');
});

test('supplier report totals paid and open amounts', function () {
    [$user, , $contract] = reportFixture();
    $payables = $contract->payables()->get();
    $payables->each->update(['amount' => 50]);
    $payables->first()->update(['paid_at' => now(), 'paid_by_user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('reports.show', 'suppliers'))
        ->assertSee('Provedor')
        ->assertSee('R$ 50,00')
        ->assertSee('R$ '.number_format(50 * $payables->count(), 2, ',', '.'));
});

test('csv export is utf8 with bom, semicolon separated and neutralizes formulas', function () {
    [$user, , $contract] = reportFixture();
    $contract->payables()->update(['description' => '=HYPERLINK("x")']);

    $response = $this->actingAs($user)->get(route('reports.export', 'payables'));
    $response->assertOk()->assertDownload();

    $content = $response->streamedContent();

    expect($content)->toStartWith("\xEF\xBB\xBF")
        ->and($content)->toContain('Vencimento;Descrição;Fornecedor;Unidade;Valor;Status;"Pago em"')
        ->and($content)->toContain("'=HYPERLINK")
        ->and($content)->toContain('Total;"R$');

    $this->get(route('reports.print', 'contracts'))->assertOk()->assertSee('window.print()', false);
});
