<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->char('cnpj', 14)->nullable()->after('legal_name');
            $table->string('email')->nullable()->after('cnpj');
            $table->string('phone', 32)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['legal_name', 'cnpj', 'email', 'phone', 'address']);
        });
    }
};
