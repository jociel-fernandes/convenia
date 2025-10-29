<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('name', 256);
            $table->string('email', 256);
            $table->string('cpf', 11)->unique();
            $table->string('city', 256);
            $table->string('state', 256);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Índices para performance
            $table->index(['user_id', 'email']);
            $table->index('cpf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};
