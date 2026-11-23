<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('action', 32);
            $table->decimal('old_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['payable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_histories');
    }
};
