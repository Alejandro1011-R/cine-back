<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_s');
            $table->timestamp('fecha');
            $table->char('ci', 11);
            $table->unsignedBigInteger('id_pg');
            $table->string('tipo', 50)->nullable();
            $table->timestamp('fecha_de_compra')->nullable();
            $table->string('medio_ad', 50)->nullable();
            $table->timestamps();

            $table->primary(['id_p', 'id_s', 'fecha', 'ci']);

            $table->foreign('id_pg')
                ->references('id_pg')
                ->on('pagos');

            $table->foreign('ci')
                ->references('ci')
                ->on('clientes');

            // Note: We don't add FK to sesiones composite PK here
            // as it would require all 3 columns to match.
            // The relationship is enforced at application level.
        });

        // Pivot: compra <-> butaca (ButacasReservadas)
        Schema::create('butacas_reservadas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_s');
            $table->timestamp('fecha');
            $table->char('ci', 11);
            $table->unsignedBigInteger('id_b');

            $table->primary(['id_p', 'id_s', 'fecha', 'id_b']);

            $table->foreign(['id_p', 'id_s', 'fecha', 'ci'])
                ->references(['id_p', 'id_s', 'fecha', 'ci'])
                ->on('compras')
                ->cascadeOnDelete();

            $table->foreign('id_b')
                ->references('id_b')
                ->on('butacas');
        });

        // Pivot: compra <-> descuento (Descontado)
        Schema::create('descontados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_s');
            $table->timestamp('fecha');
            $table->char('ci', 11);
            $table->unsignedBigInteger('id_d');

            $table->primary(['id_p', 'id_s', 'fecha', 'ci', 'id_d']);

            $table->foreign(['id_p', 'id_s', 'fecha', 'ci'])
                ->references(['id_p', 'id_s', 'fecha', 'ci'])
                ->on('compras')
                ->cascadeOnDelete();

            $table->foreign('id_d')
                ->references('id_d')
                ->on('descuentos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descontados');
        Schema::dropIfExists('butacas_reservadas');
        Schema::dropIfExists('compras');
    }
};
