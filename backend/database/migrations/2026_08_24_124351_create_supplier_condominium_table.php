<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_condominium', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'condominium_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_condominium');
    }
};
