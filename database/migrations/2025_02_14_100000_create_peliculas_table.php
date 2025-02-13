<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peliculas', function (Blueprint $table) {
            $table->id('id_p');
            $table->text('sinopsis')->nullable();
            $table->integer('anno')->nullable();
            $table->integer('nacionalidad')->nullable();
            $table->integer('duracion')->nullable();
            $table->string('titulo', 50)->nullable();
            $table->text('imagen')->nullable();
            $table->text('trailer')->nullable();
            $table->timestamps();
        });

        // Pivot: pelicula <-> actor (Elenco)
        Schema::create('elenco', function (Blueprint $table) {
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_a');

            $table->primary(['id_p', 'id_a']);

            $table->foreign('id_p')
                ->references('id_p')
                ->on('peliculas')
                ->cascadeOnDelete();

            $table->foreign('id_a')
                ->references('id_a')
                ->on('actores')
                ->cascadeOnDelete();
        });

        // Pivot: pelicula <-> genero (Generos)
        Schema::create('pelicula_genero', function (Blueprint $table) {
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_g');

            $table->primary(['id_p', 'id_g']);

            $table->foreign('id_p')
                ->references('id_p')
                ->on('peliculas')
                ->cascadeOnDelete();

            $table->foreign('id_g')
                ->references('id_g')
                ->on('generos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelicula_genero');
        Schema::dropIfExists('elenco');
        Schema::dropIfExists('peliculas');
    }
};
