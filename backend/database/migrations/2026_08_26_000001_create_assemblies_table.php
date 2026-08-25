<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominium_id')->constrained('condominiums')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('minutes_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('title');
            $table->string('type')->default('ordinary');
            $table->string('status')->default('scheduled');
            $table->text('agenda');
            $table->string('location')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('held_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assemblies');
    }
};
