<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pg');
            $table->timestamps();
        });

        Schema::create('efectivos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pg')->primary();
            $table->decimal('cantidad_e', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('id_pg')
                ->references('id_pg')
                ->on('pagos')
                ->cascadeOnDelete();
        });

        Schema::create('puntos_pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pg')->primary();
            $table->integer('gastados')->nullable();
            $table->timestamps();

            $table->foreign('id_pg')
                ->references('id_pg')
                ->on('pagos')
                ->cascadeOnDelete();
        });

        Schema::create('web_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pg')->primary();
            $table->string('codigo_t', 18)->nullable();
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('id_pg')
                ->references('id_pg')
                ->on('pagos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_payments');
        Schema::dropIfExists('puntos_pagos');
        Schema::dropIfExists('efectivos');
        Schema::dropIfExists('pagos');
    }
};
