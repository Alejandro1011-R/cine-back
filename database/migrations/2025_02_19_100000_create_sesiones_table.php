<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_s');
            $table->timestamp('fecha');

            $table->primary(['id_p', 'id_s', 'fecha']);

            $table->foreign('id_p')
                ->references('id_p')
                ->on('peliculas')
                ->cascadeOnDelete();

            $table->foreign('id_s')
                ->references('id_s')
                ->on('salas')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones');
    }
};
