<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Quota millesimale di proprietà (su 1000), usata per ripartire le
            // spese/rate condominiali proporzionalmente. Nullable: non tutte le
            // unità la hanno impostata finché l'amministratore non la compila.
            $table->decimal('millesimi', 7, 3)->nullable()->after('surface_sqm');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('millesimi');
        });
    }
};
