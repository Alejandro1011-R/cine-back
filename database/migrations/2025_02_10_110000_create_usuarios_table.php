<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->char('ci', 11)->primary();
            $table->string('nombre_s', 50)->nullable();
            $table->string('apellidos', 50)->nullable();
            $table->integer('puntos')->default(0);
            $table->string('codigo', 11)->nullable()->unique();
            $table->string('contrasena', 256)->nullable();
            $table->string('rol', 10)->default('Cliente');
            $table->timestamps();

            $table->foreign('ci')
                ->references('ci')
                ->on('clientes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
