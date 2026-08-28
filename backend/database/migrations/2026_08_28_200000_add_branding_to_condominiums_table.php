<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('description');
            $table->string('logo_mime_type')->nullable()->after('logo_path');
            $table->string('brand_color', 7)->nullable()->after('logo_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('condominiums', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'logo_mime_type', 'brand_color']);
        });
    }
};
