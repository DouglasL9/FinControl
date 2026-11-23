<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::table('recurring_payables', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
        });

        $branches = DB::table('recurring_payables')
            ->select(['company_id', 'branch'])
            ->distinct()
            ->get()
            ->merge(
                DB::table('payables')
                    ->select(['company_id', 'branch'])
                    ->distinct()
                    ->get(),
            )
            ->unique(fn ($row) => $row->company_id.'|'.$row->branch);

        foreach ($branches as $branch) {
            $unitId = DB::table('units')
                ->where('company_id', $branch->company_id)
                ->where('name', $branch->branch)
                ->value('id');

            if ($unitId === null) {
                $unitId = DB::table('units')->insertGetId([
                    'company_id' => $branch->company_id,
                    'name' => $branch->branch,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('recurring_payables')
                ->where('company_id', $branch->company_id)
                ->where('branch', $branch->branch)
                ->update(['unit_id' => $unitId]);

            DB::table('payables')
                ->where('company_id', $branch->company_id)
                ->where('branch', $branch->branch)
                ->update(['unit_id' => $unitId]);
        }

        if (
            DB::table('recurring_payables')->whereNull('unit_id')->exists()
            || DB::table('payables')->whereNull('unit_id')->exists()
        ) {
            throw new RuntimeException('Unable to assign every existing payable to a unit.');
        }

        Schema::table('recurring_payables', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->dropColumn('branch');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->dropColumn('branch');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_payables', function (Blueprint $table) {
            $table->string('branch')->nullable();
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->string('branch')->nullable();
        });

        $this->restoreBranchNames('recurring_payables');
        $this->restoreBranchNames('payables');

        Schema::table('recurring_payables', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::dropIfExists('units');
    }

    private function restoreBranchNames(string $table): void
    {
        $rows = DB::table($table)
            ->join('units', 'units.id', '=', $table.'.unit_id')
            ->select([$table.'.id', 'units.name'])
            ->get();

        foreach ($rows as $row) {
            DB::table($table)->where('id', $row->id)->update(['branch' => $row->name]);
        }
    }
};
