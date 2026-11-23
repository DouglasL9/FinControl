<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#4f46e5');
            $table->string('accent_color', 7)->default('#7c3aed');
            $table->string('logo_path')->nullable();
            $table->unsignedTinyInteger('watermark_opacity')->default(6);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'accent_color', 'logo_path', 'watermark_opacity']);
        });
    }
};
