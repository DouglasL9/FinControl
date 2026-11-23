<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('cnpj', 14)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'cnpj']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::table('recurring_payables', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
        });

        foreach (
            DB::table('recurring_payables')
                ->select(['company_id', 'provider'])
                ->whereNotNull('provider')
                ->where('provider', '<>', '')
                ->distinct()
                ->orderBy('company_id')
                ->orderBy('provider')
                ->get() as $contract
        ) {
            $supplierId = DB::table('suppliers')->insertGetId([
                'company_id' => $contract->company_id,
                'name' => $contract->provider,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('recurring_payables')
                ->where('company_id', $contract->company_id)
                ->where('provider', $contract->provider)
                ->update(['supplier_id' => $supplierId]);
        }
    }

    public function down(): void
    {
        Schema::table('recurring_payables', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::dropIfExists('suppliers');
    }
};
