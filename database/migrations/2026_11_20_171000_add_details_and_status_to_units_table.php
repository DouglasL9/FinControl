<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('postal_code', 8)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 32)->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->char('state', 2)->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'postal_code',
                'street',
                'number',
                'complement',
                'neighborhood',
                'city',
                'state',
                'phone',
                'is_active',
            ]);
        });
    }
};
