<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['company_id', 'name', 'permissions', 'is_default'])]
class CompanyPermissionProfile extends Model
{
    public const GROUPS = [
        'Painel' => [
            'dashboard.view' => 'Visualizar o painel financeiro',
        ],
        'Contas a pagar' => [
            'payables.view' => 'Visualizar contratos, parcelas e valores',
            'payables.create_contract' => 'Cadastrar contratos recorrentes',
            'payables.update_amount' => 'Alterar o valor das parcelas',
            'payables.change_payment_status' => 'Marcar pagamentos e reabrir parcelas',
            'payables.manage_contract' => 'Pausar e reativar contratos',
        ],
        'Unidades' => [
            'units.view' => 'Visualizar unidades',
            'units.create' => 'Cadastrar unidades',
            'units.update' => 'Editar unidades',
            'units.archive' => 'Arquivar e reativar unidades',
        ],
        'Fornecedores' => [
            'suppliers.view' => 'Visualizar fornecedores',
            'suppliers.create' => 'Cadastrar fornecedores',
            'suppliers.update' => 'Editar fornecedores',
            'suppliers.archive' => 'Arquivar e reativar fornecedores',
        ],
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public static function permissionKeys(): array
    {
        $keys = [];

        foreach (self::GROUPS as $permissions) {
            $keys = [...$keys, ...array_keys($permissions)];
        }

        return $keys;
    }

    public static function allPermissions(): array
    {
        return array_fill_keys(self::permissionKeys(), true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user', 'permission_profile_id', 'user_id')
            ->withPivot('company_id');
    }
}
